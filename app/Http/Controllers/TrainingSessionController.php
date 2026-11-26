<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $canManage = $user->hasRole(Role::Admin, Role::TesdaAdmin);
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $sessions = TrainingSession::query()->with(['program:id,title,status,duration_days,training_center_id', 'center:id,name,type', 'trainer:id,name'])
            ->when($search !== '', fn ($query) => $query->where(fn ($sessions) => $sessions->whereHas('program', fn ($program) => $program->where('title', 'like', '%'.$search.'%'))->orWhereHas('center', fn ($center) => $center->where('name', 'like', '%'.$search.'%'))))
            ->withCount(['applications as accepted_count' => fn ($query) => $query->where('status', 'accepted')])
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $user->training_center_id))
            ->when(! $canManage, fn ($query) => $query->where('status', 'open')->where('is_demo', false)->where('starts_at', '>', now())->whereHas('program', fn ($program) => $program->published()->where('is_demo', false))->whereHas('center', fn ($center) => $center->where('is_active', true)->where('is_demo', false)))
            ->orderBy('starts_at')->paginate(50)->withQueryString();
        $centers = $canManage ? TrainingCenter::query()->where('is_active', true)->where('is_demo', false)->when($user->isCenterScopedTesda(), fn ($query) => $query->whereKey($user->training_center_id))->orderBy('name')->get() : collect();
        $programs = $canManage ? TrainingProgram::published()->where('is_demo', false)
            ->with([
                'center:id,name,type',
                'teachingAssignments' => fn ($query) => $query->whereHas('trainer', fn ($trainer) => $trainer->where('role', Role::TesdaAdmin->value)->where('is_active', true))
                    ->with('trainer:id,name,role,training_center_id'),
            ])
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $user->training_center_id))
            ->orderBy('title')->get(['id', 'title', 'training_center_id', 'duration_days']) : collect();

        return $request->routeIs('mobile.*') ? response()->json(compact('sessions', 'centers', 'programs', 'canManage')) : view('operations.sessions', compact('sessions', 'centers', 'programs', 'canManage'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        TrainingSession::create($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Training session saved.'], 201) : back()->with('status', 'Training session saved.');
    }

    public function update(Request $request, TrainingSession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request, $session);
        $data = $this->validatedData($request);
        $accepted = $session->applications()->where('status', 'accepted')->count();
        abort_if($data['capacity'] < $accepted, 422, 'Capacity is below accepted trainees.');
        abort_if($accepted > 0 && ((int) $data['training_center_id'] !== $session->training_center_id || (int) $data['training_program_id'] !== $session->training_program_id), 422, 'A session with accepted trainees cannot move to another center or program.');
        $session->update($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Training session updated.']) : back()->with('status', 'Training session updated.');
    }

    public function destroy(Request $request, TrainingSession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request, $session);

        if ($session->applications()->exists()) {
            $message = 'This session has application history. Close it instead to preserve trainee records.';

            return $request->routeIs('mobile.*') || $request->expectsJson()
                ? response()->json(['message' => $message], 409)
                : back()->withErrors(['session' => $message]);
        }

        $session->delete();

        return $request->routeIs('mobile.*') || $request->expectsJson()
            ? response()->json(['message' => 'Training session deleted.'])
            : back()->with('status', 'Training session deleted.');
    }

    private function authorizeManager(Request $request, ?TrainingSession $session = null): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        if ($user->isCenterScopedTesda()) {
            abort_unless($user->training_center_id && ($session === null || $session->training_center_id === $user->training_center_id), 403);
        }
    }

    private function validatedData(Request $request): array
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'training_program_id' => ['required', Rule::exists('training_programs', 'id')->where('status', 'published')->where('is_demo', false)],
            'training_center_id' => ['required', Rule::exists('training_centers', 'id')->where('is_active', true)->where('is_demo', false)],
            'trainer_id' => ['nullable', Rule::exists('users', 'id')->where('role', Role::TesdaAdmin->value)->where('is_active', true)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'training_dates' => ['sometimes', 'array', 'max:65535'],
            'training_dates.*' => ['date_format:Y-m-d', 'distinct'],
            'skip_weekends' => ['sometimes', 'boolean'],
            'non_working_dates' => ['sometimes', 'array', 'max:100'],
            'non_working_dates.*' => ['nullable', 'date_format:Y-m-d'],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', Rule::in(['open', 'closed'])],
        ]);
        $program = TrainingProgram::query()->where('is_demo', false)->findOrFail($data['training_program_id']);
        abort_if($program->training_center_id && $program->training_center_id !== (int) $data['training_center_id'], 422, 'This program belongs to another training center.');
        if ($request->user()->isCenterScopedTesda()) {
            abort_unless((int) $data['training_center_id'] === $request->user()->training_center_id, 403);
            abort_unless($program->training_center_id === $request->user()->training_center_id, 403);
        }

        $data['skip_weekends'] = $request->boolean('skip_weekends');
        $data['non_working_dates'] = array_values(array_unique(array_filter($data['non_working_dates'] ?? [], fn ($date) => is_string($date) && $date !== '')));
        $dates = array_values($data['training_dates'] ?? []);
        $durationDays = $program->duration_days;

        if ($durationDays !== null && $dates === []) {
            $dates = $this->generateTrainingDates(
                CarbonImmutable::parse($data['starts_at'])->toDateString(),
                (int) $durationDays,
                $data['skip_weekends'],
                $data['non_working_dates'],
            );
        }

        if ($data['trainer_id'] ?? null) {
            $trainer = User::query()->findOrFail($data['trainer_id']);
            $isAssignedToProgram = TrainingAssignment::query()
                ->where('training_program_id', $program->id)
                ->where('user_id', $trainer->id)
                ->exists();

            if (! $isAssignedToProgram || ($trainer->training_center_id !== null && (int) $trainer->training_center_id !== (int) $data['training_center_id'])) {
                throw ValidationException::withMessages(['trainer_id' => 'Choose a TESDA staff member assigned to this course and training center.']);
            }
        }

        if ($durationDays !== null && count($dates) !== (int) $durationDays) {
            throw ValidationException::withMessages(['training_dates' => "This course requires exactly {$durationDays} training dates."]);
        }

        if ($dates !== []) {
            $sortedDates = $dates;
            sort($sortedDates);

            if ($dates !== $sortedDates) {
                throw ValidationException::withMessages(['training_dates' => 'Training dates must be in chronological order.']);
            }

            $excludedDates = $data['non_working_dates'];
            foreach ($dates as $date) {
                $dayOfWeek = CarbonImmutable::createFromFormat('!Y-m-d', $date)->dayOfWeek;
                if (in_array($date, $excludedDates, true) || ($data['skip_weekends'] && in_array($dayOfWeek, [CarbonImmutable::SATURDAY, CarbonImmutable::SUNDAY], true))) {
                    throw ValidationException::withMessages(['training_dates' => 'Remove weekends and selected non-working days from the training dates.']);
                }
            }

            $data['training_dates'] = $dates;
            $start = CarbonImmutable::parse($data['starts_at']);
            $end = CarbonImmutable::parse($data['ends_at']);
            $data['starts_at'] = $this->withDate($start, $dates[0]);
            $data['ends_at'] = $this->withDate($end, $dates[array_key_last($dates)]);
        } else {
            $data['training_dates'] = null;
        }

        if (CarbonImmutable::parse($data['ends_at'])->lessThanOrEqualTo(CarbonImmutable::parse($data['starts_at']))) {
            throw ValidationException::withMessages(['ends_at' => 'The end time must be after the start time.']);
        }

        return $data;
    }

    /**
     * @param  array<int, string>  $nonWorkingDates
     * @return array<int, string>
     */
    private function generateTrainingDates(string $startDate, int $durationDays, bool $skipWeekends, array $nonWorkingDates): array
    {
        $dates = [];
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $startDate);

        while (count($dates) < $durationDays) {
            $dayOfWeek = $date->dayOfWeek;
            if (! in_array($date->toDateString(), $nonWorkingDates, true)
                && (! $skipWeekends || ! in_array($dayOfWeek, [CarbonImmutable::SATURDAY, CarbonImmutable::SUNDAY], true))) {
                $dates[] = $date->toDateString();
            }

            $date = $date->addDay();
        }

        return $dates;
    }

    private function withDate(CarbonImmutable $dateTime, string $date): CarbonImmutable
    {
        $newDate = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        return $dateTime->setDate($newDate->year, $newDate->month, $newDate->day);
    }
}

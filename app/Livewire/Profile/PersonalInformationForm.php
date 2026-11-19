<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;

class PersonalInformationForm extends UpdateProfileInformationForm
{
    public function updateProfileInformation(UpdatesUserProfileInformation $updater): mixed
    {
        $response = parent::updateProfileInformation($updater);
        $this->dispatch('tuklas-profile-updated', name: Auth::user()->fresh()->name);

        return $response;
    }

    public function mount(): void
    {
        parent::mount();

        $parts = preg_split('/\s+/', trim($this->state['name']), 2);
        $storedFirstName = $this->state['first_name'] ?? null;
        $storedLastName = $this->state['last_name'] ?? null;
        $middleInitial = $this->user->middle_initial ?? null;
        $storedDisplayName = collect([$storedFirstName, $middleInitial, $storedLastName])
            ->filter(fn (?string $part): bool => filled($part))->implode(' ');
        $hasStoredNames = $storedFirstName !== null && $storedLastName !== null
            && $storedDisplayName === $this->state['name'];

        $this->state['first_name'] = $hasStoredNames ? $storedFirstName : ($parts[0] ?? '');
        $this->state['last_name'] = $hasStoredNames ? $storedLastName : ($parts[1] ?? '');
        $this->state['middle_initial'] = $middleInitial ?? '';
    }
}

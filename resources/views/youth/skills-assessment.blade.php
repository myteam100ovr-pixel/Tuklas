<x-app-layout>
    <div class="dash-title"><h1>Skills self-assessment</h1></div>
    <section class="card tk-sec">
        <div class="tk-sec-title">
            <h2>Explore your starting point</h2>
            <p>This short reflection records skills you report yourself. It is not a competency test or TESDA certification. Existing profile skills and qualifications are kept.</p>
        </div>
        <form method="POST" action="{{ route('youth.assessment.store') }}" class="tk-sec-form">
            @csrf
            @if (session('status'))
                <p role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <ul role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            <fieldset class="field">
                <legend class="label">1. Which tasks have you practiced at school, work, or home?</legend>
                <p class="hint">Choose skills you can describe with an example. Leave blank if you are just getting started.</p>
                @foreach ($skills as $skill)
                    <label><input type="checkbox" name="skills[]" value="{{ $skill }}" @checked(in_array($skill, old('skills', $profile?->skills ?? []), true))> {{ $skill }}</label>
                @endforeach
            </fieldset>
            <fieldset class="field">
                <legend class="label">2. Which areas would you like to learn or work in?</legend>
                <p class="hint">Choose at least one interest.</p>
                @foreach ($interests as $interest)
                    <label><input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, old('interests', $profile?->interests ?? []), true))> {{ $interest }}</label>
                @endforeach
            </fieldset>
            <button class="btn btn-primary" type="submit">Save self-assessment</button>
        </form>
    </section>
    <section class="card tk-sec">
        <div class="tk-sec-title"><h2>Your next step</h2></div>
        <div class="tk-sec-form">
            <p>Use a resume or certificate to get AI recommendations with supporting evidence. You can also compare published programs and confirm requirements with the provider.</p>
            <p><a href="{{ route('scanner.index') }}">Scan a resume or certificate</a> · <a href="{{ route('tesda.index') }}">Explore TESDA training</a> · <a href="{{ route('youth.dashboard') }}">Back to dashboard</a></p>
        </div>
    </section>
</x-app-layout>

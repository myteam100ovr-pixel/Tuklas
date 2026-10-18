<?php

namespace App\Livewire\Profile;

use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;

class PersonalInformationForm extends UpdateProfileInformationForm
{
    public function mount(): void
    {
        parent::mount();

        $parts = preg_split('/\s+/', trim($this->state['name']), 2);
        $storedFirstName = $this->state['first_name'] ?? null;
        $storedLastName = $this->state['last_name'] ?? null;
        $hasStoredNames = $storedFirstName !== null && $storedLastName !== null
            && $storedFirstName.' '.$storedLastName === $this->state['name'];

        $this->state['first_name'] = $hasStoredNames ? $storedFirstName : ($parts[0] ?? '');
        $this->state['last_name'] = $hasStoredNames ? $storedLastName : ($parts[1] ?? '');
    }
}

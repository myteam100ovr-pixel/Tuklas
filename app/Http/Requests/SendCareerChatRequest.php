<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendCareerChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'messages' => ['required', 'array', 'min:1', 'max:10'],
            'messages.*' => ['required', 'array:role,text'],
            'messages.*.role' => ['required', 'in:user,model'],
            'messages.*.text' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $messages = $this->input('messages');
            $latest = is_array($messages) && $messages !== [] ? end($messages) : null;
            if (is_array($latest) && ($latest['role'] ?? null) !== 'user') {
                $validator->errors()->add('messages', 'The latest message must be from you.');
            }
        }];
    }
}

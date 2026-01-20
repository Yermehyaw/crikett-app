<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'phone' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.string' => 'The first name must be a valid string.',
            'last_name.string' => 'The last name must be a valid string.',
            'date_of_birth.date' => 'The date of birth must be a valid date.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already taken.',
        ];
    }
}

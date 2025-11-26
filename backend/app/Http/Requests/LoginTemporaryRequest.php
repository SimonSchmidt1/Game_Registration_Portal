<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginTemporaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'regex:/^[0-9]{7}@ucm\.sk$/'],
            'temporary_password' => ['required', 'string', 'size:14'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.regex' => 'Email musí byť v tvare: 7 číslic@ucm.sk (napr. 1234567@ucm.sk)',
            'temporary_password.size' => 'Dočasné heslo musí mať formát XXXX-XXXX-XXXX.'
        ];
    }
}

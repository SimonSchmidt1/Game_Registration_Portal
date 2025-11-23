<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy can be applied later.
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required','integer','exists:teams,id'],
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string','max:2000'],
            'category' => ['nullable','string','max:100'],
            'release_date' => ['nullable','date'],
            'trailer' => ['nullable','file','mimetypes:video/mp4,video/quicktime','max:20480'],
            'trailer_url' => ['nullable','url'],
            'splash_screen' => ['nullable','file','image','max:5120'],
            'source_code' => ['nullable','file','max:51200'],
            'export' => ['nullable','file','max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.required' => 'Tím je povinný.',
            'title.required' => 'Názov hry je povinný.',
            'trailer.max' => 'Video je príliš veľké (max 20MB).',
        ];
    }
}

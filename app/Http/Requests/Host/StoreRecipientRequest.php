<?php

namespace App\Http\Requests\Host;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'greeting' => ['nullable', 'string', 'max:500'],
            'max_guests' => ['required', 'integer', 'between:1,10'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}

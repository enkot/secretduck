<?php

namespace App\Http\Requests\Guest;

use App\Enums\RsvpResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'respondent_name' => ['required', 'string', 'max:255'],
            'response' => ['required', Rule::enum(RsvpResponse::class)],
            'guest_count' => ['required', 'integer', 'between:0,10'],
            'dietary_notes' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:3000'],
        ];
    }
}

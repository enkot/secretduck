<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'optionId' => ['nullable', 'string', 'max:64'],
            'thresholdReached' => ['nullable', 'boolean'],
            'grid' => ['nullable', 'array', 'size:16'],
            'grid.*' => ['nullable', 'integer', 'between:1,4'],
        ];
    }
}

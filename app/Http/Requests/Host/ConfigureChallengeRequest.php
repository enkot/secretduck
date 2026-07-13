<?php

namespace App\Http\Requests\Host;

use App\Enums\ChallengeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ChallengeType::class)],
            'configuration' => ['required', 'array'],
            'max_attempts' => ['sometimes', 'integer', 'between:1,10'],
        ];
    }
}

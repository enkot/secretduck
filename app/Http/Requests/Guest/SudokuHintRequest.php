<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

class SudokuHintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'grid' => ['required', 'array', 'size:16'],
            'grid.*' => ['nullable', 'integer', 'between:1,4'],
        ];
    }
}

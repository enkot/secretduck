<?php

namespace App\Http\Requests\Host;

use Illuminate\Foundation\Http\FormRequest;

class CoverImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']];
    }
}

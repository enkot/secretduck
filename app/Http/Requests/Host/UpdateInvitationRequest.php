<?php

namespace App\Http\Requests\Host;

use App\Enums\Theme;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvitationRequest extends FormRequest
{
    /** @var array<string, string> */
    private const TIMEZONE_ALIASES = [
        'Europe/Kiev' => 'Europe/Kyiv',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $httpsUrl = function (string $attribute, mixed $value, Closure $fail): void {
            if ($value !== null && $value !== '' && parse_url((string) $value, PHP_URL_SCHEME) !== 'https') {
                $fail("The {$attribute} must be an absolute HTTPS URL.");
            }
        };

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'host_names' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'timezone' => ['nullable', 'timezone:all'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'dress_code' => ['nullable', 'string', 'max:255'],
            'rsvp_deadline_at' => ['nullable', 'date'],
            'map_url' => ['nullable', 'url:http,https', 'max:2048', $httpsUrl],
            'external_url' => ['nullable', 'url:http,https', 'max:2048', $httpsUrl],
            'theme' => ['sometimes', Rule::enum(Theme::class)],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'reveal_heading' => ['nullable', 'string', 'max:255'],
            'teaser_text' => ['nullable', 'string', 'max:500'],
            'success_message' => ['nullable', 'string', 'max:500'],
            'default_max_guests' => ['sometimes', 'integer', 'between:1,10'],
            'access_expires_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $timezone = $this->input('timezone');

        if (is_string($timezone) && isset(self::TIMEZONE_ALIASES[$timezone])) {
            $this->merge([
                'timezone' => self::TIMEZONE_ALIASES[$timezone],
            ]);
        }
    }
}

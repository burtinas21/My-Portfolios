<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExperienceRequest extends FormRequest
{
    private const EMPLOYMENT_TYPES = [
        'Full-time',
        'Part-time',
        'Internship',
        'Freelance',
        'Contract',
        'Volunteer',
    ];

    private const WORK_MODES = [
        'Remote',
        'On-site',
        'Hybrid',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:120'],
            'organization' => ['required', 'string', 'max:120'],
            'period' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1500'],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
                Rule::prohibitedIf(fn (): bool => $this->boolean('is_current')),
            ],
            'is_current' => ['sometimes', 'boolean'],
            'employment_type' => ['nullable', 'string', 'max:30', Rule::in(self::EMPLOYMENT_TYPES)],
            'work_mode' => ['nullable', 'string', 'max:20', Rule::in(self::WORK_MODES)],
            'location' => ['nullable', 'string', 'max:100'],
            'highlights' => ['nullable', 'array', 'max:6'],
            'highlights.*' => ['string', 'max:200'],
            'technologies' => ['nullable', 'array', 'max:8'],
            'technologies.*' => ['string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'period',
            'description',
            'start_date',
            'end_date',
            'employment_type',
            'work_mode',
            'location',
            'highlights',
            'technologies',
        ];

        $normalized = [];
        foreach ($nullableFields as $field) {
            if ($this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        foreach (['highlights', 'technologies'] as $field) {
            $items = $this->input($field);
            if (! is_array($items)) {
                continue;
            }

            $normalized[$field] = array_values(array_filter(
                array_map(
                    static fn ($item) => is_string($item) ? trim($item) : $item,
                    $items,
                ),
                static fn ($item): bool => $item !== null && (! is_string($item) || $item !== ''),
            ));
        }

        $this->merge($normalized);
    }
}

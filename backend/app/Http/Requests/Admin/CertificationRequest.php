<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'issuer' => ['required', 'string', 'max:120'],
            'issuer_icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'credential_id' => ['nullable', 'string', 'max:100'],
            'credential_url' => ['nullable', 'string', 'max:255', 'regex:~^https://~i'],
            'image' => ['nullable', 'string', 'max:255', 'regex:~^/storage/~'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'issuer_icon',
            'expiry_date',
            'credential_id',
            'credential_url',
            'image',
            'description',
        ];

        $emptyFields = [];
        foreach ($nullableFields as $field) {
            if ($this->input($field) === '') {
                $emptyFields[$field] = null;
            }
        }

        $this->merge($emptyFields);
    }
}

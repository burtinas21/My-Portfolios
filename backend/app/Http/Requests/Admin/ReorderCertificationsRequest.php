<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCertificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certifications' => ['required', 'array', 'min:1'],
            'certifications.*.id' => ['required', 'integer', 'distinct', 'exists:certifications,id'],
            'certifications.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}

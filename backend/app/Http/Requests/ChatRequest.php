<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'messages' => 'required|array|min:1|max:10',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:500',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $messages = $this->input('messages', []);
            
            // Check that the last message is from the user
            if (!empty($messages)) {
                $lastMessage = end($messages);
                if ($lastMessage['role'] !== 'user') {
                    $validator->errors()->add(
                        'messages',
                        'The last message must be from the user.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('messages') && is_array($this->messages)) {
            // Trim and strip control characters from message content
            $messages = array_map(function ($message) {
                if (isset($message['content'])) {
                    // Strip control characters except newlines and tabs
                    $message['content'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message['content']);
                    $message['content'] = trim($message['content']);
                }
                return $message;
            }, $this->messages);
            
            $this->merge(['messages' => $messages]);
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'messages.required' => 'Messages are required.',
            'messages.min' => 'At least one message is required.',
            'messages.max' => 'Maximum 10 messages allowed.',
            'messages.*.role.required' => 'Each message must have a role.',
            'messages.*.role.in' => 'Message role must be either "user" or "assistant".',
            'messages.*.content.required' => 'Message content is required.',
            'messages.*.content.max' => 'Each message must be 500 characters or less.',
        ];
    }
}

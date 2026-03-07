<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'       => 'required|string|max:10000',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,txt,csv,xlsx,json,pptx,odt',
                'max:51200', // 50MB in KB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required'        => 'Please enter a message.',
            'message.max'             => 'Message cannot exceed 10,000 characters.',
            'attachments.max'         => 'You can upload a maximum of 5 files per message.',
            'attachments.*.file'      => 'Each attachment must be a valid file.',
            'attachments.*.mimes'     => 'Unsupported file type. Allowed types: images, documents, data files.',
            'attachments.*.max'       => 'Each file cannot exceed 50MB.',
        ];
    }
}

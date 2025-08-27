<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrescriptionStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is an admin
        return $this->user() && $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,approved,denied',
            'admin_notes' => 'nullable|string|max:1000',
            'denial_reason' => 'required_if:status,denied|nullable|string|max:1000',
        ];
    }
    
    /**
     * Get custom error messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'status.required' => 'A status is required.',
            'status.in' => 'Status must be pending, approved, or denied.',
            'admin_notes.max' => 'Admin notes cannot exceed 1000 characters.',
            'denial_reason.required_if' => 'A reason for denial is required when denying a prescription.',
            'denial_reason.max' => 'Denial reason cannot exceed 1000 characters.',
        ];
    }
}
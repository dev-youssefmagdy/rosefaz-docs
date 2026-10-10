<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الموظف مطلوب.',
            'name.max' => 'اسم الموظف طويل جدًا.',
            'branch_id.exists' => 'الفرع غير موجود.',
            'branch_id.integer' => 'الفرع غير صالح.',
            'is_active.boolean' => 'حالة الموظف غير صالحة.',
        ];
    }
}

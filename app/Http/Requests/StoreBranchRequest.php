<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($branch?->id)],
            'number' => ['required', 'integer', 'min:1', Rule::unique('branches', 'number')->ignore($branch?->id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الفرع مطلوب.',
            'name.unique' => 'اسم الفرع مستخدم من قبل.',
            'name.max' => 'اسم الفرع طويل جدًا.',
            'number.required' => 'رقم الفرع مطلوب.',
            'number.integer' => 'رقم الفرع يجب أن يكون رقمًا صحيحًا.',
            'number.min' => 'رقم الفرع غير صالح.',
            'number.unique' => 'رقم الفرع مستخدم من قبل.',
            'is_active.boolean' => 'حالة الفرع غير صالحة.',
        ];
    }
}

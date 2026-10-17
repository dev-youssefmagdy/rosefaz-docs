<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $editing = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editing?->id)],
            'password' => [$editing ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(Role::class)],
            'branch_ids' => [
                Rule::requiredIf($this->input('role') === Role::BranchManager->value),
                'nullable',
                'array',
                'min:1',
            ],
            'branch_ids.*' => ['integer', Rule::exists('branches', 'id')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'role.required' => 'الدور مطلوب.',
            'role.enum' => 'الدور غير صالح.',
            'branch_ids.required' => 'يجب اختيار فرع واحد على الأقل لمدير الفرع.',
            'branch_ids.min' => 'يجب اختيار فرع واحد على الأقل.',
            'branch_ids.*.exists' => 'أحد الفروع المحددة غير موجود.',
        ];
    }
}

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
            'branch_id' => [
                Rule::requiredIf($this->input('role') === Role::BranchManager->value),
                'nullable',
                Rule::exists('branches', 'id'),
            ],
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
            'branch_id.required' => 'الفرع مطلوب لمدير الفرع.',
            'branch_id.exists' => 'الفرع غير موجود.',
        ];
    }
}

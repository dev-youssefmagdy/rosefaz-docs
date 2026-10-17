<?php

namespace App\Http\Requests;

use App\Enums\RentalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contractRequest'));
    }

    protected function prepareForValidation(): void
    {
        $trim = fn ($v) => is_string($v) ? trim($v) : $v;

        $annualRent = (float) $this->input('annual_rent', 0);
        $months = max(1, (int) $this->input('contract_duration_months', 1));

        $this->merge([
            'full_name' => $trim($this->input('full_name')),
            'id_number' => $trim($this->input('id_number')),
            'mobile' => $trim($this->input('mobile')),
            'apartment_no' => $trim($this->input('apartment_no')) ?: null,
            'total_rent' => round($annualRent * $months, 2),
        ]);
    }

    public function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'];

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'full_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'digits:10'],
            'birth_date' => ['required', 'date', 'before:today'],
            'mobile' => ['required', 'regex:/^05\d{8}$/'],
            'needs_electricity_bill' => ['required', 'boolean'],
            'apartment_no' => ['nullable', 'string', 'max:50'],
            'annual_rent' => $money,
            'insurance_amount' => $money,
            'authentication_amount' => $money,
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'contract_duration_months' => ['required', 'integer', 'between:1,600'],
            'rental_date' => ['required', 'date'],
            'amount_paid' => [...$money, 'lte:total_rent'],
            'start_date' => ['required', 'date'],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'الفرع مطلوب.',
            'branch_id.exists' => 'الفرع المحدد غير موجود.',
            'full_name.required' => 'الاسم الرباعي مطلوب.',
            'full_name.max' => 'الاسم طويل جدًا.',
            'id_number.required' => 'رقم السجل مطلوب.',
            'id_number.digits' => 'رقم السجل يجب أن يتكون من 10 أرقام.',
            'birth_date.required' => 'تاريخ الميلاد مطلوب.',
            'birth_date.date' => 'تاريخ الميلاد غير صحيح.',
            'birth_date.before' => 'تاريخ الميلاد يجب أن يكون قبل اليوم.',
            'mobile.required' => 'رقم الجوال مطلوب.',
            'mobile.regex' => 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام.',
            'needs_electricity_bill.required' => 'حدد هل يتطلب فاتورة كهرباء.',
            'needs_electricity_bill.boolean' => 'قيمة فاتورة الكهرباء غير صحيحة.',
            'apartment_no.max' => 'رقم الشقة طويل جدًا.',
            '*.numeric' => 'يجب إدخال رقم صحيح.',
            '*.min' => 'القيمة يجب ألا تقل عن صفر.',
            '*.max' => 'القيمة كبيرة جدًا.',
            '*.decimal' => 'يسمح بخانتين عشريتين كحد أقصى.',
            'annual_rent.required' => 'قيمة الإيجار مطلوبة.',
            'insurance_amount.required' => 'مبلغ التأمين مطلوب.',
            'authentication_amount.required' => 'قيمة التوثيق مطلوبة.',
            'amount_paid.required' => 'المبلغ المدفوع مطلوب.',
            'amount_paid.lte' => 'المبلغ المدفوع لا يمكن أن يتجاوز إجمالي قيمة الإيجار.',
            'rental_type.required' => 'نوع الإيجار مطلوب.',
            'rental_type.enum' => 'نوع الإيجار غير صحيح.',
            'contract_duration_months.required' => 'مدة العقد مطلوبة.',
            'contract_duration_months.integer' => 'مدة العقد يجب أن تكون عددًا صحيحًا من الأشهر.',
            'contract_duration_months.between' => 'مدة العقد يجب أن تكون بين 1 و 600 شهر.',
            'rental_date.required' => 'تاريخ الإيجار مطلوب.',
            'rental_date.date' => 'تاريخ الإيجار غير صحيح.',
            'start_date.required' => 'تاريخ بداية العقد مطلوب.',
            'start_date.date' => 'تاريخ بداية العقد غير صحيح.',
            'user_id.required' => 'الموظف المختص مطلوب.',
            'user_id.exists' => 'الموظف المحدد غير موجود.',
        ];
    }
}

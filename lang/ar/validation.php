<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'string' => 'قيمة :attribute غير صحيحة.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'boolean' => 'قيمة :attribute غير صحيحة.',
    'date' => ':attribute ليس تاريخًا صحيحًا.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'digits' => 'يجب أن يتكون :attribute من :digits أرقام.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal خانات عشرية.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'exists' => ':attribute المحدد غير موجود.',
    'unique' => ':attribute مستخدم من قبل.',
    'enum' => ':attribute المحدد غير صحيح.',
    'in' => ':attribute المحدد غير صحيح.',
    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفًا.',
    ],
    'lte' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'string' => 'يجب ألا يتجاوز طول :attribute :value حرفًا.',
    ],
    'max' => [
        'numeric' => 'يجب ألا تتجاوز قيمة :attribute :max.',
        'string' => 'يجب ألا يتجاوز طول :attribute :max حرفًا.',
    ],
    'min' => [
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل طول :attribute عن :min أحرف.',
    ],
    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'name' => 'الاسم',
        'number' => 'رقم الفرع',
        'branch_id' => 'الفرع',
        'is_active' => 'الحالة',
        'body' => 'الملاحظة',
        'full_name' => 'الاسم الرباعي',
        'id_number' => 'رقم السجل',
        'birth_date' => 'تاريخ الميلاد',
        'mobile' => 'رقم الجوال',
        'apartment_no' => 'رقم الشقة',
    ],
];

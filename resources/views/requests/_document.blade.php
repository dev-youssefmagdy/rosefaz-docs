{{--
    Single source of truth for the "طلب توثيق عقد الكتروني" paper form.
    Params: $request (ContractRequest|null), $mode ('edit'|'view'), $branch (Branch|null, defaults to $request->branch).
    edit: yellow cells hold inputs; view: the same cells show stored values.
--}}
@php
    $r = $request ?? null;
    $edit = ($mode ?? 'view') === 'edit';
    $branch = $branch ?? $r?->branch;

    $date = fn ($d) => $d ? $d->format('d/m/Y') : '';
    $iso = fn ($d) => $d ? $d->format('Y-m-d') : '';
    $money = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2);
    $refDigits = $r ? preg_replace('/\D/', '', (string) $r->ref_no) : '';

    // Cell styles (paper look: black grid, bold bilingual labels, yellow fill-in cells).
    $ar = 'border border-black bg-white px-2 py-1.5 text-start text-[13px] font-bold leading-tight';
    $en = 'border border-black bg-white px-2 py-1.5 text-start text-[10px] font-bold uppercase leading-tight tracking-tight';
    $yellow = 'border border-black bg-[#ffff00] px-1 py-1 text-center align-middle';
    $white = 'border border-black bg-white px-1 py-1 text-center align-middle';
    $field = 'w-full bg-transparent px-1 py-1 text-center text-sm font-semibold outline-none focus:bg-yellow-100';
    $static = 'block min-h-7 px-1 py-1 text-sm font-semibold';
    $err = 'mt-0.5 hidden text-[11px] font-normal text-red-700 print:hidden';
@endphp

<div class="request-document overflow-x-auto bg-white print:overflow-visible">
    <div class="mx-auto min-w-[720px] max-w-[820px] p-4 text-black print:min-w-0 print:p-0">

        {{-- Header: logo on the right, group / department on the left --}}
        <div class="flex items-center justify-between border-b-2 border-[#a9852f] pb-3">
            <img src="{{ asset('logo.png') }}" alt="مجموعة روز فان الفندقية" class="h-20 w-auto">
            <div class="text-end leading-relaxed">
                <div class="text-lg font-bold">مجموعة روز فان الفندقية</div>
                <div class="text-base font-bold text-gray-700">الادارة العامة</div>
            </div>
        </div>

        {{-- Title bar --}}
        <div class="mt-3 border border-black bg-[#a9852f] py-2 text-center text-xl font-bold text-white">
            طلب توثيق عقد الكتروني
        </div>

        <table class="mt-2 w-full table-fixed border-collapse border border-black">
            <colgroup>
                @for ($i = 0; $i < 12; $i++)
                    <col class="w-1/12">
                @endfor
            </colgroup>
            <tbody>
                {{-- الفرع | تاريخ الطلب --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">الفرع</th>
                    <td colspan="2" class="{{ $white }}"><span class="{{ $static }}">{{ $branch?->name }}</span></td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Branch</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">تاريخ الطلب</th>
                    <td colspan="2" class="{{ $white }}"><span class="{{ $static }}" dir="ltr">{{ $r ? $date($r->request_date) : now()->format('d/m/Y') }}</span></td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Request Date</th>
                </tr>

                {{-- الاسم الرباعي --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">الاسم الرباعي</th>
                    <td colspan="8" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="text" name="full_name" maxlength="255" autocomplete="off" class="{{ $field }}" aria-label="الاسم الرباعي">
                            <p data-error-for="full_name" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}">{{ $r?->full_name }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Full Name</th>
                </tr>

                {{-- رقم السجل --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">رقم السجل</th>
                    <td colspan="8" class="{{ $white }}">
                        @include('requests._digit-boxes', ['name' => 'id_number', 'label' => 'رقم السجل', 'value' => $r?->id_number ?? '', 'edit' => $edit, 'yellow' => true])
                        @if ($edit)<p data-error-for="id_number" class="{{ $err }}"></p>@endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">ID No.</th>
                </tr>

                {{-- تاريخ الميلاد + الهجري --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">تاريخ الميلاد</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="date" name="birth_date" max="{{ now()->subDay()->format('Y-m-d') }}" class="{{ $field }}" aria-label="تاريخ الميلاد">
                            <p data-error-for="birth_date" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $date($r?->birth_date) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Date of Birth</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">التاريخ الهجري</th>
                    <td colspan="2" class="{{ $white }}"><span class="{{ $static }}" dir="ltr" data-hijri-for="birth_date">{{ $r?->birth_date_hijri }}</span></td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Hijri Date</th>
                </tr>

                {{-- رقم الجوال في أبشر --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">رقم الجوال في أبشر</th>
                    <td colspan="8" class="{{ $white }}">
                        @include('requests._digit-boxes', ['name' => 'mobile', 'label' => 'رقم الجوال في أبشر', 'value' => $r?->mobile ?? '', 'edit' => $edit, 'yellow' => true])
                        @if ($edit)<p data-error-for="mobile" class="{{ $err }}"></p>@endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Mobile No. (Absher)</th>
                </tr>

                {{-- رقم الفرع --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">رقم الفرع</th>
                    <td colspan="8" class="{{ $white }}"><span class="{{ $static }} text-base">{{ $branch?->number }}</span></td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Branch No.</th>
                </tr>

                {{-- رقم الشقة + يتطلب فاتورة كهرباء --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">رقم الشقة</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="text" name="apartment_no" maxlength="50" autocomplete="off" class="{{ $field }}" aria-label="رقم الشقة">
                            <p data-error-for="apartment_no" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}">{{ $r?->apartment_no }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Apartment No.</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">يتطلب فاتورة كهرباء</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <div class="flex items-center justify-center gap-1.5" dir="ltr" role="radiogroup" aria-label="يتطلب فاتورة كهرباء">
                                <label class="cursor-pointer border border-black px-2 py-0.5 text-sm font-bold text-red-600 has-checked:bg-red-600 has-checked:text-white">
                                    <input type="radio" name="needs_electricity_bill" value="1" class="sr-only">YES
                                </label>
                                <label class="cursor-pointer border border-black px-2 py-0.5 text-sm font-bold has-checked:bg-black has-checked:text-white">
                                    <input type="radio" name="needs_electricity_bill" value="0" class="sr-only">NO
                                </label>
                            </div>
                            <p data-error-for="needs_electricity_bill" class="{{ $err }}"></p>
                        @elseif ($r)
                            <span class="{{ $static }} {{ $r->needs_electricity_bill ? 'text-red-600' : '' }}" dir="ltr">{{ $r->needs_electricity_bill ? 'YES' : 'NO' }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Electricity Bill</th>
                </tr>

                {{-- قيمة الإيجار + مبلغ التأمين --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">قيمة الإيجار</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="number" name="annual_rent" min="0" step="0.01" inputmode="decimal" dir="ltr" class="{{ $field }}" aria-label="قيمة الإيجار">
                            <p data-error-for="annual_rent" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $money($r?->annual_rent) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Annual Rent</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">مبلغ التأمين</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="number" name="insurance_amount" min="0" step="0.01" inputmode="decimal" dir="ltr" class="{{ $field }}" aria-label="مبلغ التأمين">
                            <p data-error-for="insurance_amount" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $money($r?->insurance_amount) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Insurance Amount</th>
                </tr>

                {{-- قيمة التوثيق --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">قيمة التوثيق</th>
                    <td colspan="8" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="number" name="authentication_amount" min="0" step="0.01" inputmode="decimal" dir="ltr" class="{{ $field }}" aria-label="قيمة التوثيق">
                            <p data-error-for="authentication_amount" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $money($r?->authentication_amount) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Authentication Fee</th>
                </tr>

                {{-- نوع الايجار + مدة العقد --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">نوع الايجار</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <select name="rental_type" class="{{ $field }}" aria-label="نوع الايجار">
                                <option value="">—</option>
                                @foreach (\App\Enums\RentalType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            <p data-error-for="rental_type" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}">{{ $r?->rental_type?->label() }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Rental Type</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">مدة العقد <span class="text-[10px] font-normal">(شهر)</span></th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="number" name="contract_duration_months" min="1" max="600" step="1" value="12" inputmode="numeric" dir="ltr" class="{{ $field }}" aria-label="مدة العقد بالأشهر">
                            <p data-error-for="contract_duration_months" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}">{{ $r?->contract_duration_months }} @if ($r) شهر @endif</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Contract Duration (Months)</th>
                </tr>

                {{-- تاريخ الايجار --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">تاريخ الايجار</th>
                    <td colspan="8" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="date" name="rental_date" class="{{ $field }} mx-auto max-w-56" aria-label="تاريخ الايجار">
                            <p data-error-for="rental_date" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $date($r?->rental_date) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Rental Date</th>
                </tr>

                {{-- المبلغ المدفوع + المبلغ المتبقي --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">المبلغ المدفوع</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="number" name="amount_paid" min="0" step="0.01" inputmode="decimal" dir="ltr" class="{{ $field }}" aria-label="المبلغ المدفوع">
                            <p data-error-for="amount_paid" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $money($r?->amount_paid) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Amount Paid</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">المبلغ المتبقي</th>
                    <td colspan="2" class="{{ $white }}"><span class="{{ $static }}" dir="ltr" data-remaining>{{ $money($r?->remaining_amount) }}</span></td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Remaining Amount</th>
                </tr>

                {{-- تاريخ بداية العقد + الهجري --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">تاريخ بداية العقد</th>
                    <td colspan="2" class="{{ $yellow }}">
                        @if ($edit)
                            <input type="date" name="start_date" class="{{ $field }}" aria-label="تاريخ بداية العقد">
                            <p data-error-for="start_date" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}" dir="ltr">{{ $date($r?->start_date) }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Contract Start Date</th>
                    <th scope="row" colspan="2" class="{{ $ar }}">التاريخ الهجري</th>
                    <td colspan="2" class="{{ $white }}"><span class="{{ $static }}" dir="ltr" data-hijri-for="start_date">{{ $r?->start_date_hijri }}</span></td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Hijri Date</th>
                </tr>

                {{-- الموظف المختص --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">الموظف المختص</th>
                    <td colspan="8" class="{{ $yellow }}">
                        @if ($edit)
                            <select name="employee_id" class="{{ $field }}" aria-label="الموظف المختص">
                                <option value="">جاري التحميل…</option>
                            </select>
                            <p data-error-for="employee_id" class="{{ $err }}"></p>
                        @else
                            <span class="{{ $static }}">{{ $r?->employee?->name }}</span>
                        @endif
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Responsible Officer</th>
                </tr>

                {{-- خاص بالإدارة فقط --}}
                <tr>
                    <th colspan="12" class="border border-black bg-[#d9d9d9] px-2 py-2 text-center text-[13px] font-bold">خاص بالإدارة فقط</th>
                </tr>

                {{-- رقم العقد / Ref # --}}
                <tr>
                    <th scope="row" colspan="2" class="{{ $ar }}">رقم العقد</th>
                    <td colspan="8" class="{{ $white }}">
                        @include('requests._digit-boxes', [
                            'name' => null,
                            'value' => $refDigits,
                            'edit' => false,
                            'yellow' => false,
                            'placeholder' => $r ? $r->ref_no : 'يُولَّد تلقائيا',
                        ])
                    </td>
                    <th colspan="2" class="{{ $en }}" dir="ltr">Ref #</th>
                </tr>
            </tbody>
        </table>

        @if ($edit)
            <div class="mt-3 flex items-center gap-2 text-sm print:hidden">
                <span class="inline-block h-5 w-10 border border-black bg-[#ffff00]"></span>
                <span class="font-bold">يتم تعبئة هذا اللون فقط</span>
            </div>
        @endif
    </div>
</div>

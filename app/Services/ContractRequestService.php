<?php

namespace App\Services;

use App\Models\ContractRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractRequestService
{
    /**
     * @param  array<string, mixed>  $data  validated input
     */
    public function create(User $user, array $data): ContractRequest
    {
        $branchId = $user->branch_id;

        $employeeOk = Employee::query()
            ->whereKey($data['employee_id'])
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branchId))
            ->exists();

        if (! $employeeOk) {
            throw ValidationException::withMessages(['employee_id' => 'الموظف المختص غير متاح لهذا الفرع.']);
        }

        $attempts = 0;
        while (true) {
            try {
                return DB::transaction(function () use ($user, $data, $branchId) {
                    $request = new ContractRequest($data);
                    $request->branch_id = $branchId;
                    $request->created_by = $user->id;
                    $request->request_date = now()->toDateString();
                    $request->remaining_amount = bcsub((string) $data['annual_rent'], (string) $data['amount_paid'], 2);
                    $request->ref_no = $this->nextRefNo();
                    $request->save();

                    return $request;
                });
            } catch (UniqueConstraintViolationException $e) {
                if (++$attempts >= 3) {
                    throw $e;
                }
            }
        }
    }

    private function nextRefNo(): string
    {
        $prefix = 'RF-'.now()->year.'-';

        $last = ContractRequest::query()
            ->where('ref_no', 'like', $prefix.'%')
            ->orderByDesc('ref_no')
            ->lockForUpdate()
            ->value('ref_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function employees(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $user->isBranchManager() ? $user->branch_id : $request->query('branch_id');

        $employees = Employee::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('branch_id')->when($branchId, fn ($w) => $w->orWhere('branch_id', (int) $branchId)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($employees);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('employees.index', ['branches' => Branch::orderBy('number')->get(['id', 'name', 'number'])]);
    }

    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch_id');

        $page = Employee::query()
            ->with('branch:id,name,number')
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when($branchId === 'all', fn ($query) => $query->whereNull('branch_id'))
            ->when($branchId !== null && $branchId !== '' && $branchId !== 'all', fn ($query) => $query->where('branch_id', (int) $branchId))
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'data' => $page->items(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated() + ['is_active' => true]);

        return response()->json(['message' => 'تمت إضافة الموظف بنجاح.', 'data' => $employee->load('branch:id,name,number')], 201);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        return response()->json(['message' => 'تم تحديث الموظف بنجاح.', 'data' => $employee->load('branch:id,name,number')]);
    }

    public function toggle(Employee $employee): JsonResponse
    {
        $employee->update(['is_active' => ! $employee->is_active]);

        return response()->json([
            'message' => $employee->is_active ? 'تم تفعيل الموظف.' : 'تم إيقاف الموظف.',
            'data' => $employee,
        ]);
    }
}

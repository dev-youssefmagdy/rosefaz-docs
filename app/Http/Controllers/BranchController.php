<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('branches.index');
    }

    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $page = Branch::query()
            ->withCount(['users as managers_count' => fn ($query) => $query->where('role', 'branch_manager')])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('number', 'like', "%{$q}%")))
            ->orderBy('number')
            ->paginate(10);

        return response()->json([
            'data' => $page->items(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = Branch::create($request->validated() + ['is_active' => true]);

        return response()->json(['message' => 'تمت إضافة الفرع بنجاح.', 'data' => $branch], 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $branch->update($request->validated());

        return response()->json(['message' => 'تم تحديث الفرع بنجاح.', 'data' => $branch]);
    }

    public function toggle(Branch $branch): JsonResponse
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        return response()->json([
            'message' => $branch->is_active ? 'تم تفعيل الفرع.' : 'تم إيقاف الفرع.',
            'data' => $branch,
        ]);
    }
}

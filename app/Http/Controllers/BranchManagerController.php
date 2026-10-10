<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreManagerRequest;
use App\Http\Requests\UpdateManagerRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BranchManagerController extends Controller
{
    public function index(Branch $branch): JsonResponse
    {
        return response()->json([
            'data' => $branch->users()->where('role', Role::BranchManager)->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreManagerRequest $request, Branch $branch): JsonResponse
    {
        $user = new User($request->validated());
        $user->role = Role::BranchManager;
        $user->branch_id = $branch->id;
        $user->save();

        return response()->json(['message' => 'تمت إضافة المدير بنجاح.', 'data' => $user->only('id', 'name', 'email')], 201);
    }

    public function update(UpdateManagerRequest $request, Branch $branch, User $user): JsonResponse
    {
        $this->ensureBelongs($branch, $user);

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return response()->json(['message' => 'تم تحديث المدير بنجاح.', 'data' => $user->only('id', 'name', 'email')]);
    }

    public function destroy(Branch $branch, User $user): JsonResponse
    {
        $this->ensureBelongs($branch, $user);

        if ($user->createdRequests()->exists()) {
            throw ValidationException::withMessages(['user' => 'لا يمكن حذف مدير لديه طلبات.']);
        }

        $user->delete();

        return response()->json(['message' => 'تم حذف المدير.']);
    }

    private function ensureBelongs(Branch $branch, User $user): void
    {
        abort_unless($user->branch_id === $branch->id && $user->role === Role::BranchManager, 404);
    }
}

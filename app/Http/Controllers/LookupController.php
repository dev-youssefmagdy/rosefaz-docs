<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $user->isBranchManager() ? $user->branch_id : $request->query('branch_id');

        $users = User::query()
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', (int) $branchId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($users);
    }
}

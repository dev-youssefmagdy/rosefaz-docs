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
        $requestedBranchId = (int) $request->query('branch_id', 0) ?: null;

        if ($user->isBranchManager()) {
            $userBranchIds = $user->branches()->pluck('branches.id');
            $allowedBranchIds = $requestedBranchId && $userBranchIds->contains($requestedBranchId)
                ? collect([$requestedBranchId])
                : $userBranchIds;
        } else {
            $allowedBranchIds = $requestedBranchId ? collect([$requestedBranchId]) : null;
        }

        $users = User::query()
            ->where('is_active', true)
            ->when($allowedBranchIds, fn ($q) => $q->whereHas('branches', fn ($b) => $b->whereIn('branches.id', $allowedBranchIds)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($users);
    }
}

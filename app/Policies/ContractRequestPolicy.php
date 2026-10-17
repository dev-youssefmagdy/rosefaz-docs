<?php

namespace App\Policies;

use App\Models\ContractRequest;
use App\Models\User;

class ContractRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGeneralManager() || $user->isBranchManager();
    }

    public function view(User $user, ContractRequest $request): bool
    {
        return $user->isGeneralManager()
            || ($user->isBranchManager() && $user->branches()->where('branches.id', $request->branch_id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->isBranchManager() && $user->branches()->exists();
    }

    public function update(User $user, ContractRequest $request): bool
    {
        return $user->isGeneralManager();
    }

    public function updateRefNo(User $user, ContractRequest $request): bool
    {
        return $user->isGeneralManager();
    }

    public function delete(User $user, ContractRequest $request): bool
    {
        return $user->isGeneralManager();
    }
}

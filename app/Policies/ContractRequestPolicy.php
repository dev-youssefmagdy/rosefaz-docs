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
            || ($user->isBranchManager() && $user->branch_id !== null && $user->branch_id === $request->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->isBranchManager() && $user->branch_id !== null;
    }

    public function update(User $user, ContractRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, ContractRequest $request): bool
    {
        return false;
    }
}

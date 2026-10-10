<?php

namespace App\Policies;

use App\Models\ContractRequest;
use App\Models\RequestNote;
use App\Models\User;

class RequestNotePolicy
{
    public function viewAny(User $user, ContractRequest $request): bool
    {
        return $user->can('view', $request);
    }

    public function create(User $user, ContractRequest $request): bool
    {
        return $user->isGeneralManager();
    }

    public function update(User $user, RequestNote $note): bool
    {
        return false;
    }

    public function delete(User $user, RequestNote $note): bool
    {
        return false;
    }
}

<?php

namespace App\Enums;

enum Role: string
{
    case GeneralManager = 'general_manager';
    case BranchManager = 'branch_manager';

    public function label(): string
    {
        return match ($this) {
            self::GeneralManager => 'المدير العام',
            self::BranchManager => 'مدير فرع',
        };
    }
}

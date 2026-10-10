<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'gm@rosefaz.test'], [
            'name' => 'المدير العام',
            'password' => 'password',
            'role' => Role::GeneralManager,
            'branch_id' => null,
        ]);

        foreach ([1, 2, 3] as $n) {
            $branch = Branch::updateOrCreate(['number' => $n], ['name' => "فرع $n", 'is_active' => true]);

            User::updateOrCreate(['email' => "branch{$n}@rosefaz.test"], [
                'name' => "مدير فرع $n",
                'password' => 'password',
                'role' => Role::BranchManager,
                'branch_id' => $branch->id,
            ]);

            Employee::firstOrCreate(['branch_id' => $branch->id, 'name' => "موظف فرع $n - 1"]);
            Employee::firstOrCreate(['branch_id' => $branch->id, 'name' => "موظف فرع $n - 2"]);
        }

        Employee::firstOrCreate(['branch_id' => null, 'name' => 'موظف عام']);
    }
}

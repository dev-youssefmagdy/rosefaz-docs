<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::BranchManager,
            'is_active' => true,
        ];
    }

    public function generalManager(): static
    {
        return $this->state(fn () => ['role' => Role::GeneralManager]);
    }

    public function branchManager(?Branch $branch = null): static
    {
        $branch ??= Branch::factory()->create();

        return $this->state(fn () => ['role' => Role::BranchManager])
            ->afterCreating(function (User $user) use ($branch) {
                $user->branches()->syncWithoutDetaching([$branch->id]);
            });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\RentalType;
use App\Models\Branch;
use App\Models\ContractRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractRequest> */
class ContractRequestFactory extends Factory
{
    public function definition(): array
    {
        $rent = fake()->numberBetween(10, 60) * 1000;
        $paid = fake()->numberBetween(0, $rent / 1000) * 1000;

        return [
            'ref_no' => 'RF-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'branch_id' => Branch::factory(),
            'created_by' => fn (array $a) => User::factory()->branchManager(Branch::find($a['branch_id'])),
            'user_id' => fn (array $a) => $a['created_by'],
            'request_date' => now()->toDateString(),
            'full_name' => fake()->name(),
            'id_number' => fake()->numerify('1#########'),
            'birth_date' => fake()->date('Y-m-d', '-20 years'),
            'mobile' => fake()->numerify('05########'),
            'needs_electricity_bill' => fake()->boolean(),
            'apartment_no' => (string) fake()->numberBetween(1, 300),
            'annual_rent' => $rent,
            'insurance_amount' => 1000,
            'authentication_amount' => 250,
            'rental_type' => fake()->randomElement(RentalType::cases()),
            'contract_duration_months' => 12,
            'rental_date' => now()->toDateString(),
            'amount_paid' => $paid,
            'remaining_amount' => $rent - $paid,
            'start_date' => now()->toDateString(),
        ];
    }
}

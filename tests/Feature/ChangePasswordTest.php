<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected(): void
    {
        $this->get('/account/password')->assertRedirect('/login');
        $this->putJson('/account/password', [])->assertUnauthorized();
    }

    public function test_any_role_can_open_page_and_change_password(): void
    {
        foreach ([User::factory()->generalManager()->create(), User::factory()->branchManager()->create()] as $user) {
            $this->actingAs($user)->get('/account/password')->assertOk();
            $this->actingAs($user)->putJson('/account/password', [
                'current_password' => 'password',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])->assertOk();
            $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
        }
    }

    public function test_validation_failures(): void
    {
        $user = User::factory()->branchManager()->create();
        $old = $user->password;

        $this->actingAs($user)->putJson('/account/password', [
            'current_password' => 'wrong',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->actingAs($user)->putJson('/account/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->actingAs($user)->putJson('/account/password', [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'mismatch',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertSame($old, $user->fresh()->password);
    }
}

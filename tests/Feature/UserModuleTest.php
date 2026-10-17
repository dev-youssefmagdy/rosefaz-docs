<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ContractRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModuleTest extends TestCase
{
    use RefreshDatabase;

    private function gm(): User
    {
        return User::factory()->generalManager()->create();
    }

    public function test_branch_manager_is_forbidden_and_guest_redirected(): void
    {
        $this->get('/users')->assertRedirect('/login');
        $bm = User::factory()->branchManager()->create();
        $this->actingAs($bm)->get('/users')->assertForbidden();
        $this->actingAs($bm)->getJson('/users/list')->assertForbidden();
        $this->actingAs($bm)->postJson('/users', [])->assertForbidden();
    }

    public function test_page_list_and_filters(): void
    {
        $gm = $this->gm();
        $branch = Branch::factory()->create();
        User::factory()->branchManager($branch)->create(['name' => 'Zed Manager']);
        User::factory()->branchManager()->create(['is_active' => false]);

        $this->actingAs($gm)->get('/users')->assertOk();
        $this->actingAs($gm)->getJson('/users/list')->assertOk()->assertJsonPath('meta.total', 3);
        $this->actingAs($gm)->getJson('/users/list?role=general_manager')->assertJsonPath('meta.total', 1);
        $this->actingAs($gm)->getJson("/users/list?branch_id={$branch->id}")->assertJsonPath('meta.total', 1);
        $this->actingAs($gm)->getJson('/users/list?status=inactive')->assertJsonPath('meta.total', 1);
        $this->actingAs($gm)->getJson('/users/list?q=Zed')->assertJsonPath('meta.total', 1);
    }

    public function test_create_branch_manager_and_general_manager(): void
    {
        $gm = $this->gm();
        $branch = Branch::factory()->create();

        $this->actingAs($gm)->postJson('/users', [
            'name' => 'BM', 'email' => 'bm@x.test', 'password' => 'secret-pass', 'role' => 'branch_manager',
            'branch_ids' => [$branch->id], 'is_active' => true,
        ])->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'bm@x.test', 'role' => 'branch_manager']);
        $this->assertDatabaseHas('branch_user', ['branch_id' => $branch->id]);

        // a general manager never gets a branch even if one is sent
        $this->actingAs($gm)->postJson('/users', [
            'name' => 'GM2', 'email' => 'gm2@x.test', 'password' => 'secret-pass', 'role' => 'general_manager',
            'branch_ids' => [$branch->id], 'is_active' => true,
        ])->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'gm2@x.test', 'role' => 'general_manager']);
    }

    public function test_validation(): void
    {
        $gm = $this->gm();
        $this->actingAs($gm)->postJson('/users', [
            'name' => 'x', 'email' => $gm->email, 'password' => 'short', 'role' => 'branch_manager', 'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'branch_ids']);

        $this->actingAs($gm)->postJson('/users', [
            'name' => 'x', 'email' => 'a@b.test', 'password' => 'secret-pass', 'role' => 'hacker', 'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('role');
    }

    public function test_update_with_optional_password(): void
    {
        $gm = $this->gm();
        $bm = User::factory()->branchManager()->create();
        $old = $bm->password;
        $other = Branch::factory()->create();

        $this->actingAs($gm)->putJson("/users/{$bm->id}", [
            'name' => 'New', 'email' => $bm->email, 'password' => '', 'role' => 'branch_manager',
            'branch_ids' => [$other->id], 'is_active' => true,
        ])->assertOk();
        $this->assertSame($old, $bm->fresh()->password);
        $this->assertDatabaseHas('branch_user', ['branch_id' => $other->id, 'user_id' => $bm->id]);

        $this->actingAs($gm)->putJson("/users/{$bm->id}", [
            'name' => 'New', 'email' => $bm->email, 'password' => 'brand-new-pass', 'role' => 'branch_manager',
            'branch_ids' => [$other->id], 'is_active' => true,
        ])->assertOk();
        $this->assertNotSame($old, $bm->fresh()->password);
    }

    public function test_self_protection(): void
    {
        $gm = $this->gm();
        $payload = ['name' => $gm->name, 'email' => $gm->email, 'role' => 'general_manager', 'is_active' => true];

        $this->actingAs($gm)->putJson("/users/{$gm->id}", ['role' => 'branch_manager', 'branch_ids' => [Branch::factory()->create()->id]] + $payload)
            ->assertUnprocessable();
        $this->actingAs($gm)->putJson("/users/{$gm->id}", ['is_active' => false] + $payload)->assertUnprocessable();
        $this->actingAs($gm)->patchJson("/users/{$gm->id}/toggle")->assertUnprocessable();
        $this->actingAs($gm)->deleteJson("/users/{$gm->id}")->assertUnprocessable();
        $this->assertTrue($gm->fresh()->isGeneralManager());
    }

    public function test_toggle_and_delete_rules(): void
    {
        $gm = $this->gm();
        $bm = User::factory()->branchManager()->create();

        $this->actingAs($gm)->patchJson("/users/{$bm->id}/toggle")->assertOk();
        $this->assertFalse($bm->fresh()->is_active);

        $busy = User::factory()->branchManager()->create();
        $busyBranch = $busy->branches()->first();
        ContractRequest::factory()->create(['created_by' => $busy->id, 'branch_id' => $busyBranch->id]);
        $this->actingAs($gm)->deleteJson("/users/{$busy->id}")->assertUnprocessable();
        $this->assertModelExists($busy);

        $this->actingAs($gm)->deleteJson("/users/{$bm->id}")->assertOk();
        $this->assertModelMissing($bm);
    }

    public function test_inactive_user_cannot_login_and_is_logged_out(): void
    {
        $user = User::factory()->branchManager()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $active = User::factory()->branchManager()->create();
        $this->actingAs($active)->get('/home')->assertOk();
        $active->update(['is_active' => false]);
        $this->actingAs($active->fresh())->get('/home')->assertRedirect('/login');
        $this->assertGuest();
    }
}

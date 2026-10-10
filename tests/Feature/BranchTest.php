<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private function gm(): User
    {
        return User::factory()->generalManager()->create();
    }

    public function test_page_and_list(): void
    {
        $branch = Branch::factory()->create(['name' => 'فرع الرياض']);
        User::factory()->branchManager($branch)->create();

        $this->actingAs($this->gm())->get('/branches')->assertOk();
        $this->actingAs($this->gm())->getJson('/branches/list?q=الرياض')
            ->assertOk()->assertJsonPath('data.0.managers_count', 1)->assertJsonPath('meta.last_page', 1);
    }

    public function test_crud_and_toggle(): void
    {
        $gm = $this->actingAs($this->gm());

        $gm->postJson('/branches', ['name' => 'A', 'number' => 5, 'is_active' => true])->assertCreated();
        $branch = Branch::first();
        $gm->putJson("/branches/{$branch->id}", ['name' => 'B', 'number' => 5, 'is_active' => true])->assertOk();
        $this->assertSame('B', $branch->fresh()->name);
        $gm->patchJson("/branches/{$branch->id}/toggle")->assertOk();
        $this->assertFalse($branch->fresh()->is_active);
    }

    public function test_unique_validation(): void
    {
        Branch::factory()->create(['name' => 'A', 'number' => 1]);
        $this->actingAs($this->gm())->postJson('/branches', ['name' => 'A', 'number' => 1])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'number']);
    }

    public function test_branch_manager_forbidden(): void
    {
        $bm = User::factory()->branchManager()->create();
        $this->actingAs($bm)->get('/branches')->assertForbidden();
        $this->actingAs($bm)->postJson('/branches', ['name' => 'x', 'number' => 9])->assertForbidden();
        $this->actingAs($bm)->getJson('/branches/'.$bm->branch_id.'/managers')->assertForbidden();
    }

    public function test_managers_crud(): void
    {
        $branch = Branch::factory()->create();
        $gm = $this->actingAs($this->gm());

        $gm->postJson("/branches/{$branch->id}/managers", ['name' => 'M', 'email' => 'm@x.test', 'password' => 'secret123', 'branch_id' => 999])->assertCreated();
        $user = User::where('email', 'm@x.test')->first();
        $this->assertSame($branch->id, $user->branch_id);
        $this->assertTrue($user->isBranchManager());

        $gm->getJson("/branches/{$branch->id}/managers")->assertOk()->assertJsonCount(1, 'data');
        $gm->putJson("/branches/{$branch->id}/managers/{$user->id}", ['name' => 'N', 'email' => 'm@x.test'])->assertOk();
        $this->assertSame('N', $user->fresh()->name);
        $gm->deleteJson("/branches/{$branch->id}/managers/{$user->id}")->assertOk();
        $this->assertModelMissing($user);
    }

    public function test_manager_cannot_be_edited_through_other_branch(): void
    {
        $a = Branch::factory()->create();
        $b = Branch::factory()->create();
        $user = User::factory()->branchManager($a)->create();
        $gm = $this->actingAs($this->gm());

        $gm->putJson("/branches/{$b->id}/managers/{$user->id}", ['name' => 'X', 'email' => 'x@x.test'])->assertNotFound();
        $gm->deleteJson("/branches/{$b->id}/managers/{$user->id}")->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function gm(): User
    {
        return User::factory()->generalManager()->create();
    }

    public function test_crud_and_toggle(): void
    {
        $branch = Branch::factory()->create();
        $gm = $this->actingAs($this->gm());

        $gm->get('/employees')->assertOk();
        $gm->postJson('/employees', ['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');
        $gm->postJson('/employees', ['name' => 'أحمد', 'branch_id' => $branch->id])->assertCreated();
        $gm->postJson('/employees', ['name' => 'عام', 'branch_id' => null])->assertCreated();
        $gm->postJson('/employees', ['name' => 'x', 'branch_id' => 9999])->assertUnprocessable();

        $emp = Employee::where('name', 'أحمد')->first();
        $gm->putJson("/employees/{$emp->id}", ['name' => 'محمد', 'branch_id' => null, 'is_active' => true])->assertOk();
        $this->assertSame('محمد', $emp->fresh()->name);
        $this->assertNull($emp->fresh()->branch_id);
        $gm->patchJson("/employees/{$emp->id}/toggle")->assertOk();
        $this->assertFalse($emp->fresh()->is_active);
        $gm->deleteJson("/employees/{$emp->id}")->assertStatus(405);
    }

    public function test_list_search_and_branch_filter(): void
    {
        [$a, $b] = Branch::factory()->count(2)->create();
        Employee::factory()->create(['name' => 'سالم', 'branch_id' => $a->id]);
        Employee::factory()->create(['name' => 'خالد', 'branch_id' => $b->id]);
        Employee::factory()->global()->create(['name' => 'عام']);
        $gm = $this->actingAs($this->gm());

        $gm->getJson('/employees/list')->assertJsonPath('meta.total', 3);
        $gm->getJson("/employees/list?branch_id={$a->id}")->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'سالم');
        $gm->getJson('/employees/list?branch_id=all')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'عام');
        $gm->getJson('/employees/list?q=خال')->assertJsonPath('meta.total', 1);
    }

    public function test_lookup_scoping(): void
    {
        [$a, $b] = Branch::factory()->count(2)->create();
        Employee::factory()->create(['name' => 'A1', 'branch_id' => $a->id]);
        Employee::factory()->create(['name' => 'A2', 'branch_id' => $a->id, 'is_active' => false]);
        Employee::factory()->create(['name' => 'B1', 'branch_id' => $b->id]);
        Employee::factory()->global()->create(['name' => 'G']);

        $bm = User::factory()->branchManager($a)->create();
        $names = collect($this->actingAs($bm)->getJson("/lookups/employees?branch_id={$b->id}")->assertOk()->json())->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['A1', 'G'], $names);

        $names = collect($this->actingAs($this->gm())->getJson("/lookups/employees?branch_id={$b->id}")->json())->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['B1', 'G'], $names);

        $this->app['auth']->forgetGuards();
        $this->getJson('/lookups/employees')->assertUnauthorized();
    }

    public function test_branch_manager_forbidden_on_module(): void
    {
        $bm = User::factory()->branchManager(Branch::factory()->create())->create();
        $this->actingAs($bm)->get('/employees')->assertForbidden();
        $this->actingAs($bm)->getJson('/employees/list')->assertForbidden();
        $this->actingAs($bm)->postJson('/employees', ['name' => 'x'])->assertForbidden();
    }
}

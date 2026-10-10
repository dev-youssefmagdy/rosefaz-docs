<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ContractRequest;
use App\Models\Employee;
use App\Models\RequestNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ContractRequestTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $bm;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->bm = User::factory()->branchManager($this->branch)->create();
        $this->employee = Employee::factory()->create(['branch_id' => $this->branch->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'محمد أحمد علي سالم',
            'id_number' => '1234567890',
            'birth_date' => '1990-05-15',
            'mobile' => '0551234567',
            'needs_electricity_bill' => true,
            'apartment_no' => '12',
            'annual_rent' => '30000',
            'insurance_amount' => '1500',
            'authentication_amount' => '250.50',
            'rental_type' => 'residential',
            'contract_duration_months' => 12,
            'rental_date' => '2026-10-01',
            'amount_paid' => '10000.25',
            'start_date' => '2026-10-10',
            'employee_id' => $this->employee->id,
        ], $overrides);
    }

    public function test_branch_manager_creates_request_with_server_side_fields(): void
    {
        $other = Branch::factory()->create();

        $res = $this->actingAs($this->bm)->postJson('/requests', $this->payload([
            'branch_id' => $other->id,
            'remaining_amount' => '1',
            'created_by' => 999,
            'ref_no' => 'HACK',
            'request_date' => '2000-01-01',
        ]))->assertCreated()->assertJsonStructure(['id', 'url', 'ref_no']);

        $r = ContractRequest::findOrFail($res->json('id'));
        $this->assertSame($this->branch->id, $r->branch_id);
        $this->assertSame($this->bm->id, $r->created_by);
        $this->assertSame('19999.75', $r->remaining_amount);
        $this->assertSame(today()->toDateString(), $r->request_date->toDateString());
        $this->assertSame('RF-'.now()->year.'-000001', $r->ref_no);
        $this->assertNull($r->viewed_at);
        $this->assertSame('29/04/1448', $r->start_date_hijri);
        $this->assertNotEmpty($r->birth_date_hijri);
    }

    public function test_ref_no_is_sequential_and_unique(): void
    {
        $refs = collect(range(1, 3))->map(fn () => $this->actingAs($this->bm)->postJson('/requests', $this->payload())->assertCreated()->json('ref_no'));

        $y = now()->year;
        $this->assertSame(["RF-$y-000001", "RF-$y-000002", "RF-$y-000003"], $refs->all());
    }

    public function test_general_manager_cannot_create(): void
    {
        $gm = User::factory()->generalManager()->create();
        $this->actingAs($gm)->get('/requests/create')->assertForbidden();
        $this->actingAs($gm)->postJson('/requests', $this->payload())->assertForbidden();
        $this->assertSame(0, ContractRequest::count());
    }

    public function test_create_page_renders_document_for_branch_manager(): void
    {
        $this->actingAs($this->bm)->get('/requests/create')->assertOk()
            ->assertSee('طلب توثيق عقد الكتروني')
            ->assertSee('يتم تعبئة هذا اللون فقط')
            ->assertSee('يُولَّد تلقائيا')
            ->assertSee($this->branch->name)
            ->assertSee('name="id_number"', false);
    }

    public function test_validation_rules(): void
    {
        $bm = $this->actingAs($this->bm);

        $bm->postJson('/requests', [])->assertUnprocessable()->assertJsonValidationErrors([
            'full_name', 'id_number', 'birth_date', 'mobile', 'needs_electricity_bill', 'annual_rent',
            'insurance_amount', 'authentication_amount', 'rental_type', 'contract_duration_months',
            'rental_date', 'amount_paid', 'start_date', 'employee_id',
        ]);

        $bm->postJson('/requests', $this->payload([
            'id_number' => '12345',
            'mobile' => '0661234567',
            'rental_type' => 'villa',
            'contract_duration_months' => 0,
            'annual_rent' => '-5',
            'birth_date' => 'not-a-date',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['id_number', 'mobile', 'rental_type', 'contract_duration_months', 'annual_rent', 'birth_date']);

        $bm->postJson('/requests', $this->payload(['amount_paid' => '30000.01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_paid' => 'المبلغ المدفوع لا يمكن أن يتجاوز قيمة الإيجار.']);

        $bm->postJson('/requests', $this->payload(['mobile' => '055123456a']))->assertJsonValidationErrors('mobile');
        $bm->postJson('/requests', $this->payload(['contract_duration_months' => 601]))->assertJsonValidationErrors('contract_duration_months');

        $this->assertSame(0, ContractRequest::count());
    }

    public function test_employee_must_be_active_and_of_branch_or_global(): void
    {
        $foreign = Employee::factory()->create();
        $inactive = Employee::factory()->create(['branch_id' => $this->branch->id, 'is_active' => false]);
        $global = Employee::factory()->global()->create();
        $bm = $this->actingAs($this->bm);

        $bm->postJson('/requests', $this->payload(['employee_id' => $foreign->id]))->assertJsonValidationErrors('employee_id');
        $bm->postJson('/requests', $this->payload(['employee_id' => $inactive->id]))->assertJsonValidationErrors('employee_id');
        $bm->postJson('/requests', $this->payload(['employee_id' => 99999]))->assertJsonValidationErrors('employee_id');
        $bm->postJson('/requests', $this->payload(['employee_id' => $global->id]))->assertCreated();
    }

    public function test_list_is_scoped_per_role(): void
    {
        $otherBranch = Branch::factory()->create();
        ContractRequest::factory()->count(2)->create(['branch_id' => $this->branch->id, 'created_by' => $this->bm->id]);
        ContractRequest::factory()->create(['branch_id' => $otherBranch->id, 'full_name' => 'زيد الغريب']);
        ContractRequest::factory()->create(['branch_id' => $otherBranch->id, 'viewed_at' => now()]);

        $this->actingAs($this->bm)->get('/requests')->assertOk();
        $this->actingAs($this->bm)->getJson('/requests/list')->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.is_new', false);
        $this->actingAs($this->bm)->getJson("/requests/list?branch_id={$otherBranch->id}")->assertJsonPath('meta.total', 2);

        $gm = User::factory()->generalManager()->create();
        $this->actingAs($gm)->get('/requests')->assertOk();
        $this->actingAs($gm)->getJson('/requests/list')->assertJsonPath('meta.total', 4);
        $res = $this->actingAs($gm)->getJson("/requests/list?branch_id={$otherBranch->id}")->assertJsonPath('meta.total', 2);
        $this->assertEqualsCanonicalizing([true, false], array_column($res->json('data'), 'is_new'));
        $this->actingAs($gm)->getJson('/requests/list?q='.urlencode('زيد'))->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.full_name', 'زيد الغريب');

        $this->assertSame(2, ContractRequest::forUser($this->bm)->count());
        $this->assertSame(4, ContractRequest::forUser($gm)->count());
    }

    public function test_requests_cannot_be_updated_or_deleted(): void
    {
        $r = ContractRequest::factory()->create(['branch_id' => $this->branch->id, 'created_by' => $this->bm->id]);
        $gm = User::factory()->generalManager()->create();

        foreach ([$this->bm, $gm] as $user) {
            $this->assertFalse($user->can('update', $r));
            $this->assertFalse($user->can('delete', $r));
            foreach (['putJson', 'patchJson', 'deleteJson'] as $method) {
                $this->assertContains($this->actingAs($user)->{$method}("/requests/{$r->id}", ['full_name' => 'x'])->status(), [404, 405]);
            }
        }
        $this->assertNotSame('x', $r->fresh()->full_name);
    }

    public function test_view_policy(): void
    {
        $r = ContractRequest::factory()->create(['branch_id' => $this->branch->id, 'created_by' => $this->bm->id]);
        $otherBm = User::factory()->branchManager()->create();

        $this->assertTrue($this->bm->can('view', $r));
        $this->assertFalse($otherBm->can('view', $r));
        $this->assertTrue(User::factory()->generalManager()->create()->can('view', $r));
    }

    public function test_notes_are_append_only(): void
    {
        $r = ContractRequest::factory()->create();
        $note = new RequestNote(['body' => 'ملاحظة']);
        $note->contract_request_id = $r->id;
        $note->user_id = User::factory()->generalManager()->create()->id;
        $note->save();

        $this->assertNotNull($note->created_at);
        $this->assertCount(1, $r->notes);

        $this->expectException(LogicException::class);
        $note->update(['body' => 'changed']);
    }

    public function test_notes_cannot_be_deleted(): void
    {
        $r = ContractRequest::factory()->create();
        $note = new RequestNote(['body' => 'ملاحظة']);
        $note->contract_request_id = $r->id;
        $note->user_id = $r->created_by;
        $note->save();

        $this->expectException(LogicException::class);
        $note->delete();
    }

    public function test_deleting_manager_with_requests_returns_422(): void
    {
        $gm = User::factory()->generalManager()->create();
        ContractRequest::factory()->create(['branch_id' => $this->branch->id, 'created_by' => $this->bm->id]);

        $this->actingAs($gm)->deleteJson("/branches/{$this->branch->id}/managers/{$this->bm->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user' => 'لا يمكن حذف مدير لديه طلبات.']);
        $this->assertNotNull($this->bm->fresh());
    }
}

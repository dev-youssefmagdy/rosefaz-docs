<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ContractRequest;
use App\Models\RequestNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RequestShowNotesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $gm;

    private User $bm;

    private ContractRequest $req;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->gm = User::factory()->generalManager()->create();
        $this->bm = User::factory()->branchManager($this->branch)->create();
        $this->req = ContractRequest::factory()->create(['branch_id' => $this->branch->id]);
    }

    private function note(string $body, $at = null): RequestNote
    {
        $n = new RequestNote(['body' => $body]);
        $n->contract_request_id = $this->req->id;
        $n->user_id = $this->gm->id;
        $n->created_at = $at ?? now();
        $n->save();

        return $n;
    }

    public function test_show_access_matrix(): void
    {
        $this->actingAs($this->gm)->get(route('requests.show', $this->req))->assertOk()->assertSee($this->req->ref_no);
        $this->actingAs($this->bm)->get(route('requests.show', $this->req))->assertOk();
        $other = User::factory()->branchManager()->create();
        $this->actingAs($other)->get(route('requests.show', $this->req))->assertForbidden();
    }

    public function test_viewed_at_set_once_and_only_by_gm(): void
    {
        $this->actingAs($this->bm)->get(route('requests.show', $this->req));
        $this->assertNull($this->req->fresh()->viewed_at);

        $this->actingAs($this->gm)->get(route('requests.show', $this->req));
        $first = $this->req->fresh()->viewed_at;
        $this->assertNotNull($first);

        $this->travel(5)->minutes();
        $this->actingAs($this->gm)->get(route('requests.show', $this->req));
        $this->assertTrue($first->equalTo($this->req->fresh()->viewed_at));
    }

    public function test_gm_can_add_note_and_bm_cannot(): void
    {
        $this->actingAs($this->gm)->postJson(route('requests.notes.store', $this->req), ['body' => 'ملاحظة'])
            ->assertCreated()->assertJsonPath('data.body', 'ملاحظة')->assertJsonPath('data.author', $this->gm->name);
        $this->assertDatabaseCount('request_notes', 1);

        $this->postJson(route('requests.notes.store', $this->req), ['body' => ''])
            ->assertUnprocessable()->assertJsonPath('errors.body.0', 'حقل الملاحظة مطلوب.');
        $this->postJson(route('requests.notes.store', $this->req), ['body' => str_repeat('a', 2001)])
            ->assertUnprocessable()->assertJsonPath('errors.body.0', 'يجب ألا يتجاوز طول الملاحظة 2000 حرفًا.');

        $this->actingAs($this->bm)->postJson(route('requests.notes.store', $this->req), ['body' => 'x'])->assertForbidden();
        $this->assertDatabaseCount('request_notes', 1);
    }

    public function test_notes_list_newest_first_and_scoped(): void
    {
        $this->note('old', now()->subDay());
        $this->note('new', now());

        $this->actingAs($this->bm)->getJson(route('requests.notes.index', $this->req))
            ->assertOk()->assertJsonPath('data.0.body', 'new')->assertJsonPath('data.1.body', 'old');

        $other = User::factory()->branchManager()->create();
        $this->actingAs($other)->getJson(route('requests.notes.index', $this->req))->assertForbidden();
    }

    public function test_notes_are_immutable(): void
    {
        $n = $this->note('x');
        $this->assertFalse($this->gm->can('update', $n));
        $this->assertFalse($this->gm->can('delete', $n));

        $this->actingAs($this->gm);
        $this->putJson("/requests/{$this->req->id}/notes/{$n->id}", ['body' => 'y'])->assertStatus(404);
        $this->deleteJson("/requests/{$this->req->id}/notes/{$n->id}")->assertStatus(404);
        $this->putJson("/requests/{$this->req->id}/notes", ['body' => 'y'])->assertStatus(405);
        $this->deleteJson("/requests/{$this->req->id}/notes")->assertStatus(405);

        try {
            $n->update(['body' => 'z']);
            $this->fail('update should throw');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $n->delete();
    }

    public function test_list_includes_notes_count_and_new_flag_clears(): void
    {
        $this->note('a');
        $this->note('b');
        $row = fn () => $this->actingAs($this->gm)->getJson(route('requests.list'))->json('data.0');

        $this->assertTrue($row()['is_new']);
        $this->assertSame(2, $row()['notes_count']);

        $this->get(route('requests.show', $this->req));
        $this->assertFalse($row()['is_new']);
    }
}

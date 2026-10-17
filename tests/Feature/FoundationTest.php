<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds(): void
    {
        $user = User::factory()->generalManager()->create(['email' => 'a@b.test']);

        $this->post('/login', ['email' => 'a@b.test', 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'a@b.test']);

        $this->from('/login')->post('/login', ['email' => 'a@b.test', 'password' => 'bad'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_redirected_and_home_works(): void
    {
        $this->get('/home')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/home')->assertOk();
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/home');
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_role_middleware_blocks_branch_manager(): void
    {
        Route::middleware(['web', 'auth', 'role:general_manager'])->get('/_gm-only', fn () => 'ok');

        $this->actingAs(User::factory()->branchManager()->create())->get('/_gm-only')->assertForbidden();
        $this->actingAs(User::factory()->generalManager()->create())->get('/_gm-only')->assertOk();
    }

    public function test_seeder_creates_expected_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $gm = User::where('email', 'gm@rosefaz.test')->first();
        $this->assertTrue($gm->isGeneralManager());
        $this->assertSame(3, Branch::count());

        foreach ([1, 2, 3] as $n) {
            $bm = User::where('email', "branch{$n}@rosefaz.test")->first();
            $this->assertTrue($bm->isBranchManager());
            $this->assertSame($n, $bm->branches()->first()->number);
        }
    }
}

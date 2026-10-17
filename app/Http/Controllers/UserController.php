<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Models\Branch;
use App\Models\RequestNote;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'branches' => Branch::orderBy('number')->get(['id', 'name', 'number']),
            'roles' => Role::cases(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $page = User::query()
            ->with(['branches:id,name,number'])
            ->withCount('createdRequests')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when(Role::tryFrom((string) $request->query('role')), fn ($query, $role) => $query->where('role', $role))
            ->when($request->filled('branch_id'), fn ($query) => $query->whereHas('branches', fn ($q) => $q->where('branches.id', (int) $request->query('branch_id'))))
            ->when(in_array($request->query('status'), ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $request->query('status') === 'active'))
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'data' => collect($page->items())->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role->value,
                'role_label' => $u->role->label(),
                'branch_ids' => $u->branches->pluck('id'),
                'branch' => $u->branches->isNotEmpty()
                    ? $u->branches->map(fn ($b) => $b->only('id', 'name', 'number'))
                    : null,
                'is_active' => $u->is_active,
                'requests_count' => $u->created_requests_count,
                'is_self' => $u->is(auth()->user()),
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        [$attributes, $branchIds] = $this->attributes($request);
        $user = new User($attributes);
        $user->is_active = $request->boolean('is_active');
        $user->save();
        if ($branchIds !== null) {
            $user->branches()->sync($branchIds);
        }

        return response()->json(['message' => 'تمت إضافة المستخدم بنجاح.'], 201);
    }

    public function update(StoreUserRequest $request, User $user): JsonResponse
    {
        [$data, $branchIds] = $this->attributes($request);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($user->is($request->user())) {
            if ($data['role'] !== Role::GeneralManager) {
                $this->refuse('لا يمكنك تغيير دورك.');
            }
            if (! $request->boolean('is_active')) {
                $this->refuse('لا يمكنك إيقاف حسابك.');
            }
        }

        $user->fill($data);
        $user->is_active = $request->boolean('is_active');
        $user->save();
        if ($branchIds !== null) {
            $user->branches()->sync($branchIds);
        } else {
            $user->branches()->detach();
        }

        return response()->json(['message' => 'تم تحديث المستخدم بنجاح.']);
    }

    public function toggle(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            $this->refuse('لا يمكنك إيقاف حسابك.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['message' => $user->is_active ? 'تم تفعيل المستخدم.' : 'تم إيقاف المستخدم.']);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            $this->refuse('لا يمكنك حذف حسابك.');
        }
        if ($user->createdRequests()->exists() || RequestNote::where('user_id', $user->id)->exists()) {
            $this->refuse('لا يمكن حذف مستخدم لديه طلبات أو ملاحظات، يمكنك إيقافه بدلاً من ذلك.');
        }

        $user->delete();

        return response()->json(['message' => 'تم حذف المستخدم.']);
    }

    /**
     * Returns [user attributes, branch_ids|null].
     * General managers get null branch_ids (pivot cleared).
     *
     * @return array{0: array<string, mixed>, 1: int[]|null}
     */
    private function attributes(StoreUserRequest $request): array
    {
        $data = $request->safe()->only(['name', 'email', 'password']);
        $data['role'] = Role::from($request->input('role'));

        if ($data['role'] === Role::BranchManager) {
            $branchIds = array_map('intval', (array) $request->input('branch_ids', []));

            return [$data, $branchIds];
        }

        return [$data, null];
    }

    private function refuse(string $message): never
    {
        throw ValidationException::withMessages(['user' => $message]);
    }
}

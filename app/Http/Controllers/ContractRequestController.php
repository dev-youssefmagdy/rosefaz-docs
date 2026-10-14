<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequestRequest;
use App\Models\Branch;
use App\Models\ContractRequest;
use App\Models\RequestNote;
use App\Services\ContractRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class ContractRequestController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ContractRequest::class);

        $branches = $request->user()->isGeneralManager()
            ? Branch::orderBy('number')->get(['id', 'name', 'number'])
            : collect();

        return view('requests.index', ['branches' => $branches]);
    }

    public function list(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ContractRequest::class);

        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch_id');

        $page = ContractRequest::query()
            ->forUser($user)
            ->with(['branch:id,name,number', 'employee:id,name'])
            ->withCount('notes')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('ref_no', 'like', "%{$q}%")
                ->orWhere('full_name', 'like', "%{$q}%")
                ->orWhere('id_number', 'like', "%{$q}%")
                ->orWhere('mobile', 'like', "%{$q}%")))
            ->when($user->isGeneralManager() && $branchId, fn ($query) => $query->where('branch_id', (int) $branchId))
            ->latest('id')
            ->paginate(15);

        $hasShow = Route::has('requests.show');

        return response()->json([
            'data' => collect($page->items())->map(fn (ContractRequest $r) => [
                'id' => $r->id,
                'ref_no' => $r->ref_no,
                'full_name' => $r->full_name,
                'branch' => $r->branch?->only('id', 'name', 'number'),
                'employee' => $r->employee?->name,
                'request_date' => $r->request_date->format('Y-m-d'),
                'notes_count' => $r->notes_count,
                'is_new' => $user->isGeneralManager() && $r->viewed_at === null,
                'url' => $hasShow ? route('requests.show', $r) : url('/requests/'.$r->id),
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, ContractRequest $contractRequest)
    {
        Gate::authorize('view', $contractRequest);

        $user = $request->user();
        if ($user->isGeneralManager() && $contractRequest->viewed_at === null) {
            ContractRequest::whereKey($contractRequest->id)->whereNull('viewed_at')->update(['viewed_at' => now()]);
        }

        $contractRequest->load(['branch', 'employee']);

        return view('requests.show', [
            'contractRequest' => $contractRequest,
            'canAddNote' => $user->can('create', [RequestNote::class, $contractRequest]),
            'canEditRefNo' => $user->can('updateRefNo', $contractRequest),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', ContractRequest::class);

        return view('requests.create', ['branch' => $request->user()->branch]);
    }

    public function updateRefNo(Request $request, ContractRequest $contractRequest): JsonResponse
    {
        Gate::authorize('updateRefNo', $contractRequest);

        $validated = $request->validate([
            'ref_no' => ['required', 'digits:11', 'unique:contract_requests,ref_no,'.$contractRequest->id],
        ], [
            'ref_no.required' => 'رقم العقد مطلوب.',
            'ref_no.digits' => 'رقم العقد يجب أن يتكون من 11 رقمًا.',
            'ref_no.unique' => 'رقم العقد مستخدم مسبقًا.',
        ]);

        $contractRequest->ref_no = $validated['ref_no'];
        $contractRequest->save();

        return response()->json(['message' => 'تم حفظ رقم العقد بنجاح.', 'ref_no' => $contractRequest->ref_no]);
    }

    public function store(StoreContractRequestRequest $request, ContractRequestService $service): JsonResponse
    {
        $contract = $service->create($request->user(), $request->validated());
        $url = Route::has('requests.show') ? route('requests.show', $contract) : url('/requests/'.$contract->id);

        return response()->json([
            'message' => 'تم إرسال الطلب بنجاح.',
            'id' => $contract->id,
            'ref_no' => $contract->ref_no,
            'url' => $url,
        ], 201);
    }
}

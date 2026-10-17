<?php

namespace App\Http\Controllers;

use App\Models\ContractRequest;
use App\Models\RequestNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RequestNoteController extends Controller
{
    public function index(ContractRequest $contractRequest): JsonResponse
    {
        Gate::authorize('viewAny', [RequestNote::class, $contractRequest]);

        $notes = $contractRequest->notes()
            ->with('user.branches:id,name,number')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $notes->map(fn (RequestNote $n) => $this->format($n))->values()]);
    }

    public function store(Request $request, ContractRequest $contractRequest): JsonResponse
    {
        Gate::authorize('create', [RequestNote::class, $contractRequest]);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $note = new RequestNote(['body' => trim($data['body'])]);
        $note->contract_request_id = $contractRequest->id;
        $note->user_id = $request->user()->id;
        $note->save();
        $note->load('user.branches:id,name,number');

        return response()->json(['message' => 'تمت إضافة الملاحظة.', 'data' => $this->format($note)], 201);
    }

    private function format(RequestNote $n): array
    {
        return [
            'id' => $n->id,
            'body' => $n->body,
            'author' => $n->user?->name,
            'role' => $n->user?->role?->label(),
            'branch' => $n->user?->branches?->first()?->name,
            'created_at' => $n->created_at?->format('d/m/Y H:i'),
        ];
    }
}

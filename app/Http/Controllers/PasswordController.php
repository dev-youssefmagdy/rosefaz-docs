<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);
        $request->session()->regenerate();

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح.']);
    }
}

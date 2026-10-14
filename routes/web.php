<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchManagerController;
use App\Http\Controllers\ContractRequestController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RequestNoteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('account.password');
    Route::put('/account/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('account.password.update');
    Route::redirect('/', '/home');
    Route::view('/home', 'home')->name('home');

    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [ContractRequestController::class, 'index'])->name('index');
        Route::get('/list', [ContractRequestController::class, 'list'])->name('list');
        Route::get('/create', [ContractRequestController::class, 'create'])->name('create');
        Route::post('/', [ContractRequestController::class, 'store'])->name('store');
        Route::get('/{contractRequest}', [ContractRequestController::class, 'show'])->whereNumber('contractRequest')->name('show');
        Route::patch('/{contractRequest}/ref-no', [ContractRequestController::class, 'updateRefNo'])->whereNumber('contractRequest')->name('ref-no.update');
        Route::get('/{contractRequest}/notes', [RequestNoteController::class, 'index'])->whereNumber('contractRequest')->name('notes.index');
        Route::post('/{contractRequest}/notes', [RequestNoteController::class, 'store'])->whereNumber('contractRequest')->name('notes.store');
    });

    Route::get('/lookups/users', [LookupController::class, 'users'])->name('lookups.users');

    Route::middleware('role:general_manager')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/list', [UserController::class, 'list'])->name('list');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('role:general_manager')->prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::get('/list', [BranchController::class, 'list'])->name('list');
        Route::post('/', [BranchController::class, 'store'])->name('store');
        Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
        Route::patch('/{branch}/toggle', [BranchController::class, 'toggle'])->name('toggle');

        Route::get('/{branch}/managers', [BranchManagerController::class, 'index'])->name('managers.index');
        Route::post('/{branch}/managers', [BranchManagerController::class, 'store'])->name('managers.store');
        Route::put('/{branch}/managers/{user}', [BranchManagerController::class, 'update'])->name('managers.update');
        Route::delete('/{branch}/managers/{user}', [BranchManagerController::class, 'destroy'])->name('managers.destroy');
    });
});

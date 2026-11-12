<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MemberController;
use App\Models\User;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\ReportController;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

    Route::bind('member', function (string $value) {
    return User::where('role', 'anggota')->findOrFail($value);
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('books', BookController::class)
            ->except(['show']);
        Route::patch('members/{member}/toggle', [MemberController::class, 'toggle'])
            ->name('members.toggle');

        Route::resource('members', MemberController::class);
        Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::post('loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
        Route::post('loans/{loan}/return', [LoanController::class, 'receiveReturn'])->name('loans.return');
        Route::get('reports/loans', [ReportController::class, 'loans'])->name('reports.loans');
        Route::get('reports/loans/export', [ReportController::class, 'export'])->name('reports.loans.export');
        });
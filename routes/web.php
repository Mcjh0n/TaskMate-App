<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminTasksController;
use App\Http\Controllers\Admin\AdminTaskDecisionController;
use App\Http\Controllers\Admin\VerificationDecisionController;
use App\Http\Controllers\VerificationDetailsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('verification/details', [VerificationDetailsController::class, 'edit'])->name('verification.details.edit');
    Route::put('verification/details', [VerificationDetailsController::class, 'update'])->name('verification.details.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUsersController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/verification', VerificationDecisionController::class)->name('users.verification');
    Route::get('/tasks', [AdminTasksController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [AdminTasksController::class, 'show'])->name('tasks.show');
    Route::post('/tasks/{task}/decision', AdminTaskDecisionController::class)->name('tasks.decision');
});

Route::middleware(['auth', 'account.verified', 'can.post'])->group(function () {
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/my-tasks', [TaskController::class, 'mine'])->name('tasks.mine');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');
});

Route::middleware(['auth', 'account.verified', 'can.apply'])->group(function () {
    Route::get('/browse', [BrowseController::class, 'index'])->name('browse.index');
    Route::get('/browse/{task}', [BrowseController::class, 'show'])->name('browse.show');
    Route::post('/browse/{task}/apply', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/my-applications', [ApplicationController::class, 'mine'])->name('applications.mine');
    Route::get('/my-applications/{application}/task', [BrowseController::class, 'applicationTask'])->name('applications.task');
    Route::post('/my-applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->name('applications.withdraw');
});

Route::middleware(['auth', 'account.verified', 'can.post'])->group(function () {
    Route::post('/tasks/{task}/applications/{application}/accept', [ApplicationController::class, 'accept'])->name('tasks.applications.accept');
    Route::post('/tasks/{task}/applications/{application}/decline', [ApplicationController::class, 'decline'])->name('tasks.applications.decline');
});

require __DIR__.'/settings.php';

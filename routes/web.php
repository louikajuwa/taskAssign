<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\TestplanController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryTesterController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;

// Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Main app
    Route::get('/', [TestplanController::class, 'index'])->name('home');

    // Notifications page
    Route::get('/notifications', [NotificationController::class, 'page'])->name('notifications');

    // Admin panel
    Route::prefix('admin')->middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

        // Category management — admin-only page (enforced here in code, not just hidden in UI)
        Route::get('/categories', [CategoryController::class, 'manage'])->name('admin.categories');
    });

    // API routes (same session auth, CSRF-protected)
    Route::prefix('api')->group(function () {
        // Testplans
        Route::post('/testplans', [TestplanController::class, 'store']);
        Route::post('/testplans/list', [TestplanController::class, 'list']);
        Route::put('/testplans/{id}', [TestplanController::class, 'update']);
        Route::delete('/testplans/{id}', [TestplanController::class, 'destroy']);

        // Assignments
        Route::post('/assignments', [AssignmentController::class, 'store']);
        Route::put('/assignments/update', [AssignmentController::class, 'update']);
        Route::delete('/assignments', [AssignmentController::class, 'destroy']);

        // Comments
        Route::post('/comments', [CommentController::class, 'store']);
        Route::post('/comments/list', [CommentController::class, 'index']);

        // Categories
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/simple', [CategoryController::class, 'lightweight']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Category Testers
        Route::get('/category-testers/{categoryId}', [CategoryTesterController::class, 'index']);
        Route::post('/category-testers', [CategoryTesterController::class, 'store']);
        Route::delete('/category-testers/{id}', [CategoryTesterController::class, 'destroy']);

        // Users
        Route::get('/users/search', [UserController::class, 'search']);
        Route::get('/users/me', [UserController::class, 'me']);
        Route::put('/users/settings', [UserController::class, 'updateSettings']);

        // Versions
        Route::get('/versions', [VersionController::class, 'index']);

        // Files
        Route::post('/files/upload', [FileController::class, 'upload']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    });
});

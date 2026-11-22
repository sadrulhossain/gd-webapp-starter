<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LocalesController;
use App\Http\Controllers\Api\V1\LogoutController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MeMenusController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\MeSessionController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SettingImageController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserSessionController;
use App\Http\Controllers\Api\V1\UserStatusController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class);
    Route::get('meta/locales', LocalesController::class);
    Route::get('public/settings', [SettingController::class, 'publicShow']);

    // Authenticated routes. Module routes add ->middleware('can:<permission>').
    Route::middleware(['auth:api', EnsureUserIsActive::class])->group(function (): void {
        Route::get('me', MeController::class);
        Route::get('me/menus', MeMenusController::class);
        Route::patch('me', [ProfileController::class, 'update']);
        Route::get('me/sessions', [MeSessionController::class, 'index']);
        Route::delete('me/sessions/{session}', [MeSessionController::class, 'destroy']);
        Route::post('logout', LogoutController::class);
        Route::get('dashboard', DashboardController::class)->middleware('can:dashboard.view');

        Route::post('settings/logo', [SettingImageController::class, 'storeLogo']);
        Route::delete('settings/logo', [SettingImageController::class, 'destroyLogo']);
        Route::post('settings/favicon', [SettingImageController::class, 'storeFavicon']);
        Route::delete('settings/favicon', [SettingImageController::class, 'destroyFavicon']);
        Route::apiSingleton('settings', SettingController::class);

        // Access: users, roles (+ the permission list for the matrix), menu tree
        Route::patch('users/{user}/status', UserStatusController::class);
        Route::get('users/{user}/sessions', [UserSessionController::class, 'index']);
        Route::delete('users/{user}/sessions', [UserSessionController::class, 'destroy']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
        Route::get('permissions', PermissionController::class)->middleware('can:roles.view');
        Route::put('menus/reorder', [MenuController::class, 'reorder']);
        Route::get('menus', [MenuController::class, 'index']);
        Route::patch('menus/{menu}', [MenuController::class, 'update']);
        Route::post('menus', [MenuController::class, 'store']);
        Route::delete('menus/{menu}', [MenuController::class, 'destroy']);
        Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show'])->parameters(['audit-logs' => 'auditLog'])->middleware('can:audit.view');

        // <make:module routes> new modules register their routes above this line
    });
});

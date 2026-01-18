<?php

use App\Http\Controllers\Api\V1\Admin\CollectiveProjectPaymentMethodsController;
use App\Http\Controllers\Api\V1\Admin\CollectiveProjectReportsController;
use App\Http\Controllers\Api\V1\Admin\ProjectMembersController;
use App\Http\Controllers\Api\V1\AdminAuthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ParticipantAuthController;
use App\Http\Controllers\Api\V1\ProjectMembershipController;
use App\Http\Controllers\Api\V1\PublicCollectiveProjectsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CollectiveProjectsController as ParticipantProjectsController;
use App\Http\Controllers\Api\V1\Admin\CollectiveProjectsController as AdminProjectsController;
use App\Http\Controllers\Api\V1\Admin\ProjectReportsController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/admin/login', [AdminAuthController::class, 'login']);
    Route::post('/auth/participant/register', [ParticipantAuthController::class, 'register']);
    Route::post('/auth/participant/login', [ParticipantAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);
    Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me']);

    Route::get('/projects', [PublicCollectiveProjectsController::class, 'index']);

    Route::middleware(['auth:sanctum', 'abilities:participant'])->group(function () {
        Route::get('/projects/{project:slug}', [ParticipantProjectsController::class, 'show']);
        Route::get('/projects/{project:slug}/membership', [ProjectMembershipController::class, 'show']);
        Route::post('/projects/{project:slug}/join', [ProjectMembershipController::class, 'join']);

        Route::get('/projects/{project:slug}/payments', [\App\Http\Controllers\Api\V1\CollectiveProjectPaymentsController::class, 'index']);
        Route::get('/projects/{project:slug}/payment-options', [\App\Http\Controllers\Api\V1\CollectiveProjectPaymentsController::class, 'options']);
        Route::post('/projects/{project:slug}/payments', [\App\Http\Controllers\Api\V1\CollectiveProjectPaymentsController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'abilities:admin'])->prefix('admin')->group(function () {
        Route::get('/projects', [AdminProjectsController::class, 'index']);
        Route::post('/projects', [AdminProjectsController::class, 'store']);
        Route::get('/projects/{project}', [AdminProjectsController::class, 'show']);
        Route::delete('/projects/{project}', [AdminProjectsController::class, 'destroy']);

        Route::get('/projects/{project}/members', [ProjectMembersController::class, 'index']);
        Route::delete('/projects/{project}/members/{user}', [ProjectMembersController::class, 'remove']);
        Route::post('/projects/{project}/members/{user}/restore', [ProjectMembersController::class, 'restore']);

        Route::scopeBindings()->group(function () {
            Route::get('/projects/{project}/payment-methods', [CollectiveProjectPaymentMethodsController::class, 'index']);
            Route::post('/projects/{project}/payment-methods', [CollectiveProjectPaymentMethodsController::class, 'store']);
            Route::patch('/projects/{project}/payment-methods/{paymentMethod}', [CollectiveProjectPaymentMethodsController::class, 'update']);
            Route::delete('/projects/{project}/payment-methods/{paymentMethod}', [CollectiveProjectPaymentMethodsController::class, 'destroy']);
            Route::post('/projects/{project}/payment-methods/{paymentMethod}/deactivate', [CollectiveProjectPaymentMethodsController::class, 'deactivate']);
            Route::post('/projects/{project}/payment-methods/{paymentMethod}/restore', [CollectiveProjectPaymentMethodsController::class, 'restore']);
        });

        Route::get('/projects/{project}/reports', [CollectiveProjectReportsController::class, 'index']);
        Route::post('/projects/{project}/reports/payment-status', [CollectiveProjectReportsController::class, 'store']);
        Route::get('/projects/{project}/reports/{report}/download', [CollectiveProjectReportsController::class, 'download']);
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');
    Route::get('/recipients/{recipientId}/notifications', [NotificationController::class, 'index'])->name('recipients.notifications.index');
});

<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DocumentGenerationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('chat.index');
});

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/select-provider', [ChatController::class, 'selectProvider'])->name('chat.select-provider');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{chatSession}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{chatSession}/messages', [ChatController::class, 'sendMessage'])->name('chat.message');
    Route::patch('/chat/{chatSession}/provider', [ChatController::class, 'updateProvider'])->name('chat.update-provider');
    Route::patch('/chat/{chatSession}/memory', [ChatController::class, 'updateMemory'])->name('chat.update-memory');
    Route::get('/chat/attachment/{chatAttachment}/download', [ChatController::class, 'downloadAttachment'])->name('chat.attachment.download');
    Route::get('/chat/message/{message}/export/{format}', [DocumentGenerationController::class, 'generate'])->name('chat.message.export');

    // Admin panel (requires admin role)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/disable', [UserManagementController::class, 'disable'])->name('users.disable');
        Route::patch('/users/{user}/enable', [UserManagementController::class, 'enable'])->name('users.enable');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

require __DIR__.'/auth.php';

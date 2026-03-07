<?php

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
});

require __DIR__.'/auth.php';

<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppAISmartReply\Http\Controllers\AppWhatsAppAISmartReplyController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('app/whatsapp/ai-smart-reply')->group(function () {
        Route::get('/', [AppWhatsAppAISmartReplyController::class, 'index'])->name('app.whatsappaismartreply.index');
        Route::post('info', [AppWhatsAppAISmartReplyController::class, 'info'])->name('app.whatsappaismartreply.info');
        Route::post('save', [AppWhatsAppAISmartReplyController::class, 'save'])->name('app.whatsappaismartreply.save');
    });
});

Route::middleware(['web'])->group(function () {
    Route::prefix('app/whatsapp/ai-smart-reply')->group(function () {
        Route::get('generate', [AppWhatsAppAISmartReplyController::class, 'generate'])->name('app.whatsappaismartreply.generate');
    });
});
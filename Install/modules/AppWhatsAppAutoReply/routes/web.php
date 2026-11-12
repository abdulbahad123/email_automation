<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppAutoReply\Http\Controllers\AppWhatsAppAutoReplyController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/whatsapp/auto-reply'], function () {
        Route::get('/', [AppWhatsAppAutoReplyController::class, 'index'])->name('app.whatsappautoreply.index');
        Route::post('info', [AppWhatsAppAutoReplyController::class, 'info'])->name('app.whatsappautoreply.info');
        Route::post('save', [AppWhatsAppAutoReplyController::class, 'save'])->name('app.whatsappautoreply.save');
    });
});

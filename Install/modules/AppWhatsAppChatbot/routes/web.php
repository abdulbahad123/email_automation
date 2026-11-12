<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppChatbot\Http\Controllers\AppWhatsAppChatbotController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/whatsapp/chatbot'], function () {
        Route::get('/', [AppWhatsAppChatbotController::class, 'index'])->name('app.whatsappchatbot.index');
        Route::post('info', [AppWhatsAppChatbotController::class, 'info'])->name('app.whatsappchatbot.info');
        Route::post('save', [AppWhatsAppChatbotController::class, 'save'])->name('app.whatsappchatbot.save');
        Route::post('status/{instance_id}', [AppWhatsAppChatbotController::class, 'status'])->name('app.whatsappchatbot.status');
        Route::post('delete/{id_secure?}', [AppWhatsAppChatbotController::class, 'delete'])->name('app.whatsappchatbot.delete');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppProfileInfo\Http\Controllers\AppWhatsAppProfileInfoController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/whatsapp/profile-info'], function () {
        Route::get('/', [AppWhatsAppProfileInfoController::class, 'index'])->name('app.whatsappprofileinfo.index');
        Route::post('info', [AppWhatsAppProfileInfoController::class, 'info'])->name('app.whatsappprofileinfo.info');
        Route::post('logout', [AppWhatsAppProfileInfoController::class, 'logout'])->name('app.whatsappprofileinfo.logout');
        Route::post('reset', [AppWhatsAppProfileInfoController::class, 'reset'])->name('app.whatsappprofileinfo.reset');
    });
});

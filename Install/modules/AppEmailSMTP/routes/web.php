<?php

use Illuminate\Support\Facades\Route;
use Modules\AppEmailSMTP\Http\Controllers\AppEmailSMTPController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/email/smtp'], function () {
        Route::get('/', [AppEmailSMTPController::class, 'index'])->name('app.emailsmtp.index');
        Route::match(['get', 'post'], 'list', [AppEmailSMTPController::class, 'list'])->name('app.emailsmtp.list');
        Route::get('popup-update/{id_secure?}', [AppEmailSMTPController::class, 'popupUpdate'])->name('app.emailsmtp.popup_update');
        Route::post('popup-update/{id_secure?}', [AppEmailSMTPController::class, 'popupUpdate']);
        Route::post('save/{id_secure?}', [AppEmailSMTPController::class, 'save'])->name('app.emailsmtp.save');
        Route::post('status/{status}', [AppEmailSMTPController::class, 'status'])->name('app.emailsmtp.status');
        Route::get('popup-test/{id_secure}', [AppEmailSMTPController::class, 'popupTest'])->name('app.emailsmtp.popup_test');
        Route::post('popup-test/{id_secure}', [AppEmailSMTPController::class, 'popupTest']);
        Route::post('test-send/{id_secure}', [AppEmailSMTPController::class, 'testSend'])->name('app.emailsmtp.test_send');
        Route::post('delete/{id_secure?}', [AppEmailSMTPController::class, 'delete'])->name('app.emailsmtp.delete');
    });
});


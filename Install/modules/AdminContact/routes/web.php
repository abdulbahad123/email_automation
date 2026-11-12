<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminContact\Http\Controllers\AdminContactController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'admin'], function () {
        // Main Contact Us admin management
        Route::get('contact-us', [AdminContactController::class, 'index'])->name('admin.contact.index');
        Route::post('contact-us/status/{id}', [AdminContactController::class, 'markStatus'])->name('admin.contact.status');
        Route::post('contact-us/destroy/{id}', [AdminContactController::class, 'destroy'])->name('admin.contact.destroy');
        Route::post('contact-us/settings', [AdminContactController::class, 'saveSettings'])->name('admin.contact.settings');

        // Aliases
        Route::get('contact', [AdminContactController::class, 'index']);
        Route::post('contact/settings', [AdminContactController::class, 'saveSettings']);
        Route::get('frontend/contact-us', [AdminContactController::class, 'index']);
        Route::post('frontend/contact-us/settings', [AdminContactController::class, 'saveSettings']);
    });
});

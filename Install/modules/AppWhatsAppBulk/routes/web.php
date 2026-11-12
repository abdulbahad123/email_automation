<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppBulk\Http\Controllers\AppWhatsAppBulkController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/whatsapp/bulk'], function () {
        Route::get('/', [AppWhatsAppBulkController::class, 'index'])->name('app.whatsappbulk.index');
        Route::match(['get', 'post'], 'list', [AppWhatsAppBulkController::class, 'list'])->name('app.whatsappbulk.list');
        Route::get('create', [AppWhatsAppBulkController::class, 'update'])->name('app.whatsappbulk.create');
        Route::get('edit/{id_secure}', [AppWhatsAppBulkController::class, 'update'])->name('app.whatsappbulk.edit');
        Route::post('save/{id_secure?}', [AppWhatsAppBulkController::class, 'save'])->name('app.whatsappbulk.save');
        Route::post('status/{id_secure}', [AppWhatsAppBulkController::class, 'status'])->name('app.whatsappbulk.status');
        Route::post('actions/{action}', [AppWhatsAppBulkController::class, 'actions'])->name('app.whatsappbulk.actions');
        Route::post('delete/{id_secure?}', [AppWhatsAppBulkController::class, 'delete'])->name('app.whatsappbulk.delete');
        Route::post('delete-all', [AppWhatsAppBulkController::class, 'deleteAll'])->name('app.whatsappbulk.delete_all');
    });
});

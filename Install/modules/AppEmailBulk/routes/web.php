<?php

use Illuminate\Support\Facades\Route;
use Modules\AppEmailBulk\Http\Controllers\AppEmailBulkController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/email/bulk'], function () {
        Route::get('/', [AppEmailBulkController::class, 'index'])->name('app.emailbulk.index');
        Route::match(['get', 'post'], 'list', [AppEmailBulkController::class, 'list'])->name('app.emailbulk.list');
        Route::get('create', [AppEmailBulkController::class, 'update'])->name('app.emailbulk.create');
        Route::get('edit/{id_secure}', [AppEmailBulkController::class, 'update'])->name('app.emailbulk.edit');
        Route::post('save/{id_secure?}', [AppEmailBulkController::class, 'save'])->name('app.emailbulk.save');
        Route::post('status/{id_secure}', [AppEmailBulkController::class, 'status'])->name('app.emailbulk.status');
        Route::post('actions/{action}', [AppEmailBulkController::class, 'actions'])->name('app.emailbulk.actions');
        Route::post('delete/{id_secure?}', [AppEmailBulkController::class, 'delete'])->name('app.emailbulk.delete');
        Route::post('delete-all', [AppEmailBulkController::class, 'deleteAll'])->name('app.emailbulk.delete_all');
        Route::get('logs/{id_secure}', [AppEmailBulkController::class, 'logs'])->name('app.emailbulk.logs');
        Route::match(['get', 'post'], 'logs-list/{id_secure}', [AppEmailBulkController::class, 'logsList'])->name('app.emailbulk.logs_list');
        Route::post('process-now/{id_secure?}', [AppEmailBulkController::class, 'processNow'])->name('app.emailbulk.process_now');
        Route::post('upload-image', [AppEmailBulkController::class, 'uploadImage'])->name('app.emailbulk.upload_image');
    });

    Route::group(['prefix' => 'app/email'], function () {
        Route::get('send', [AppEmailBulkController::class, 'singleSendForm'])->name('app.emailsend.index');
        Route::post('send', [AppEmailBulkController::class, 'singleSendSubmit'])->name('app.emailsend.submit');
    });
});

// Public unsubscribe and secure cron endpoints
Route::get('email/unsubscribe/{id_secure}', [AppEmailBulkController::class, 'unsubscribe'])->name('email.unsubscribe');
Route::match(['get', 'post'], 'cron/email-campaigns', [AppEmailBulkController::class, 'cronRun'])->name('cron.email_campaigns');


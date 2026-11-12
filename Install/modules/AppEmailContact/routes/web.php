<?php

use Illuminate\Support\Facades\Route;
use Modules\AppEmailContact\Http\Controllers\AppEmailContactController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('app/email/contact')->group(function () {
        Route::get('/', [AppEmailContactController::class, 'index'])->name('app.emailcontact.index');
        Route::match(['get', 'post'], 'list', [AppEmailContactController::class, 'list'])->name('app.emailcontact.list');
        Route::post('status/{status}', [AppEmailContactController::class, 'status'])->name('app.emailcontact.status');
        Route::match(['get', 'post'], 'popup-update/{id_secure?}', [AppEmailContactController::class, 'popupUpdate'])->name('app.emailcontact.popup_update');
        Route::post('save/{id_secure?}', [AppEmailContactController::class, 'save'])->name('app.emailcontact.save');
        Route::post('delete/{id_secure?}', [AppEmailContactController::class, 'delete'])->name('app.emailcontact.delete');
        Route::get('emails/{id_secure}', [AppEmailContactController::class, 'emails'])->name('app.emailcontact.emails');
        Route::match(['get', 'post'], 'emails-list/{id_secure}', [AppEmailContactController::class, 'emailsList'])->name('app.emailcontact.emails_list');
        Route::match(['get', 'post'], 'popup-import-contact/{id_secure}', [AppEmailContactController::class, 'popupImportContact'])->name('app.emailcontact.popup_import_contact');
        Route::get('download-example-upload-csv', [AppEmailContactController::class, 'downloadExampleUploadCsv'])->name('app.emailcontact.download_example_upload_csv');
        Route::post('add-contact/{id_secure}', [AppEmailContactController::class, 'addContact'])->name('app.emailcontact.add_contact');
        Route::post('do-import-contact/{id_secure}', [AppEmailContactController::class, 'doImportContact'])->name('app.emailcontact.do_import_contact');
        Route::post('delete-email', [AppEmailContactController::class, 'deleteEmail'])->name('app.emailcontact.delete_email');
    });
});


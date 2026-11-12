<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppContact\Http\Controllers\AppWhatsAppContactController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('app/whatsapp/contact')->group(function () {
        Route::get('/', [AppWhatsAppContactController::class, 'index'])->name('app.whatsappcontact.index');
        Route::post('list', [AppWhatsAppContactController::class, 'list'])->name('app.whatsappcontact.list');
        Route::post('status/{status}', [AppWhatsAppContactController::class, 'status'])->name('app.whatsappcontact.status');
        Route::get('update/{id_secure?}', [AppWhatsAppContactController::class, 'update'])->name('app.whatsappcontact.update');
        Route::post('popup-update/{id_secure?}', [AppWhatsAppContactController::class, 'popupUpdate'])->name('app.whatsappcontact.popup_update');
        Route::post('save/{id_secure?}', [AppWhatsAppContactController::class, 'save'])->name('app.whatsappcontact.save');
        Route::post('delete/{id_secure?}', [AppWhatsAppContactController::class, 'delete'])->name('app.whatsappcontact.delete');
        Route::get('phone-numbers/{id_secure}', [AppWhatsAppContactController::class, 'phoneNumbers'])->name('app.whatsappcontact.phone_numbers');
        Route::post('phone-numbers-list/{id_secure}', [AppWhatsAppContactController::class, 'phoneNumbersList'])->name('app.whatsappcontact.phone_numbers_list');
        Route::post('popup-import-contact/{id_secure}', [AppWhatsAppContactController::class, 'popupImportContact'])->name('app.whatsappcontact.popup_import_contact');
        Route::get('download-example-upload-csv', [AppWhatsAppContactController::class, 'downloadExampleUploadCsv'])->name('app.whatsappcontact.download_example_upload_csv');
        Route::post('add-contact/{id_secure}', [AppWhatsAppContactController::class, 'addContact'])->name('app.whatsappcontact.add_contact');
        Route::post('do-import-contact/{id_secure}', [AppWhatsAppContactController::class, 'doImportContact'])->name('app.whatsappcontact.do_import_contact');
        Route::post('delete-phone', [AppWhatsAppContactController::class, 'deletePhone'])->name('app.whatsappcontact.delete_phone');
    });
});

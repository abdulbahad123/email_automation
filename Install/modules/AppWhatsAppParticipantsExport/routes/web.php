<?php

use Illuminate\Support\Facades\Route;
use Modules\AppWhatsAppParticipantsExport\Http\Controllers\AppWhatsAppParticipantsExportController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('app/whatsapp/export-participants')->group(function () {
        Route::get('/', [AppWhatsAppParticipantsExportController::class, 'index'])->name('app.whatsappparticipantsexport.index');
        Route::post('groups', [AppWhatsAppParticipantsExportController::class, 'groups'])->name('app.whatsappparticipantsexport.groups');
        Route::get('export/{account_id}/{group_id}', [AppWhatsAppParticipantsExportController::class, 'exportGroup'])->name('app.whatsappparticipantsexport.export');
        Route::match(['get', 'post'], 'import-popup/{account_id}/{group_id}', [AppWhatsAppParticipantsExportController::class, 'popupImport'])->name('app.whatsappparticipantsexport.popup_import');
        Route::post('import/{account_id}/{group_id}', [AppWhatsAppParticipantsExportController::class, 'importToContacts'])->name('app.whatsappparticipantsexport.import');
    });
});

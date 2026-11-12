<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\AppChannelWhatsappUnofficial\Http\Controllers\AppChannelWhatsappUnofficialController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "app"], function () {
        Route::group(["prefix" => "whatsapp_unofficial"], function () {
            Route::group(["prefix" => "profile"], function () {
                Route::resource('/', AppChannelWhatsappUnofficialController::class)->names('app.channelwhatsappunofficial');
                Route::get('oauth/{instance_id?}', [AppChannelWhatsappUnofficialController::class, 'oauth'])->name('app.channelwhatsappunofficial.oauth');
                Route::get('get-qrcode/{instance_id}', [AppChannelWhatsappUnofficialController::class, 'getQrcode'])->name('app.channelwhatsappunofficial.qrcode');
                Route::get('check-login/{instance_id}', [AppChannelWhatsappUnofficialController::class, 'checkLogin'])->name('app.channelwhatsappunofficial.check_login');
            });
        });
    });

    Route::group(["prefix" => "admin/api-integration"], function () {
        Route::get('whatsapp-unofficial', [AppChannelWhatsappUnofficialController::class, 'settings'])->name('app.channelwhatsappunofficial.settings');
    });
});

Route::middleware(['web'])->group(function () {
    Route::group(["prefix" => "app/whatsapp_unofficial/profile"], function () {
        Route::match(['get', 'post'], 'webhook/{instance_id}', [AppChannelWhatsappUnofficialController::class, 'webhook'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('app.channelwhatsappunofficial.webhook');
    });
});

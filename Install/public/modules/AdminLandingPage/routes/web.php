<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminLandingPage\Http\Controllers\AdminLandingPageController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "admin"], function () {
        Route::group(["prefix" => "frontend/landing-page"], function () {
            Route::get('/', [AdminLandingPageController::class, 'settings'])->name('admin.landing-page.settings');
            Route::post('save', [AdminLandingPageController::class, 'save'])->name('admin.landing-page.save');
        });
    });
});
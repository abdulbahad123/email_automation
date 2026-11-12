<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(["prefix" => "admin"], function () {

        Route::post('payment/configuration/razorpay', function (Request $request) {
            return response()->json([
                "status" => 1,
                "data" => view('paymentrazorpay::configuration')->render()
            ]);
        });

    });
});

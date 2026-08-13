<?php

use App\Http\Controllers\Api\PublicVoucherValidationController;
use App\Http\Controllers\Api\VoucherIssueController;
use App\Http\Controllers\Api\VoucherRedemptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('public/vouchers/{code}/validate', PublicVoucherValidationController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('vouchers/issue', VoucherIssueController::class);
        Route::post('vouchers/{code}/redeem', VoucherRedemptionController::class);
    });
});

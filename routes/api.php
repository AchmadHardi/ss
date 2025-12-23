<?php

use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\PointController;
use App\Http\Controllers\Customer\RegionController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Sales\SalesDashboardController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Customer\DowngradeUpgradeController;
use App\Http\Controllers\Customer\CustomerDashboardController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('user')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/verify-email/{token}', [EmailVerificationController::class, 'verify']);
    Route::post('/password/reset/send', [AuthController::class, 'sendResetPassword']);
    Route::get('/password/reset', [AuthController::class, 'showResetForm']);
    Route::post('/password/reset/submit', [AuthController::class, 'submitResetPassword']);

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/check-auth', [AuthController::class, 'checkAuth']);
    });
});

/*
|--------------------------------------------------------------------------
| SALES
|--------------------------------------------------------------------------
*/
Route::prefix('sales')
    ->middleware(['auth:api', 'sales'])
    ->group(function () {
        Route::get('/dashboard', [SalesDashboardController::class, 'dashboard']);
        Route::get('/contract-list', [SalesDashboardController::class, 'contractList']);
        Route::get('/status-customer', [SalesDashboardController::class, 'statusCustomer']);
    });

/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/
Route::prefix('customer')
    ->middleware(['auth:api', 'customer'])
    ->group(function () {
        Route::get('/dashboard', [CustomerDashboardController::class, 'dashboard']);

        Route::get('/billing/active', [CustomerDashboardController::class, 'billingActive']);
        Route::get('/billing/detail/{task_id}', [CustomerDashboardController::class, 'billingDetail']);
        Route::get('/notes', [CustomerDashboardController::class, 'statusNotes']);

        Route::get('/downgrade-upgrade/list', [DowngradeUpgradeController::class, 'list']);
        Route::post('/request-du', [DowngradeUpgradeController::class, 'requestDU']);
        Route::post('/sales/request-du', [DowngradeUpgradeController::class, 'salesRequestDU']);
        Route::get('/downgrade-upgrade/history', [DowngradeUpgradeController::class, 'history']);

        Route::get('/profile', [ProfileController::class, 'getProfile']);

        Route::get('/user/{user_id}/addresses', [ProfileController::class, 'getUserAddresses']);
        Route::post('/user/{user_id}/address/add', [ProfileController::class, 'addNewAddress']);
        Route::patch('/user/{user_id}/address/{address_id}/modify', [ProfileController::class, 'updateAddress']);
        Route::post('/user/delete', [ProfileController::class, 'deleteAccount']);

        Route::get('/payment-method', [PaymentController::class, 'paymentMethod']);
        Route::get('/payment', [PaymentController::class, 'paymentPage']);

        Route::get('/point/get-total', [PointController::class, 'getTotal']);
        Route::post('/point/add', [PointController::class, 'addPoint']);
        Route::post('/point/redeem', [PointController::class, 'redeem']);
        Route::get('/rewards/get-all', [PointController::class, 'getRewards']);
        Route::post('/point/ads-watch', [PointController::class, 'adsWatch']);
        Route::post('/point/presensi', [PointController::class, 'presence']);
        Route::get('/point/transaction', [PointController::class, 'transactions']);

        Route::get('/region/zip-code', [RegionController::class, 'zipCode']);
        Route::get('/region', [RegionController::class, 'region']);
        Route::get('/region/check-coverage', [RegionController::class, 'checkCoverage']);
        Route::get('/region/odp', [RegionController::class, 'odpList']);
        Route::get('/region/odp/nearest', [RegionController::class, 'nearestODP']);

        Route::get('/retail/entri-prospek/user/{task_id}', [CustomerController::class, 'getFormProspekEntry']);
        Route::post('/retail/entri-prospek', [CustomerController::class, 'customerEntriDataProspek']);
        Route::post('/idplay/entri-prospek', [CustomerController::class, 'idplayEntriDataProspek']);
        Route::post('/referral/entri-prospek', [CustomerController::class, 'referralEntriDataProspek']);
        Route::get('/referral/entri-prospek-web', [CustomerController::class, 'referralEntryWeb']);
        Route::get('/referral/after-submit', [CustomerController::class, 'referralAfterSubmit']);
        Route::get('/idmall-customer-activation', [CustomerController::class, 'getLeadCustomer']);
        Route::post('/idmall-push-lead-customer', [CustomerController::class, 'pushLeadCustomer']);

        Route::get('/fab/preview', [CustomerController::class, 'previewFAB']);
        Route::get('/fab/generate/{task_id}', [CustomerController::class, 'generateFAB']);
        Route::get('/fkb/generate-pdf/{task_id}', [CustomerController::class, 'generateFKB']);
        Route::post('/submit-fab/{task_id}', [CustomerController::class, 'submitFAB']);

        Route::post('/retail/fkb/user', [CustomerController::class, 'uploadKTP']);
        Route::post('/signature/upload/{task_id}', [CustomerController::class, 'uploadSignature']);
        Route::post('/upload-file', [CustomerController::class, 'uploadFABDocument']);
        Route::get('/terms-and-condition', [CustomerController::class, 'termsAndCondition']);
    });

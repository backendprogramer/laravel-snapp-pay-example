<?php

use App\Http\Controllers\PaymentGatewaySnappPayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('payment/success/{orderId}', [PaymentGatewaySnappPayController::class, 'paymentSuccess']);
Route::get('order/update/{orderId}', [PaymentGatewaySnappPayController::class, 'updateOrder']);
Route::get('order/cancel/{orderId}', [PaymentGatewaySnappPayController::class, 'cancelOrder']);

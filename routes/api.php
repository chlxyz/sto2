<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/otp/request', [OtpController::class, 'request']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::patch('/user', [ProfileController::class, 'update']);
    Route::get('/user/name-suggestions', [ProfileController::class, 'suggestions']);
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::patch('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/types', [ProductController::class, 'types']);
    Route::get('/sizes', [ProductController::class, 'sizes']);
    Route::get('/colors', [ProductController::class, 'colors']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::get('/cart/summary', [CartController::class, 'summary']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::post('/cart/checkout', [CartController::class, 'checkout']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    Route::get('/orders/{order}/payment-methods', [PaymentController::class, 'show']);
    Route::post('/orders/{order}/payment-methods', [PaymentController::class, 'pay']);
    Route::post('/orders/{order}/scan', [PaymentController::class, 'scan']);

    Route::middleware('admin')->group(function () {
        Route::get('/admin/products', [AdminController::class, 'products']);
        Route::post('/admin/products', [AdminController::class, 'storeProduct']);
        Route::patch('/admin/products/order', [AdminController::class, 'reorderProducts']);
        Route::patch('/admin/products/{id}', [AdminController::class, 'updateProduct']);
        Route::delete('/admin/products/{id}', [AdminController::class, 'destroyProduct']);
        Route::post('/admin/products/{id}/colors/{colorId}/image', [AdminController::class, 'setColorImage']);
        Route::delete('/admin/products/{id}/colors/{colorId}', [AdminController::class, 'detachColor']);
        Route::patch('/admin/products/{id}/images/order', [AdminController::class, 'reorderImages']);

        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser']);

        Route::get('/admin/orders', [AdminController::class, 'orders']);
        Route::get('/admin/vouchers', [AdminController::class, 'vouchers']);
        Route::post('/admin/vouchers', [AdminController::class, 'storeVoucher']);
        Route::patch('/admin/vouchers/{id}', [AdminController::class, 'updateVoucher']);
        Route::delete('/admin/vouchers/{id}', [AdminController::class, 'destroyVoucher']);

        Route::get('/admin/sizes', [AdminController::class, 'sizes']);
        Route::post('/admin/sizes', [AdminController::class, 'storeSize']);
        Route::delete('/admin/sizes/{id}', [AdminController::class, 'destroySize']);

        Route::get('/admin/settings', [AdminController::class, 'settings']);
        Route::patch('/admin/settings', [AdminController::class, 'updateSettings']);
    });
});

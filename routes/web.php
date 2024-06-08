<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GlobalDashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FavoriteController;

Route::get('/', function () {
    return view('auth.login');
});

// Login, Register, Logout, Update, Delete Account
Route::get('/login', [UserController::class, 'loginUi'])->name('loginForm');
Route::post('/login', [UserController::class, 'login'])->name('loginHandler');

Route::get('/register', [UserController::class, 'registerUi'])->name('registerForm');
Route::post('/register', [UserController::class, 'register'])->name('registerHandler');

Route::get('/profile/{id}', [UserController::class, 'showProfile'])->name('showProfile');
Route::get('/profile/{id}/edit', [UserController::class, 'editProfileUi'])->name('editProfileForm');
Route::post('profile/{id}/update', [UserController::class, 'editProfileHandler'])->name('editProfileHandling');

Route::post('/logout', [UserController::class, 'logout'])->name('logoutHandler');

Route::delete('/delete/{id}', [UserController::class, 'deleteAccount'])->name('deleteHandler');

// Global Dashboard
Route::get('/dashboard', [GlobalDashboardController::class, 'index'])->name('dashboard');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/', [CartController::class, 'addToCart'])->name('cart.add');
Route::delete('/cart/delete/{id}', [CartController::class, 'deleteItem'])->name('cart.deleteItem');
// Route::get('/favorite', [FavoriteController::class, 'index'])->name('favorite.index');
// Route::post('favorite/add/', [FavoriteController::class, 'addToFavorite'])->name('favorite.add');

Route::post('/cart/process', [CartController::class, 'processOrder'])->name('cart.process');

Route::post('/order', [OrderController::class, 'orderFromDashboard'])->name('order.fromdashboard');

// Order routes
Route::get('/orders', [OrderController::class, 'showOrders'])->name('order.show');
Route::delete('/orders/{order}', [OrderController::class, 'cancelOrders'])->name('order.cancel');


// Payment routes
Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/payment-methods', [PaymentController::class, 'showPaymentMethods'])->name('payments.methods');
    Route::post('/orders/{order}/payment-methods', [PaymentController::class, 'processPayment'])->name('payments.process');
    Route::get('/orders/{order}/pay/scanbank', [PaymentController::class, 'payWithScanBank'])->name('payments.scanbank');
    Route::get('/orders/{order}/pay/bank-transfer', [PaymentController::class, 'payWithBankTransfer'])->name('payments.bank_transfer');
    Route::get('/orders/{order}/pay/bank-transfer-terminal', [PaymentController::class, 'payWithBankTransferUI'])->name('payments.bank_transfer_terminal');
    Route::get('/orders/{order}/confirm-bank-transfer', [PaymentController::class, 'confirmBankTransferPayment'])->name('payments.confirm_bank_transfer');
    Route::get('/orders/{order}/pay/cod', [PaymentController::class, 'payWithCOD'])->name('payments.cod');
    Route::get('/orders/{order}/confirm-cod', [PaymentController::class, 'confirmCODPayment'])->name('payments.confirm_cod');
});


// Admin Dashboard & Products Control
Route::get('/admindashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/addproduct', [AdminDashboardController::class, 'addProductForm'])->name('add.productform');
Route::post('/addproduct', [AdminDashboardController::class, 'addProduct'])->name('admin.addproduct');

Route::get('/adminallusers', [AdminDashboardController::class, 'user_index'])->name('admin.allusers');
Route::post('/adminallusers', [AdminDashboardController::class, 'deleteUser'])->name('admin.deleteuser');

Route::get('/product/{id}/edit', [AdminDashboardController::class, 'editProductForm'])->name('edit.productform');
Route::post('/product/{id}/update', [AdminDashboardController::class, 'editProductHandler'])->name('admin.editproduct');

Route::delete('/deleteproduct/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.deleteproduct');
Route::get('/adminorders', [OrderController::class, 'showAdminOrders'])->name('order.adminshow');
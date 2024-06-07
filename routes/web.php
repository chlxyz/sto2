<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GlobalDashboardController;
use App\Http\Controllers\AdminDashboardController;


Route::get('/', function () {
    return view ('auth.login');
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

// Admin Dashboard & Products Control
Route::get('/admindashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/addproduct', [AdminDashboardController::class, 'addProductForm'])->name('add.productform');
Route::post('/addproduct', [AdminDashboardController::class, 'addProduct'])->name('admin.addproduct');

Route::get('/product/{id}/edit', [AdminDashboardController::class, 'editProductForm'])->name('edit.productform');
Route::post('/product/{id}/update', [AdminDashboardController::class, 'editProductHandler'])->name('admin.editproduct');

Route::delete('/deleteproduct/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.deleteproduct');
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\AdminAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Public Page Routes
Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/index.html', [PageController::class, 'index']);

Route::get('/registration', [PageController::class, 'registration'])->name('registration');
Route::get('/registration.html', [PageController::class, 'registration']);

Route::get('/reset-password', [PageController::class, 'resetPassword'])->name('password.reset');
Route::get('/reset_password.html', [PageController::class, 'resetPassword']);

// Guest Portal Routes
Route::get('/dashboard', [PageController::class, 'userDashboard'])->name('user.dashboard');
Route::get('/dashboard.html', [PageController::class, 'userDashboard']);
Route::get('/user/dashboard', [PageController::class, 'userDashboard']);
Route::get('/user/dashboard.html', [PageController::class, 'userDashboard']);

// Admin Portal Routes
Route::get('/admin', function () {
    return redirect()->route('admin.dashboard');
});
Route::get('/admin/dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');
Route::get('/admin/dashboard.php', [PageController::class, 'adminDashboard']);

// Staff Portal Routes
Route::get('/staff/dashboard', [PageController::class, 'staffDashboard'])->name('staff.dashboard');
Route::get('/admin/staff_dashboard.php', [PageController::class, 'staffDashboard']);

// Authentication Actions (Session Logout Handlers)
Route::match(['GET', 'POST'], '/user/logout.php', [UserAuthController::class, 'logout'])->name('user.logout');
Route::match(['GET', 'POST'], '/user/logout', [UserAuthController::class, 'logout']);
Route::match(['GET', 'POST'], '/admin/logout.php', [AdminAuthController::class, 'logout'])->name('admin.logout');
Route::match(['GET', 'POST'], '/admin/logout', [AdminAuthController::class, 'logout']);
Route::match(['GET', 'POST'], '/admin/staff_logout.php', [AdminAuthController::class, 'logout'])->name('staff.logout');

// PayMongo Callback & Success Routes
Route::match(['GET', 'POST'], '/user/payment_success.php', function () {
    require base_path('user/payment_success.php');
});
Route::match(['GET', 'POST'], '/user/payment_failed.php', function () {
    require base_path('user/payment_failed.php');
});
Route::match(['GET', 'POST'], '/user/paymongo_webhook.php', function () {
    require base_path('user/paymongo_webhook.php');
});

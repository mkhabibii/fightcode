<?php

use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminTestimoniController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TestimoniController;
use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');


//todo Group remember url middleware setelah login
Route::middleware(['remember.url'])->group(function(){
    Route::view('/lainnya', 'lainnya')->name('lainnya');
    Route::view('/dampak', 'dampak')->name('dampak');
    Route::get('/learningpath', [CourseController::class, 'index'])->name('Learning Path');
    Route::get('/learningpath/{id}', [CourseController::class, 'show'])->name('Learning Path Detail');
});


//! Login dan Regis

//todo grup page login dulu baru bisa diakses
Route::middleware(['auth'])->group(function() {
    // Profil
     Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
     Route::post('/profile/upload', [ProfileController::class, 'upload'])->name('profile.upload');
     Route::delete('/profile/hapus-foto', [ProfileController::class, 'delete'])->name('profile.delete');
     Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

     // Settings
     Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
     Route::post('/settings/password', [SettingsController::class, 'changePass'])->name('change.password');
 
     //course
     Route::post('/checkout/{id}', [PurchaseController::class, 'store'])->name('checkout');
     Route::get('/my-course', [PurchaseController::class, 'myCourse'])->name('my-course');
     Route::get('/my-course/{id}/learn', [PurchaseController::class, 'learn'])->name('my-course.learn');

     //testimoni
     Route::post('/testimoni', [TestimoniController::class, 'store'])->name('testimoni.store');
    

});


//! Admin
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function() {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/export/excel', [AdminUserController::class, 'exportExcel'])->name('users.export.excel');

    Route::get('/testimonials', [AdminTestimoniController::class, 'index'])->name('testimonials.index');
    Route::patch('/testimonials/{id}/approve', [AdminTestimoniController::class, 'approve'])->name('testimonials.approve');
    Route::delete('/testimonials/{id}', [AdminTestimoniController::class, 'destroy'])->name('testimonials.destroy');

    Route::resource('courses', AdminCourseController::class);
});




//todo login auth dengan google
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'googleCallback']);


Route::get('/register', [RegisterController::class, 'show'])->name('register'); // manggil method show di RegisteController
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Webhook Notification (Harus bisa diakses publik oleh Midtrans)
Route::post('/payment/callback', [PaymentCallbackController::class, 'handle'])->name('payment.callback');


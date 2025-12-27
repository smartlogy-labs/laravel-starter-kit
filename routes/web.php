<?php

use App\Http\Controllers\Admin\TaskCategoryController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'doLogin'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Users Routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('_admin.dashboard');
    })->name('dashboard');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/add', [UserController::class, 'add'])->name('add');
        Route::post('/create', [UserController::class, 'doCreate'])->name('create');
        Route::get('/detail/{id}', [UserController::class, 'detail'])->name('detail');
        Route::get('/update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('/update/{id}', [UserController::class, 'doUpdate'])->name('doUpdate');
        Route::delete('/delete/{id}', [UserController::class, 'delete'])->name('delete');
        Route::post('/reset-password/{id}', [UserController::class, 'resetPassword'])->name('resetPassword');
    });

    Route::prefix('task-categories')->name('task_categories.')->group(function () {
        Route::get('/', [TaskCategoryController::class, 'index'])->name('index');
        Route::get('/add', [TaskCategoryController::class, 'add'])->name('add');
        Route::post('/create', [TaskCategoryController::class, 'doCreate'])->name('create');
        Route::get('/update/{id}', [TaskCategoryController::class, 'update'])->name('update');
        Route::post('/update/{id}', [TaskCategoryController::class, 'doUpdate'])->name('doUpdate');
        Route::delete('/delete/{id}', [TaskCategoryController::class, 'delete'])->name('delete');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/add', [TaskController::class, 'add'])->name('add');
        Route::post('/create', [TaskController::class, 'doCreate'])->name('do_create');
        Route::get('/update/{id}', [TaskController::class, 'update'])->name('update');
        Route::post('/update/{id}', [TaskController::class, 'doUpdate'])->name('do_update');
        Route::delete('/delete/{id}', [TaskController::class, 'delete'])->name('delete');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/change-password', [UserController::class, 'changePassword'])->name('change_password');
        Route::post('/change-password', [UserController::class, 'doChangePassword'])->name('do_change_password');
    });
});

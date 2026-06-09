<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Post routes — authenticated routes first so /posts/create is registered before /posts/{post}
Route::middleware('auth')->group(function () {
    Route::resource('posts', PostController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
});

Route::resource('posts', PostController::class)
    ->only(['index', 'show']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

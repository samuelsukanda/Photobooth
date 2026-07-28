<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Photobooth routes
Route::post('/photos', [\App\Http\Controllers\PhotoController::class, 'store']);
Route::get('/photos', [\App\Http\Controllers\PhotoController::class, 'index']);
Route::get('/events/{slug}', [\App\Http\Controllers\EventController::class, 'show']);
Route::post('/guests', [\App\Http\Controllers\GuestController::class, 'store']);

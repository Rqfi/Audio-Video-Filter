<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AudioFilterController;

Route::get('/', [AudioFilterController::class, 'index']);
Route::post('/', [AudioFilterController::class, 'process']);
Route::post('/delete', [AudioFilterController::class, 'delete']);
Route::post('/delete-all', [AudioFilterController::class, 'deleteAll']);
Route::post('/download-all', [AudioFilterController::class, 'downloadAll']);

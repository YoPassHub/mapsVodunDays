<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VodunDaysController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/vodun-days', [VodunDaysController::class, 'index'])->name('vodun-days');

// Version simplifiée avec le composant réutilisable
Route::get('/vodun-days-simple', [VodunDaysController::class, 'simple'])->name('vodun-days-simple');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VodunDaysController;

Route::get('/', function () {
    return view('welcome');
});

// Carte Google Maps des événements Vodun Days
Route::get('/vodun-days', [VodunDaysController::class, 'index'])->name('vodun-days');

// Vider le cache des événements
Route::get('/clear-cache', function () {
    \Cache::flush();
    return redirect()->back()->with('success', '✅ Cache vidé avec succès !');
})->name('clear-cache');

// Route de test de l'API (pour déboguer)
require __DIR__.'/api-test.php';

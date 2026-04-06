<?php

use App\Http\Controllers\ListaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('listas.index');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('listas', ListaController::class)->except(['show']);
    Route::post('/listas/{lista}/finalizar', [ListaController::class, 'finalizar'])
        ->name('listas.finalizar');
    Route::get('/listas/{lista}/recomendacion', [ListaController::class, 'recomendacion'])
        ->name('listas.recomendacion');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

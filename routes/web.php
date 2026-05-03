<?php

use App\Http\Controllers\DespensaController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupermercadoController;
use App\Http\Controllers\VendenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('listas.index');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('listas', ListaController::class)->except(['show']);
    Route::resource('despensas', DespensaController::class)->except(['show']);
    Route::resource('supermercados', SupermercadoController::class)->only(['index']);
    Route::resource('productos', ProductoController::class)->only(['index']);
    Route::get('precios', [VendenController::class, 'index'])->name('precios.index');
    Route::get('/despensas/{despensa}/stock', [DespensaController::class, 'stock'])
        ->name('despensas.stock');
    Route::post('/despensas/{despensa}/stock', [DespensaController::class, 'agregarProducto'])
        ->name('despensas.stock.agregar');
    Route::patch('/despensas/{despensa}/stock/{producto}', [DespensaController::class, 'actualizarStock'])
        ->name('despensas.stock.actualizar');
    Route::delete('/despensas/{despensa}/stock/{producto}', [DespensaController::class, 'quitarProducto'])
        ->name('despensas.stock.quitar');
    Route::post('/listas/{lista}/finalizar', [ListaController::class, 'finalizar'])
        ->name('listas.finalizar');
    Route::get('/listas/{lista}/recomendacion', [ListaController::class, 'recomendacion'])
        ->name('listas.recomendacion');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('supermercados', SupermercadoController::class)
        ->except(['index', 'show']);
    Route::resource('productos', ProductoController::class)
        ->except(['index', 'show']);
    Route::get('precios/create', [VendenController::class, 'create'])->name('precios.create');
    Route::post('precios', [VendenController::class, 'store'])->name('precios.store');
    Route::get('precios/{producto}/{supermercado}/edit', [VendenController::class, 'edit'])->name('precios.edit');
    Route::put('precios/{producto}/{supermercado}', [VendenController::class, 'update'])->name('precios.update');
    Route::delete('precios/{producto}/{supermercado}', [VendenController::class, 'destroy'])->name('precios.destroy');
});

require __DIR__.'/auth.php';

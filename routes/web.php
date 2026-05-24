<?php

use App\Http\Controllers\DespensaController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\PaginaPublicaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoExternoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupermercadoController;
use App\Http\Controllers\VendenController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard')->name('dashboard');
Route::redirect('/dashboard', '/');

Route::get('supermercados', [SupermercadoController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('supermercados.index');
Route::get('precios', [VendenController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('precios.index');
Route::get('aviso-legal', [PaginaPublicaController::class, 'avisoLegal'])
    ->name('aviso-legal');
Route::get('privacidad', [PaginaPublicaController::class, 'privacidad'])
    ->name('privacidad');
Route::get('contacto', [PaginaPublicaController::class, 'contacto'])
    ->name('contacto');
Route::post('contacto', [PaginaPublicaController::class, 'enviarContacto'])
    ->middleware('throttle:10,1')
    ->name('contacto.enviar');

Route::middleware('auth')->group(function () {
    Route::resource('listas', ListaController::class)->except(['create']);
    Route::resource('despensas', DespensaController::class)->except(['show', 'create']);
    Route::get('productos', [ProductoController::class, 'index'])
        ->name('productos.index')
        ->middleware('admin');
    Route::get('/despensas/{despensa}/stock', [DespensaController::class, 'stock'])
        ->name('despensas.stock');
    Route::get('/despensas/{despensa}/stock/sugerencias', [DespensaController::class, 'sugerenciasStock'])
        ->name('despensas.stock.sugerencias');
    Route::get('/despensas/{despensa}/stock/catalogo-sugerencias', [DespensaController::class, 'sugerenciasCatalogoProductos'])
        ->name('despensas.stock.catalogo-sugerencias');
    Route::post('/despensas/{despensa}/stock', [DespensaController::class, 'agregarProducto'])
        ->name('despensas.stock.agregar');
    Route::patch('/despensas/{despensa}/stock/{producto}', [DespensaController::class, 'actualizarStock'])
        ->name('despensas.stock.actualizar');
    Route::delete('/despensas/{despensa}/stock/{producto}', [DespensaController::class, 'quitarProducto'])
        ->name('despensas.stock.quitar');
    Route::get('/listas/{lista}/productos', [ListaController::class, 'productos'])
        ->name('listas.productos');
    Route::get('/listas/{lista}/productos/sugerencias', [ListaController::class, 'sugerenciasProductos'])
        ->name('listas.productos.sugerencias');
    Route::post('/listas/{lista}/productos', [ListaController::class, 'agregarProducto'])
        ->name('listas.productos.agregar');
    Route::patch('/listas/{lista}/productos/{producto}', [ListaController::class, 'actualizarProducto'])
        ->name('listas.productos.actualizar');
    Route::delete('/listas/{lista}/productos/{producto}', [ListaController::class, 'quitarProducto'])
        ->name('listas.productos.quitar');
    Route::get('/listas/{lista}/finalizar', [ListaController::class, 'confirmarFinalizacion'])
        ->name('listas.finalizar.confirmar');
    Route::post('/listas/{lista}/finalizar', [ListaController::class, 'finalizar'])
        ->name('listas.finalizar');
    Route::get('/listas/{lista}/recomendacion', [ListaController::class, 'recomendacion'])
        ->name('listas.recomendacion');
    Route::post('/listas/{lista}/recomendacion/elegir', [ListaController::class, 'elegirSupermercado'])
        ->name('listas.recomendacion.elegir');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/location', [ProfileController::class, 'updateLocation'])->name('profile.location.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminPanelController::class, 'index'])->name('index');
    Route::get('supermercados', [AdminPanelController::class, 'redirectToSupermercadosTab'])->name('supermercados.index');
    Route::get('supermercados/create', [SupermercadoController::class, 'create'])->name('supermercados.create');
    Route::get('supermercados/{supermercado}/edit', [SupermercadoController::class, 'edit'])->name('supermercados.edit');
    Route::post('supermercados', [SupermercadoController::class, 'store'])->name('supermercados.store');
    Route::put('supermercados/{supermercado}', [SupermercadoController::class, 'update'])->name('supermercados.update');
    Route::delete('supermercados/{supermercado}', [SupermercadoController::class, 'destroy'])->name('supermercados.destroy');
    Route::patch('supermercados/{supermercado}/activo', [SupermercadoController::class, 'toggleActivo'])->name('supermercados.toggle');
    Route::patch('cadenas-supermercados/{cadenaSupermercado}/activo', [SupermercadoController::class, 'toggleCadenaActivo'])->name('cadenas-supermercados.toggle');
    Route::get('productos', [AdminPanelController::class, 'redirectToProductosTab'])->name('productos.index');
    Route::get('productos/create', [ProductoController::class, 'create'])->name('productos.create');
    Route::get('productos/{producto}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::post('productos', [ProductoController::class, 'store'])->name('productos.store');
    Route::put('productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
    Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    Route::get('usuarios', [AdminPanelController::class, 'redirectToUsuariosTab'])->name('usuarios.index');
    Route::post('usuarios', [AdminUserController::class, 'store'])->name('usuarios.store');
    Route::delete('usuarios/{user}', [AdminUserController::class, 'destroy'])->name('usuarios.destroy');
    Route::get('productos-externos', [ProductoExternoController::class, 'index'])
        ->name('productos-externos.index');
    Route::post('productos-externos/{productoExterno}/confirmar', [ProductoExternoController::class, 'confirmar'])
        ->name('productos-externos.confirmar');
    Route::post('productos-externos/{productoExterno}/crear-producto', [ProductoExternoController::class, 'store'])
        ->name('productos-externos.store');
    Route::post('productos-externos/{productoExterno}/descartar', [ProductoExternoController::class, 'descartar'])
        ->name('productos-externos.descartar');
    Route::get('precios/create', [VendenController::class, 'create'])->name('precios.create');
    Route::post('precios', [VendenController::class, 'store'])->name('precios.store');
    Route::get('precios/{producto}/{supermercado}/edit', [VendenController::class, 'edit'])->name('precios.edit');
    Route::put('precios/{producto}/{supermercado}', [VendenController::class, 'update'])->name('precios.update');
    Route::delete('precios/{producto}/{supermercado}', [VendenController::class, 'destroy'])->name('precios.destroy');
});

require __DIR__.'/auth.php';

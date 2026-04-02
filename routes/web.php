<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListaController;

Route::get('/', function () {
    return redirect()->route('listas.index');
});

Route::resource('listas', ListaController::class)->except(['show']);

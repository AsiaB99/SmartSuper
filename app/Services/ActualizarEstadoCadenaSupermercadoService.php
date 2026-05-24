<?php

namespace App\Services;

use App\Models\CadenaSupermercado;

class ActualizarEstadoCadenaSupermercadoService
{
    public function actualizar(CadenaSupermercado $cadena, bool $activo): int
    {
        return $cadena->supermercados()->update([
            'activo' => $activo,
        ]);
    }
}

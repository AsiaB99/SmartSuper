<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PrecioCadena extends Pivot
{
    protected $table = 'precios_cadena';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'precio_unidad' => 'decimal:2',
            'fecha_actualizacion' => 'datetime',
        ];
    }
}

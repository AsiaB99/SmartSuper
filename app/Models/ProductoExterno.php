<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoExterno extends Model
{
    protected $table = 'productos_externos';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'precio_anterior' => 'decimal:2',
            'precio_unidad' => 'decimal:2',
            'disponible' => 'boolean',
            'payload' => 'array',
            'fecha_importacion' => 'datetime',
        ];
    }
}

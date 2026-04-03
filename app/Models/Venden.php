<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Venden extends Pivot
{
    protected $table = 'venden';

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

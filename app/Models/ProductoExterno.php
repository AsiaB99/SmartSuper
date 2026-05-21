<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoExterno extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_SUGERIDO = 'sugerido';

    public const ESTADO_MAPEADO = 'mapeado';

    public const ESTADO_DESCARTADO = 'descartado';

    protected $table = 'productos_externos';

    public $timestamps = false;

    protected $guarded = [];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'precio_anterior' => 'decimal:2',
            'precio_unidad' => 'decimal:2',
            'disponible' => 'boolean',
            'payload' => 'array',
            'fecha_importacion' => 'datetime',
            'sugerencia_snapshot' => 'array',
        ];
    }
}

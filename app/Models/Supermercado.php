<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supermercado extends Model
{
    use HasFactory;

    protected $table = 'supermercados';

    public $timestamps = false;

    protected $fillable = [
        'nombre_super',
        'direccion',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
        ];
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'venden', 'id_super', 'id_producto')
            ->using(Venden::class)
            ->withPivot([
                'precio',
                'precio_unidad',
                'unidad_ref',
                'moneda',
                'fuente_precio',
                'url_origen',
                'fecha_precio',
                'fecha_actualizacion',
            ]);
    }
}

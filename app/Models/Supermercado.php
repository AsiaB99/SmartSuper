<?php

namespace App\Models;

use App\Support\TextEncoding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supermercado extends Model
{
    use HasFactory;

    protected $table = 'supermercados';

    public $timestamps = false;

    protected $fillable = [
        'id_cadena',
        'nombre_super',
        'direccion',
        'latitud',
        'longitud',
        'fuente',
        'external_id',
        'osm_type',
        'marca',
        'operador',
        'activo',
        'ultima_vista_en',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
            'activo' => 'boolean',
            'ultima_vista_en' => 'datetime',
        ];
    }

    public function cadena(): BelongsTo
    {
        return $this->belongsTo(CadenaSupermercado::class, 'id_cadena');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'venden', 'id_super', 'id_producto')
            ->using(Venden::class)
            ->withPivot(['precio', 'precio_unidad', 'unidad_ref', 'fecha_actualizacion']);
    }

    protected function nombreSuper(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => TextEncoding::fixMojibake($value),
            set: static fn (?string $value): ?string => TextEncoding::fixMojibake($value),
        );
    }

    protected function direccion(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => TextEncoding::fixMojibake($value),
            set: static fn (?string $value): ?string => TextEncoding::fixMojibake($value),
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    public $timestamps = false;

    protected $fillable = [
        'id_seccion',
        'nombre_producto',
        'marca',
        'formato',
        'imagen',
    ];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'id_seccion');
    }

    public function listas(): BelongsToMany
    {
        return $this->belongsToMany(Lista::class, 'formadas', 'id_producto', 'id_lista')
            ->using(Formadas::class)
            ->withPivot(['cantidad', 'marcado']);
    }

    public function despensas(): BelongsToMany
    {
        return $this->belongsToMany(Despensa::class, 'almacena', 'id_producto', 'id_despensa')
            ->using(Almacena::class)
            ->withPivot('stock');
    }

    public function supermercados(): BelongsToMany
    {
        return $this->belongsToMany(Supermercado::class, 'venden', 'id_producto', 'id_super')
            ->using(Venden::class)
            ->withPivot(['precio', 'precio_unidad', 'unidad_ref', 'fecha_actualizacion']);
    }

    public function productosExternos(): HasMany
    {
        return $this->hasMany(ProductoExterno::class);
    }
}

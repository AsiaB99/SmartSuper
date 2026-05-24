<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lista extends Model
{
    use HasFactory;

    protected $table = 'listas';

    public $timestamps = false;

    protected $fillable = [
        'nombre_lista',
        'estado',
        'id_supermercado_elegido',
        'supermercados_recomendados_snapshot',
        'fecha_creacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
            'supermercados_recomendados_snapshot' => 'array',
        ];
    }

    public function supermercadoElegido(): BelongsTo
    {
        return $this->belongsTo(Supermercado::class, 'id_supermercado_elegido');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hacen', 'id_lista', 'id_usuario')
            ->using(Hacen::class)
            ->withPivot('permiso_lista');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'formadas', 'id_lista', 'id_producto')
            ->using(Formadas::class)
            ->withPivot(['cantidad', 'marcado']);
    }
}

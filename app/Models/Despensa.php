<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Despensa extends Model
{
    use HasFactory;

    protected $table = 'despensas';

    public $timestamps = false;

    protected $fillable = [
        'nombre_despensa',
        'fecha_creacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tienen', 'id_despensa', 'id_usuario')
            ->using(Tienen::class)
            ->withPivot('permiso_despensa');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'almacena', 'id_despensa', 'id_producto')
            ->using(Almacena::class)
            ->withPivot('stock');
    }
}

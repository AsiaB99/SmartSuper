<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CadenaSupermercado extends Model
{
    use HasFactory;

    protected $table = 'cadenas_supermercados';

    protected $fillable = [
        'nombre',
        'nombre_normalizado',
    ];

    public function supermercados(): HasMany
    {
        return $this->hasMany(Supermercado::class, 'id_cadena');
    }
}

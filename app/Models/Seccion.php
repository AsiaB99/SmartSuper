<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seccion extends Model
{
    use HasFactory;

    protected $table = 'secciones';

    public $timestamps = false;

    protected $fillable = [
        'nombre_seccion',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_seccion');
    }
}

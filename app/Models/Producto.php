<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Producto extends Model
{
    use HasFactory;

    public const ORIGEN_MANUAL = 'manual';

    public const ORIGEN_EXTERNO = 'externo';

    public const ORIGEN_DEMO = 'demo';

    protected $table = 'productos';

    public $timestamps = false;

    protected $fillable = [
        'id_seccion',
        'nombre_producto',
        'marca',
        'formato',
        'imagen',
        'origen_catalogo',
    ];

    public function getNombreCanonicoAttribute(): string
    {
        return trim((string) $this->nombre_producto);
    }

    public function getMarcaCanonicaAttribute(): ?string
    {
        return $this->normalizarCampoVisible($this->marca);
    }

    public function getFormatoCanonicoAttribute(): ?string
    {
        return $this->normalizarCampoVisible($this->formato);
    }

    public function getImagenCanonicaAttribute(): ?string
    {
        return $this->normalizarCampoVisible($this->imagen);
    }

    public function getDescripcionCanonicaAttribute(): ?string
    {
        $descripcion = $this->metadatosCanonicos()
            ->implode(' · ');

        return $descripcion !== '' ? $descripcion : null;
    }

    /**
     * @return Collection<int, string>
     */
    public function metadatosCanonicos(): Collection
    {
        return collect([$this->marca_canonica, $this->formato_canonico])->filter();
    }

    public function scopePublicables(Builder $query): Builder
    {
        return $query->where('origen_catalogo', '!=', self::ORIGEN_DEMO);
    }

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

    public function cadenasSupermercados(): BelongsToMany
    {
        return $this->belongsToMany(CadenaSupermercado::class, 'precios_cadena', 'id_producto', 'id_cadena')
            ->using(PrecioCadena::class)
            ->withPivot(['precio', 'precio_unidad', 'unidad_ref', 'fecha_actualizacion']);
    }

    public function productosExternos(): HasMany
    {
        return $this->hasMany(ProductoExterno::class);
    }

    private function normalizarCampoVisible(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : null;
    }
}

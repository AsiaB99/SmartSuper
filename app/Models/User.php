<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nombre_usuario',
        'email',
        'password',
        'rol',
        'latitud',
        'longitud',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
        ];
    }

    public function listas(): BelongsToMany
    {
        return $this->belongsToMany(Lista::class, 'hacen', 'id_usuario', 'id_lista')
            ->using(Hacen::class)
            ->withPivot('permiso_lista');
    }

    public function despensas(): BelongsToMany
    {
        return $this->belongsToMany(Despensa::class, 'tienen', 'id_usuario', 'id_despensa')
            ->using(Tienen::class)
            ->withPivot('permiso_despensa');
    }

    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function permisoEnLista(Lista $lista): ?string
    {
        if ($this->relationLoaded('listas')) {
            /** @var Collection<int, Lista> $listas */
            $listas = $this->getRelation('listas');
            $listaRelacionada = $listas->firstWhere('id', $lista->id);

            return $listaRelacionada?->pivot?->permiso_lista;
        }

        $listaRelacionada = $this->listas()
            ->where('listas.id', $lista->id)
            ->first();

        return $listaRelacionada?->pivot?->permiso_lista;
    }

    public function permisoEnDespensa(Despensa $despensa): ?string
    {
        if ($this->relationLoaded('despensas')) {
            /** @var Collection<int, Despensa> $despensas */
            $despensas = $this->getRelation('despensas');
            $despensaRelacionada = $despensas->firstWhere('id', $despensa->id);

            return $despensaRelacionada?->pivot?->permiso_despensa;
        }

        $despensaRelacionada = $this->despensas()
            ->where('despensas.id', $despensa->id)
            ->first();

        return $despensaRelacionada?->pivot?->permiso_despensa;
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Almacena extends Pivot
{
    protected $table = 'almacena';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Formadas extends Pivot
{
    protected $table = 'formadas';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'marcado' => 'boolean',
        ];
    }
}

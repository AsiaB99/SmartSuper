<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Hacen extends Pivot
{
    protected $table = 'hacen';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];
}

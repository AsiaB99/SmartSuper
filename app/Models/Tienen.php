<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Tienen extends Pivot
{
    protected $table = 'tienen';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];
}

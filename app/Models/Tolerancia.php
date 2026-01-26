<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tolerancia extends Model
{
    use \App\Models\Traits\TeamOwned;

    protected $fillable = ['user_id', 'team_id', 'monto', 'dias'];

    protected $casts = [
        'monto' => 'decimal:2',
        'dias' => 'integer',
    ];
}

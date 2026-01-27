<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conciliacion extends Model
{
    // use \App\Models\Traits\UserOwned; // Removed to allow Team-wide visibility

    protected $fillable = [
        'user_id',
        'factura_id',
        'movimiento_id',
        'monto_aplicado',
        'estatus',
        'tipo',
        'fecha_conciliacion',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
        'fecha_conciliacion' => 'datetime',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class);
    }
}

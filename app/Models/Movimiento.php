<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use \App\Models\Traits\HasCreator;
    use \App\Models\Traits\TeamOwned;

    protected $fillable = [
        'user_id',
        'team_id',
        'banco_id',
        'file_id',
        'fecha',
        'monto',
        'tipo',
        'referencia',
        'descripcion',
        'hash'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }

    public function archivo()
    {
        return $this->belongsTo(Archivo::class, 'file_id');
    }

    public function conciliaciones()
    {
        return $this->hasMany(Conciliacion::class);
    }
}

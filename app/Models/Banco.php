<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    // use \App\Models\Traits\TeamOwned; // Banks are global reference data
    protected $fillable = ['nombre', 'codigo', 'estatus', 'team_id'];

    public function archivos()
    {
        return $this->hasMany(Archivo::class);
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }
}

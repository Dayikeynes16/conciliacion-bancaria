<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use \App\Models\Traits\HasCreator;
    use \App\Models\Traits\TeamOwned;

    protected $fillable = ['user_id', 'team_id', 'banco_id', 'path', 'mime', 'size', 'checksum', 'estatus'];

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }

    public function factura()
    {
        return $this->hasOne(Factura::class, 'file_id_xml');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'file_id');
    }
}

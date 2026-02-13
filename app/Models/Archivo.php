<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use \App\Models\Traits\HasCreator;
    use \App\Models\Traits\TeamOwned;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['user_id', 'team_id', 'banco_id', 'bank_format_id', 'path', 'original_name', 'mime', 'size', 'checksum', 'estatus'];

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }

    public function bankFormat()
    {
        return $this->belongsTo(BankFormat::class);
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

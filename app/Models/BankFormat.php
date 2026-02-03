<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankFormat extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'start_row',
        'date_column',
        'description_column',
        'amount_column',
        'reference_column',
        'type_column',
        'color',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

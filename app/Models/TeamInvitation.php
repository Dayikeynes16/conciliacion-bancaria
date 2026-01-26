<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'role',
        'token',
    ];

    /**
     * Get the team that the invitation belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

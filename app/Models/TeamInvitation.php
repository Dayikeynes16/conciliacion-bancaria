<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'role',
    ];

    protected static function booted(): void
    {
        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(32);
            }
        });
    }

    /**
     * Get the team that the invitation belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}

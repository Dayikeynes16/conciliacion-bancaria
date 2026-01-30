<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait TeamOwned
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootTeamOwned(): void
    {
        // Automatically scope queries to the current team
        static::addGlobalScope('team', function (Builder $builder) {
            if (Auth::check() && Auth::user()->current_team_id) {
                $builder->where($builder->getModel()->getTable().'.team_id', Auth::user()->current_team_id);
            }
        });

        // Automatically set team_id when creating models
        static::creating(function ($model) {
            if (Auth::check() && ! isset($model->team_id) && Auth::user()->current_team_id) {
                $model->team_id = Auth::user()->current_team_id;
            }
        });
    }

    /**
     * Get the team that owns the model.
     */
    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}

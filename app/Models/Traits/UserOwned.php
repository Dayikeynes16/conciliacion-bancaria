<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait UserOwned
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootUserOwned(): void
    {
        // Automatically scope queries to the current user
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('user_id', Auth::id());
            }
        });

        // Automatically set user_id when creating models
        static::creating(function ($model) {
            if (Auth::check() && ! isset($model->user_id)) {
                $model->user_id = Auth::id();
            }
        });
    }

    /**
     * Get the user that owns the model.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

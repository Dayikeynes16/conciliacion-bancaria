<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasCreator
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootHasCreator(): void
    {
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
        return $this->belongsTo(User::class);
    }
}

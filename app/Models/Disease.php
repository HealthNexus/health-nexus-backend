<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Disease extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'disease_user', 'disease_id', 'user_id')->withTimestamps();
    }
}

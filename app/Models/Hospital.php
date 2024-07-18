<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function patients(): HasMany
    {
        return $this->hasMany(User::class, 'hospital_id');
    }
}

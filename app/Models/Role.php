<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;
    protected $guarded = [];

    //define the relationship between the Role and User models
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

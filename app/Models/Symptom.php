<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Symptom extends Model
{
    use HasFactory;

    protected $guarded = [];


    //Symptom and Disease relationship
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class)->withTimestamps();
    }
}

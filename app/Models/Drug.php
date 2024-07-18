<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Drug extends Model
{
    use HasFactory;

    protected $guarded = [];

    //Disease and Drug relationship
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class)->withTimestamps();
    }

    //Drug and DrugCategory relationship
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DrugCategory::class)->withTimestamps();
    }
}

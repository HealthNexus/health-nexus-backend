<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DrugCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    //Drug and DrugCategory relationship
    public function drugs(): BelongsToMany
    {
        return $this->belongsToMany(Drug::class)->withTimestamps();
    }
}

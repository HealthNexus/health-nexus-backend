<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    protected $guarded = [];

    //Category and Post relationship
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    //Disease and Category relationship
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'category_disease', 'category_id', 'disease_id')->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disease extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['categories', 'drugs', 'symptoms'];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'disease_user', 'disease_id', 'user_id')->as('record')->withTimestamps()->withPivot('id');
    }

    //Disease and Category relationship
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_disease', 'disease_id', 'category_id')->withTimestamps();
    }

    //Disease and Drug relationship
    public function drugs(): BelongsToMany
    {
        return $this->belongsToMany(Drug::class)->withTimestamps();
    }

    //Symptom and Disease relationship
    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class)->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

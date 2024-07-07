<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function replies(){
        return $this->hasMany(Reply::class);
    }

    public function post(){
        return $this->belongsTo(Post::class);
    }
}

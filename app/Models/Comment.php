<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $with = ['writer', 'replies'];

    //relationship between commentss and replies
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    //relationship between comments and post
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    //relationship between comments and user
    public function writer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

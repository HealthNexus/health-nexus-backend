<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reply extends Model
{
    use HasFactory;
    protected $guarded = [];

    //comment reply relationship
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    //reply writer relationship
    public function writer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

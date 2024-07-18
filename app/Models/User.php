<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //relationship between user and hospital
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    //relationship between user and disease
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'disease_user', 'user_id', 'disease_id')->as('records')->withTimestamps();
    }

    //relationship between user and comment
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    //relationship between user and reply
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }
}

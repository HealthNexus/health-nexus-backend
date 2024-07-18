<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeFilter($query, array $filters)
    {
        $query
            ->when(
                $filters['search'] ?? false,
                fn ($query, $search) =>
                $query->where(
                    fn ($query) =>
                    $query->where('title', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('excerpt', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('body', 'LIKE', '%' . request('search') . '%')
                )

            );

        $query
            ->when(
                $filters['category'] ?? false,
                fn ($query, $category) =>
                $query
                    ->whereHas(
                        'category',
                        fn ($query) =>
                        $query->where('slug', $category)
                    )
            );

        $query
            ->when(
                $filters['creator'] ?? false,
                fn ($query, $author) =>
                $query
                    ->whereHas(
                        'author',
                        fn ($query) =>
                        $query->where('username', $author)
                    )
            );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    //Category and Post relationship
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}

<?php

namespace App\Http\Controllers;

use App\Events\NewDiseasePostCreated;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //,
        $posts = Post::latest()->get();
        return response(['posts' => $posts], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'disease_id' => ['numeric'],
            'title' => ['required', 'max:255'],
            'excerpt' => ['required', 'string'],
            'body' => ['required', 'string'],
            'thumbnail' => ['required', 'image', 'max:2048']
        ]);

        $thumbnail = $this->saveImage($request->thumbnail, 'posts');
        $attributes['thumbnail'] = $thumbnail;
        $post = $request->user()->posts()->create($attributes);

        // Notify users about the new post
        event(new NewDiseasePostCreated($post));
        return response([
            'post' => $post,
            'message' => 'Post successfully created'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return response([
            'post' => $post
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //
        $attributes = $request->validate([
            'disease_id' => ['numeric'],
            'title' => ['max:255'],
            'excerpt' => ['string'],
            'body' => ['string'],
            'thumbnail' => ['image', 'max:2048']
        ]);

        $post->update($attributes);

        return response([
            'post' => $post,
            'message' => 'post updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            return response(['message' => 'You are not authorized to delete this post'], 403);
        }
        $post->delete();
        return response([
            'message' => 'post deleted successfully',
        ], 204);
    }
}

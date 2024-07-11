<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    //
    //create comment
    public function store(Post $post)
    {
        request()->validate([
            'body' => ['required', 'max:500']
        ]);

        $post->comments()->create([

            'body' => request('body'),

            'user_id' => request()->user()->id
        ]);

        return response([
            'message' => 'Comment created'
        ], 200);
    }

    //delete comment
    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response([
            'message' => 'Comment deleted'
        ], 200);
    }
}

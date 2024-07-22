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

        $comment = $post->comments()->create([

            'body' => request('body'),

            'user_id' => request()->user()->id
        ]);

        return response([
            'comment' => $comment,
            'message' => 'Comment created'
        ], 201);
    }

    //delete comment
    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== request()->user()->id) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        } else {
            $comment->delete();
            return response([
                'message' => 'Comment deleted'
            ], 200);
        }
    }
}

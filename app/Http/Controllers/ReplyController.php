<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Reply;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    //
    //create comment
    public function store(Comment $comment)
    {
        request()->validate([
            'body' => ['required', 'max:500']
        ]);

        $comment->replies()->create([
            'body' => request('body'),
            'user_id' => request()->user()->id
        ]);

        return response([
            'message' => 'Replied to comment successfully'
        ], 200);
    }

    //delete comment
    public function destroy(Reply $reply)
    {
        $reply->delete();
        return response([
            'message' => 'Reply deleted'
        ], 200);
    }
}

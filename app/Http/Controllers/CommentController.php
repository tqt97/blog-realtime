<?php

namespace App\Http\Controllers;

use App\Events\PostCommentCreated;
use App\Events\UserMetricDelta;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\PostCommentedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        /** @var Comment $comment */
        $comment = null;

        DB::transaction(function () use ($post, $user, $data, &$comment) {
            $comment = $post->comments()->create([
                'user_id' => $user->id,
                'content' => $data['content'],
            ]);
        });

        DB::afterCommit(function () use ($post, $user, $comment) {
            event(new PostCommentCreated($post->id, [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at->toISOString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]));

            $author = $post->author;
            if ($author && $author->id !== $user->id) {
                $author->notify(new PostCommentedNotification($post->id, $user->id, $comment->id));
                event(new UserMetricDelta($author->id, 'comments_today', +1));
            }
        });

        return response()->json([
            'ok' => true,
            'comment_id' => $comment->id,
        ]);
    }
}

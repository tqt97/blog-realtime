<?php

namespace App\Http\Controllers;

use App\Events\PostLikeCountUpdated;
use App\Events\UserMetricDelta;
use App\Models\Post;
use App\Notifications\PostLikedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function toggle(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $liked = false;

        DB::transaction(function () use ($post, $user, &$liked) {
            $existing = $post->likes()->where('user_id', $user->id)->first();

            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                $post->likes()->create(['user_id' => $user->id]);
                $liked = true;
            }
        });

        DB::afterCommit(function () use ($post, $user, $liked) {
            $count = $post->likes()->count();
            event(new PostLikeCountUpdated($post->id, $count));

            // Notify author + dashboard delta (nếu actor không phải author)
            $author = $post->author;
            if ($liked && $author && $author->id !== $user->id) {
                $author->notify(new PostLikedNotification($post->id, $user->id));
                event(new UserMetricDelta($author->id, 'likes_today', +1));
            }

            if (! $liked && $author && $author->id !== $user->id) {
                event(new UserMetricDelta($author->id, 'likes_today', -1));
            }
        });

        return response()->json([
            'liked' => $liked,
            'like_count' => $post->likes()->count(),
        ]);
    }
}

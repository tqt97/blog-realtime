<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today();

        // likes/comments trên các bài của user
        $postIds = $user->posts()->pluck('id');

        $likesToday = Like::whereIn('post_id', $postIds)
            ->whereDate('created_at', $today)
            ->count();

        $commentsToday = Comment::whereIn('post_id', $postIds)
            ->whereDate('created_at', $today)
            ->count();

        $followers = $user->followers()->count();

        return view('dashboard', compact('likesToday', 'commentsToday', 'followers'));
    }
}

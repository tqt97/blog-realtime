<?php

namespace App\Http\Controllers;

use App\Events\UserMetricDelta;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    public function toggle(Request $request, User $user): JsonResponse // $user = following target
    {
        $actor = $request->user();

        abort_if($actor->id === $user->id, 403);

        $following = false;

        DB::transaction(function () use ($actor, $user, &$following) {
            $exists = $actor->following()->where('following_id', $user->id)->exists();

            if ($exists) {
                $actor->following()->detach($user->id);
                $following = false;
            } else {
                $actor->following()->attach($user->id);
                $following = true;
            }
        });

        DB::afterCommit(function () use ($user, $following) {
            event(new UserMetricDelta($user->id, 'followers', $following ? +1 : -1));
        });

        return response()->json([
            'following' => $following,
        ]);
    }
}

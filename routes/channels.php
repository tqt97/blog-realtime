<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{userId}', function ($user, int $userId) {
    return $user->id === $userId;
});

Broadcast::channel('post.{postId}', function ($user, int $postId) {
    return true; // public
});

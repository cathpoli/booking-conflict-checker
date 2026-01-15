<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for all booking updates
Broadcast::channel('bookings', function ($user) {
    // All authenticated users can listen to booking updates
    return $user !== null;
});

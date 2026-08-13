<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.vouchers', function ($user) {
    return $user !== null;
});

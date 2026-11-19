<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\LoginSecurityService;
use Illuminate\Auth\Events\Login;

class RecordLoginHistory
{
    public function __construct(
        protected LoginSecurityService $security,
    ) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->security->recordLogin($event->user, request());
        }
    }
}


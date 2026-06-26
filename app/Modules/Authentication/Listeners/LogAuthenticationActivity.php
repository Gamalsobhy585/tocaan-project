<?php


namespace App\Modules\Authentication\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user->id)
            ->withProperties((object)[
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard'      => $event->guard,
            ])
            ->log('User logged in');
    }

    public function handleLogout(Logout $event): void
    {
        activity('auth')
            ->causedBy($event->user?->getAuthIdentifier())
            ->withProperties((object)[
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged out');
    }

    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->withProperties((object)[
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
                'email'      => $event->credentials['email'] ?? null,
                'guard'      => $event->guard,
            ])
            ->log('Failed login attempt');
    }
}
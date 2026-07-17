<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Обновляет last_seen_at авторизованного пользователя, чтобы isOnline()
 * корректно показывал "в сети" во время обычной работы с чатом (не только
 * в голосовых каналах). Пишем в БД не чаще раза в 20 секунд, чтобы не
 * нагружать базу на каждый запрос/polling.
 */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = Auth::user()) {
            if (! $user->last_seen_at || $user->last_seen_at->diffInSeconds(now()) > 20) {
                $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    /**
     * Данные для всплывающей карточки профиля пользователя (как в Discord).
     * Возвращает JSON, который подставляется в попап через Alpine.js.
     */
    public function card(User $user): JsonResponse
    {
        $me = Auth::user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'banner_color' => $user->banner_color,
            'bio' => $user->bio,
            'pronouns' => $user->pronouns,
            'status' => $user->status,
            'is_online' => $user->isOnline(),
            'member_since' => optional($user->created_at)->format('d.m.Y'),
            'mutual_servers' => $me ? $me->mutualServersCount($user) : 0,
            'relationship' => $me ? $me->relationshipStatusWith($user) : 'none',
        ]);
    }
}

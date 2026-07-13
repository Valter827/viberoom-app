<?php

use App\Models\Channel as ChatChannel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Здесь описываем, кто может подписаться на приватные вебсокет-каналы.
| Discord-подобный доступ: пользователь должен быть участником сервера,
| которому принадлежит канал.
*/

Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    $channel = ChatChannel::find($channelId);

    if (! $channel) {
        return false;
    }

    // Проверяем, состоит ли пользователь в сервере, которому принадлежит канал
    return $channel->server->members()->where('user_id', $user->id)->exists();
});

/**
 * Presence-канал сервера — показывает, кто сейчас онлайн (список участников
 * в правой панели, как в Discord).
 */
Broadcast::channel('server.{serverId}', function ($user, $serverId) {
    $server = \App\Models\Server::find($serverId);

    if (! $server || ! $server->members()->where('user_id', $user->id)->exists()) {
        return null;
    }

    return ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatar_url];
});

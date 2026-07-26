<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\VoiceParticipant;
use App\Models\VoiceSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Голосовые каналы без постоянного WebSocket-сервера: сигналинг WebRTC
 * (offer/answer/ice candidate) идёт через обычную таблицу в БД, которую
 * клиенты опрашивают раз в секунду. Само аудио после установки соединения
 * идёт напрямую между браузерами (P2P) — сервер в нём не участвует.
 *
 * Ограничение: без TURN-сервера звонок может не установиться на некоторых
 * сетях с жёстким NAT/файрволом (за корпоративными сетями и т.п.). Для
 * небольших групп (2-6 человек) в обычных домашних/мобильных сетях работает.
 */
class VoiceController extends Controller
{
    /**
     * Зайти в голосовой канал (или обновить heartbeat, если уже внутри).
     */
    public function join(Channel $channel): JsonResponse
    {
        abort_unless($channel->isVoice(), 422, 'Это не голосовой канал.');
        $this->authorizeMember($channel);

        $participant = VoiceParticipant::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => Auth::id()],
            ['last_seen_at' => now()]
        );

        // Обновляем общий "в сети" статус пользователя сразу же — иначе индикатор
        // онлайн может моргнуть "не в сети" в первые секунды звонка, пока не
        // сработает первый heartbeat.
        Auth::user()->forceFill(['last_seen_at' => now()])->saveQuietly();

        return response()->json([
            'ok' => true,
            'participants' => $this->participantsList($channel),
            'joined_at' => $participant->created_at->toIso8601String(),
        ]);
    }

    /**
     * Heartbeat — чтобы понимать, кто ещё реально в канале (клиент шлёт раз в 5 сек).
     */
    public function heartbeat(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        $participant = VoiceParticipant::where('channel_id', $channel->id)
            ->where('user_id', Auth::id())
            ->first();

        abort_unless($participant, 404, 'Вы не в этом голосовом канале.');

        $participant->update([
            'last_seen_at' => now(),
            'muted' => $request->boolean('muted', $participant->muted),
        ]);

        // тот же прямой апдейт общего статуса "в сети" на каждый heartbeat
        Auth::user()->forceFill(['last_seen_at' => now()])->saveQuietly();

        // считаем "вышедшими" тех, от кого не было heartbeat более 15 секунд
        VoiceParticipant::where('channel_id', $channel->id)
            ->where('last_seen_at', '<', now()->subSeconds(15))
            ->delete();

        return response()->json(['participants' => $this->participantsList($channel)]);
    }

    /**
     * Выйти из голосового канала.
     */
    public function leave(Channel $channel): JsonResponse
    {
        VoiceParticipant::where('channel_id', $channel->id)->where('user_id', Auth::id())->delete();

        // сообщаем остальным, что мы вышли (чтобы закрыли PeerConnection)
        $others = VoiceParticipant::where('channel_id', $channel->id)->pluck('user_id');
        foreach ($others as $userId) {
            VoiceSignal::create([
                'channel_id' => $channel->id,
                'from_user_id' => Auth::id(),
                'to_user_id' => $userId,
                'type' => 'leave',
                'payload' => '{}',
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Отправить WebRTC-сигнал (offer/answer/candidate) конкретному участнику.
     */
    public function sendSignal(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        $validated = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:offer,answer,candidate'],
            'payload' => ['required', 'string'],
        ]);

        VoiceSignal::create([
            'channel_id' => $channel->id,
            'from_user_id' => Auth::id(),
            'to_user_id' => $validated['to_user_id'],
            'type' => $validated['type'],
            'payload' => $validated['payload'],
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Отдаёт iceServers для WebRTC-звонка. Порядок приоритета:
     *   1) Metered.ca — бесплатно 500 МБ/мес БЕЗ привязки карты
     *      (см. METERED_TURN_APP_NAME/METERED_TURN_API_KEY в .env).
     *   2) Cloudflare Realtime TURN — 1000 ГБ/мес бесплатно, но требует привязать карту
     *      (см. CLOUDFLARE_TURN_KEY_ID/CLOUDFLARE_TURN_TOKEN в .env).
     *   3) Собственный coturn, если настроен (TURN_HOST/TURN_SECRET).
     *   4) STUN-only — звонок будет работать только между собеседниками без жёсткого NAT.
     */
    public function turnCredentials(): JsonResponse
    {
        if ($iceServers = $this->meteredTurnCredentials()) {
            return response()->json(['iceServers' => $iceServers]);
        }

        if ($iceServers = $this->cloudflareTurnCredentials()) {
            return response()->json(['iceServers' => $iceServers]);
        }

        $host = config('services.turn.host');
        $secret = config('services.turn.secret');

        if ($host && $secret) {
            $ttlSeconds = 6 * 3600;
            $username = (now()->addSeconds($ttlSeconds)->timestamp) . ':' . Auth::id();
            $credential = base64_encode(hash_hmac('sha1', $username, $secret, true));

            return response()->json([
                'iceServers' => [
                    ['urls' => "stun:{$host}:3478"],
                    ['urls' => "turn:{$host}:3478?transport=udp", 'username' => $username, 'credential' => $credential],
                    ['urls' => "turn:{$host}:3478?transport=tcp", 'username' => $username, 'credential' => $credential],
                ],
            ]);
        }

        return response()->json([
            'iceServers' => [['urls' => 'stun:stun.cloudflare.com:3478']],
        ]);
    }

    /**
     * Metered.ca отдаёт готовый массив iceServers по простому GET-запросу с apiKey —
     * никакого TTL/подписи не нужно, поэтому кэшировать особо нечего, но всё равно
     * кэшируем на минуту, чтобы не долбить их API при частых /join подряд.
     */
    private function meteredTurnCredentials(): ?array
    {
        $appName = config('services.metered_turn.app_name');
        $apiKey = config('services.metered_turn.api_key');
        if (! $appName || ! $apiKey) {
            return null;
        }

        if ($cached = Cache::get('metered_turn_ice_servers')) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)
                ->get("https://{$appName}.metered.live/api/v1/turn/credentials", [
                    'apiKey' => $apiKey,
                ]);
        } catch (\Throwable $e) {
            return null; // Metered недоступен — упадём на следующий фолбэк
        }

        if (! $response->successful()) {
            return null;
        }

        $iceServers = $response->json();
        if (! is_array($iceServers) || ! count($iceServers)) {
            return null;
        }

        Cache::put('metered_turn_ice_servers', $iceServers, 60);
        return $iceServers;
    }

    /**
     * Кредов от Cloudflare хватает на всех пользователей сразу (они не привязаны
     * к конкретному человеку, только к TTL) — поэтому кэшируем на 5 минут меньше
     * TTL и не дёргаем их API на каждый /join, а только раз в ~6 часов.
     */
    private function cloudflareTurnCredentials(): ?array
    {
        $keyId = config('services.cloudflare_turn.key_id');
        $token = config('services.cloudflare_turn.token');
        if (! $keyId || ! $token) {
            return null;
        }

        if ($cached = Cache::get('cloudflare_turn_ice_servers')) {
            return $cached;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->acceptJson()
                ->post("https://rtc.live.cloudflare.com/v1/turn/keys/{$keyId}/credentials/generate-ice-servers", [
                    'ttl' => 21600, // 6 часов
                ]);
        } catch (\Throwable $e) {
            return null; // Cloudflare недоступен — упадём на фолбэк в turnCredentials()
        }

        if (! $response->successful()) {
            return null;
        }

        $iceServers = $response->json('iceServers');
        if (! $iceServers) {
            return null;
        }

        Cache::put('cloudflare_turn_ice_servers', $iceServers, 21300); // на 5 мин меньше TTL
        return $iceServers;
    }


    /**
     * Забрать все сигналы, адресованные мне, с момента последнего опроса.
     */
    public function pollSignals(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        $afterId = (int) $request->query('after', 0);

        $signals = VoiceSignal::where('channel_id', $channel->id)
            ->where('to_user_id', Auth::id())
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get(['id', 'from_user_id', 'type', 'payload']);

        return response()->json($signals);
    }

    /**
     * Лёгкий опрос "кто сейчас в каких голосовых каналах этого сервера" —
     * используется в сайдбаре, чтобы показывать участников прямо под названием
     * канала (даже если сам зритель не в голосовом канале).
     */
    public function serverParticipants(\App\Models\Server $server): JsonResponse
    {
        abort_unless($server->members()->where('user_id', Auth::id())->exists(), 403);

        // подчищаем "зависших" (без heartbeat > 15 сек) по всем голосовым каналам сервера
        $channelIds = $server->channels()->where('type', 'voice')->pluck('id');
        VoiceParticipant::whereIn('channel_id', $channelIds)
            ->where('last_seen_at', '<', now()->subSeconds(15))
            ->delete();

        $participants = VoiceParticipant::whereIn('channel_id', $channelIds)
            ->with('user:id,name,avatar_path')
            ->get()
            ->groupBy('channel_id')
            ->map(fn ($group) => $group->map(fn ($p) => [
                'user_id' => $p->user_id,
                'name' => $p->user->name,
                'avatar_url' => $p->user->avatar_url,
                'muted' => $p->muted,
            ])->values());

        return response()->json($participants);
    }

    private function participantsList(Channel $channel): array
    {
        return VoiceParticipant::where('channel_id', $channel->id)
            ->with('user:id,name,avatar_path')
            ->get()
            ->map(fn ($p) => [
                'user_id' => $p->user_id,
                'name' => $p->user->name,
                'avatar_url' => $p->user->avatar_url,
                'muted' => $p->muted,
                'is_me' => $p->user_id === Auth::id(),
            ])
            ->values()
            ->all();
    }

    private function authorizeMember(Channel $channel): void
    {
        abort_unless(
            $channel->server->members()->where('user_id', Auth::id())->exists(),
            403
        );
    }
}

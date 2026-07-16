<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\VoiceParticipant;
use App\Models\VoiceSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        VoiceParticipant::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => Auth::id()],
            ['last_seen_at' => now()]
        );

        return response()->json(['ok' => true, 'participants' => $this->participantsList($channel)]);
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

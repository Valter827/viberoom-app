<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\PartyCard;
use App\Models\PartyCardSlot;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Interactive Party Finder / «Карточка Пати».
 *
 * Карточка — это специальное сообщение (messages.type = 'party') со
 * связанной записью party_cards и слотами party_card_slots. Изменения
 * состояния карточки (кто зашёл/вышел, готова ли команда) доходят до
 * остальных участников через тот же polling чата — мы просто "трогаем"
 * (touch) связанное сообщение, и оно попадает в очередной ответ /poll.
 */
class PartyController extends Controller
{
    public function store(Request $request, Channel $channel): JsonResponse
    {
        abort_unless($channel->isText(), 422, 'Карточку пати можно создать только в текстовом канале.');
        $this->authorizeMember($channel);
        abort_unless($channel->server->party_finder_enabled, 403, 'Пати-финдер выключен на этом сервере.');

        $validated = $request->validate([
            'game' => ['required', 'string', 'max:60'],
            'mode' => ['nullable', 'string', 'max:40'],
            'max_slots' => ['required', 'integer', 'min:2', 'max:10'],
        ]);

        $message = $channel->messages()->create([
            'user_id' => Auth::id(),
            'type' => 'party',
        ]);

        $card = PartyCard::create([
            'message_id' => $message->id,
            'channel_id' => $channel->id,
            'creator_id' => Auth::id(),
            'game' => $validated['game'],
            'mode' => $validated['mode'] ?? null,
            'max_slots' => $validated['max_slots'],
        ]);

        // создатель сразу занимает первый слот своей же карточки
        PartyCardSlot::create(['party_card_id' => $card->id, 'user_id' => Auth::id(), 'position' => 0]);

        $message->load([
            'user:id,name,avatar_path', 'reactions.user:id,name', 'parent.user:id,name',
            'partyCard.slots.user:id,name,avatar_path',
        ]);

        return response()->json($message->toChatArray(), 201);
    }

    /**
     * Встать в слот в 1 клик.
     */
    public function joinSlot(Request $request, PartyCard $card): JsonResponse
    {
        $this->authorizeMember($card->channel);
        abort_unless($card->status === 'open', 422, 'Эта карточка уже неактивна.');

        $validated = $request->validate(['position' => ['required', 'integer', 'min:0']]);
        abort_if($validated['position'] >= $card->max_slots, 422, 'Такого слота нет.');
        abort_if($card->slots()->where('user_id', Auth::id())->exists(), 422, 'Вы уже в этой карточке.');

        try {
            PartyCardSlot::create([
                'party_card_id' => $card->id,
                'user_id' => Auth::id(),
                'position' => $validated['position'],
            ]);
        } catch (QueryException $e) {
            // кто-то занял слот на долю секунды раньше — это нормально при "гонке" за место
            return response()->json(['error' => 'Слот уже занят.'], 409);
        }

        $this->syncStatus($card);

        return response()->json($card->fresh()->toCardArray());
    }

    public function leaveSlot(PartyCard $card): JsonResponse
    {
        $this->authorizeMember($card->channel);

        PartyCardSlot::where('party_card_id', $card->id)->where('user_id', Auth::id())->delete();

        $this->syncStatus($card);

        return response()->json($card->fresh()->toCardArray());
    }

    /**
     * Отменить карточку — доступно создателю или модератору канала.
     */
    public function cancel(PartyCard $card): JsonResponse
    {
        $this->authorizeMember($card->channel);
        $canModerate = $card->channel->server->canModerate(Auth::id());
        abort_unless($card->creator_id === Auth::id() || $canModerate, 403);

        $card->update(['status' => 'cancelled']);
        $card->message?->touch();

        return response()->json($card->fresh()->toCardArray());
    }

    private function syncStatus(PartyCard $card): void
    {
        if ($card->status === 'cancelled') {
            return;
        }

        $filled = $card->slots()->count();
        $newStatus = $filled >= $card->max_slots ? 'full' : 'open';

        if ($card->status !== $newStatus) {
            $card->update(['status' => $newStatus]);
        }

        // "трогаем" сообщение в любом случае — состав слотов мог поменяться
        // без смены статуса (кто-то вышел, оставив место открытым)
        $card->message?->touch();
    }

    private function authorizeMember(Channel $channel): void
    {
        abort_unless($channel->server->members()->where('user_id', Auth::id())->exists(), 403);
    }
}

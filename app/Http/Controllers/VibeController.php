<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelPresence;
use App\Models\VibeActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vibe Match / Совпадение интересов.
 *
 * Пользователь может выставить себе короткий "движок" (играю / ищу компанию /
 * слушаю), а клиент раз в несколько секунд шлёт heartbeat "я сейчас смотрю
 * на этот текстовый канал". На основе того, кто реально в канале прямо
 * сейчас, сервер считает пересечения интересов и отдаёт готовые дружелюбные
 * плашки — без вебсокетов, тем же способом polling, что и остальной чат.
 */
class VibeController extends Controller
{
    /**
     * Выставить/обновить свою текущую активность.
     */
    public function setActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:' . implode(',', VibeActivity::CATEGORIES)],
            'label' => ['required', 'string', 'max:80'],
        ]);

        $activity = VibeActivity::updateOrCreate(['user_id' => Auth::id()], $validated);

        return response()->json(['activity' => $activity->toArray()]);
    }

    /**
     * Сбросить свою активность ("больше не показывать статус").
     */
    public function clearActivity(): JsonResponse
    {
        VibeActivity::where('user_id', Auth::id())->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Моя текущая активность — подтягивается при открытии формы, чтобы
     * предзаполнить поля тем, что уже было выставлено ранее.
     */
    public function mine(): JsonResponse
    {
        $activity = VibeActivity::where('user_id', Auth::id())->first();

        return response()->json(['activity' => $activity?->toArray()]);
    }

    /**
     * Heartbeat "я сейчас смотрю на этот канал" + вернуть текущие совпадения
     * интересов среди тех, кто реально в канале прямо сейчас.
     */
    public function heartbeat(Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        ChannelPresence::updateOrCreate(
            ['channel_id' => $channel->id, 'user_id' => Auth::id()],
            ['last_seen_at' => now()]
        );

        // считаем "вышедшими" тех, от кого не было heartbeat > 20 секунд
        ChannelPresence::where('channel_id', $channel->id)
            ->where('last_seen_at', '<', now()->subSeconds(20))
            ->delete();

        if (! $channel->server->vibe_match_enabled) {
            return response()->json(['matches' => []]);
        }

        $presentUserIds = ChannelPresence::where('channel_id', $channel->id)->pluck('user_id');

        $activities = VibeActivity::whereIn('user_id', $presentUserIds)
            ->with('user:id,name')
            ->get();

        return response()->json(['matches' => $this->computeMatches($activities)]);
    }

    /**
     * Простой алгоритм пересечений: сравниваем всех присутствующих попарно.
     * Совпадение — если ярлык активности (игра/трек) совпадает после
     * нормализации регистра/пробелов. Осознанно без "нечёткого" сравнения —
     * лучше меньше, но точных совпадений, чем ложные срабатывания.
     */
    private function computeMatches($activities): array
    {
        $items = $activities->values();
        $matches = [];

        for ($i = 0; $i < $items->count(); $i++) {
            for ($j = $i + 1; $j < $items->count(); $j++) {
                $a = $items[$i];
                $b = $items[$j];

                if ($a->user_id === $b->user_id) {
                    continue;
                }

                $labelA = mb_strtolower(trim($a->label));
                $labelB = mb_strtolower(trim($b->label));

                if ($labelA === '' || $labelA !== $labelB) {
                    continue;
                }

                $nameA = $a->user->name;
                $nameB = $b->user->name;
                $bothLfg = $a->category === 'lfg' && $b->category === 'lfg';
                $oneLfg = $a->category === 'lfg' xor $b->category === 'lfg';
                $bothMusic = $a->category === 'music' && $b->category === 'music';

                $text = match (true) {
                    $bothLfg => "Кажется, {$nameA} и {$nameB} сейчас не прочь сыграть в «{$a->label}» — предложите им объединиться!",
                    $oneLfg => "{$nameA} и {$nameB}: один ищет компанию, а другой уже в «{$a->label}» — самое время позвать в команду.",
                    $bothMusic => "{$nameA} и {$nameB} сейчас слушают одно и то же — «{$a->label}» 🎧",
                    default => "{$nameA} и {$nameB} сейчас оба играют в «{$a->label}»",
                };

                $matches[] = [
                    'key' => min($a->user_id, $b->user_id) . '-' . max($a->user_id, $b->user_id) . '|' . $labelA,
                    'text' => $text,
                    'users' => [$a->user_id, $b->user_id],
                ];
            }
        }

        return $matches;
    }

    private function authorizeMember(Channel $channel): void
    {
        abort_unless($channel->server->members()->where('user_id', Auth::id())->exists(), 403);
    }
}

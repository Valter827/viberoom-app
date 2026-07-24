<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\TacticalBoard;
use App\Models\TacticalStroke;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tactical Canvas / Тактический Оверлей.
 *
 * Один "холст" на текстовый канал. Штрихи — отдельные записи с координатами
 * в процентах (0-100), синхронизация тем же polling-паттерном, что и чат:
 * клиент периодически спрашивает "что появилось после штриха с id X".
 * "Очистить" не удаляет штрихи, а увеличивает version холста — старые
 * штрихи просто перестают попадать в выборку, а у клиентов, которые ещё не
 * знают о новой версии, следующий poll сам подскажет перерисовать холст с нуля.
 */
class TacticalController extends Controller
{
    public function show(Channel $channel): JsonResponse
    {
        $this->authorizeAccess($channel);
        $board = $this->boardFor($channel);

        $strokes = $board->strokes()->where('version', $board->version)->orderBy('id')->get();

        return response()->json([
            'map_key' => $board->map_key,
            'version' => $board->version,
            'strokes' => $strokes->map(fn ($s) => $s->toStrokeArray())->values(),
        ]);
    }

    public function poll(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeAccess($channel);
        $board = $this->boardFor($channel);

        $afterId = (int) $request->query('after', 0);
        $clientVersion = (int) $request->query('version', 0);

        // клиент отстал по версии (кто-то очистил холст) — просим перезагрузить всё целиком
        if ($clientVersion !== 0 && $clientVersion !== $board->version) {
            $strokes = $board->strokes()->where('version', $board->version)->orderBy('id')->get();

            return response()->json([
                'map_key' => $board->map_key,
                'version' => $board->version,
                'reset' => true,
                'strokes' => $strokes->map(fn ($s) => $s->toStrokeArray())->values(),
            ]);
        }

        $strokes = $board->strokes()
            ->where('version', $board->version)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'map_key' => $board->map_key,
            'version' => $board->version,
            'reset' => false,
            'strokes' => $strokes->map(fn ($s) => $s->toStrokeArray())->values(),
        ]);
    }

    public function addStroke(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeAccess($channel);
        $board = $this->boardFor($channel);

        $validated = $request->validate([
            'tool' => ['required', 'in:pen,arrow,line'],
            'color' => ['required', 'string', 'max:10'],
            'width' => ['required', 'integer', 'min:1', 'max:12'],
            'points' => ['required', 'array', 'min:2'],
            'points.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'points.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $stroke = TacticalStroke::create([
            'tactical_board_id' => $board->id,
            'user_id' => Auth::id(),
            'version' => $board->version,
            'tool' => $validated['tool'],
            'color' => $validated['color'],
            'width' => $validated['width'],
            'points' => json_encode($validated['points']),
        ]);

        return response()->json($stroke->toStrokeArray(), 201);
    }

    public function setMap(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeAccess($channel);
        $board = $this->boardFor($channel);

        $validated = $request->validate(['map_key' => ['required', 'in:' . implode(',', TacticalBoard::MAPS)]]);

        $board->update(['map_key' => $validated['map_key'], 'version' => $board->version + 1]);

        return response()->json(['map_key' => $board->map_key, 'version' => $board->version]);
    }

    public function clear(Channel $channel): JsonResponse
    {
        $this->authorizeAccess($channel);
        $board = $this->boardFor($channel);

        $board->update(['version' => $board->version + 1]);

        return response()->json(['version' => $board->version]);
    }

    private function boardFor(Channel $channel): TacticalBoard
    {
        return TacticalBoard::firstOrCreate(['channel_id' => $channel->id]);
    }

    private function authorizeAccess(Channel $channel): void
    {
        abort_unless($channel->server->members()->where('user_id', Auth::id())->exists(), 403);
        abort_unless($channel->server->tactical_canvas_enabled, 403, 'Тактический оверлей выключен на этом сервере.');
    }
}

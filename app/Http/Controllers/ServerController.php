<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServerController extends Controller
{
    /**
     * Показать сервер: список каналов слева + первый доступный текстовый канал по центру.
     */
    public function show(Server $server): View|RedirectResponse
    {
        $this->authorizeMember($server);

        $server->load(['categories.channels', 'channels' => fn ($q) => $q->whereNull('category_id')]);

        // По умолчанию открываем первый текстовый канал сервера
        $firstChannel = $server->channels()->where('type', 'text')->orderBy('position')->first();

        if (! $firstChannel) {
            // если каналов ещё нет — просто показываем пустой сервер
            return view('servers.show', ['server' => $server, 'activeChannel' => null, 'messages' => collect()]);
        }

        return redirect()->route('channels.show', [$server, $firstChannel]);
    }

    /**
     * Форма создания нового сервера.
     */
    public function create(): View
    {
        return view('servers.create');
    }

    /**
     * Живой статус "в сети" всех участников сервера — опрашивается сайдбаром
     * раз в ~10 сек, чтобы дот онлайн/оффлайн обновлялся без перезагрузки
     * страницы (например, когда кто-то вышел или поставил "невидимку").
     */
    public function onlineStatuses(Server $server): \Illuminate\Http\JsonResponse
    {
        abort_unless($server->members()->where('user_id', Auth::id())->exists(), 403);

        $statuses = $server->members()->get(['users.id'])->mapWithKeys(
            fn ($m) => [$m->id => $m->isOnline()]
        );

        return response()->json($statuses);
    }

    /**
     * Страница настроек сервера (название, иконка, описание + управление участниками) —
     * доступна owner/admin (полностью) и moderator (только вкладка участников).
     */
    public function edit(Server $server): View
    {
        $role = $server->roleOf(Auth::id());
        abort_unless(in_array($role, ['owner', 'admin', 'moderator']), 403, 'Недостаточно прав.');

        $server->load(['bans.user']);
        $roleOrder = ['owner' => 0, 'admin' => 1, 'moderator' => 2, 'member' => 3];
        $server->setRelation(
            'members',
            $server->members()->get()->sortBy(fn ($m) => $roleOrder[$m->pivot->role] ?? 9)->values()
        );

        return view('servers.edit', ['server' => $server, 'myRole' => $role]);
    }

    /**
     * Сохранить новый сервер: создатель автоматически становится owner
     * и первым участником, плюс создаётся дефолтная категория и текстовый канал.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('server-icons', 'public');
        }

        $server = Server::create([
            'name' => $validated['name'],
            'icon_path' => $iconPath,
            'owner_id' => Auth::id(),
        ]);

        // владелец сразу добавляется как участник с ролью owner
        $server->members()->attach(Auth::id(), ['role' => 'owner']);

        // дефолтная структура: категория "Text Channels" + канал "general"
        $category = $server->categories()->create(['name' => 'Текстовые каналы', 'position' => 0]);
        $server->channels()->create([
            'category_id' => $category->id,
            'name' => 'general',
            'type' => 'text',
            'position' => 0,
        ]);

        return redirect()->route('servers.show', $server)
            ->with('status', 'Сервер "' . $server->name . '" успешно создан!');
    }

    /**
     * Присоединиться к серверу по коду приглашения.
     */
    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string'],
        ]);

        $server = Server::where('invite_code', $validated['invite_code'])->firstOrFail();

        abort_if(
            $server->bans()->where('user_id', Auth::id())->exists(),
            403,
            'Вы забанены на этом сервере.'
        );

        // если пользователь уже участник — просто перенаправляем на сервер
        if (! $server->members()->where('user_id', Auth::id())->exists()) {
            $server->members()->attach(Auth::id(), ['role' => 'member']);
        }

        return redirect()->route('servers.show', $server);
    }

    /**
     * Обновить название/иконку сервера (доступно только owner/admin).
     */
    public function update(Request $request, Server $server): RedirectResponse
    {
        $this->authorizeAdmin($server);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('icon')) {
            if ($server->icon_path) {
                Storage::disk('public')->delete($server->icon_path);
            }
            $validated['icon_path'] = $request->file('icon')->store('server-icons', 'public');
        }

        $server->update($validated);

        return back()->with('status', 'Настройки сервера обновлены.');
    }

    /**
     * Проверка: пользователь является участником сервера.
     * В реальном проекте лучше вынести в Policy (ServerPolicy).
     */
    private function authorizeMember(Server $server): void
    {
        abort_unless(
            $server->members()->where('user_id', Auth::id())->exists(),
            403,
            'Вы не являетесь участником этого сервера.'
        );
    }

    private function authorizeAdmin(Server $server): void
    {
        $role = $server->members()->where('user_id', Auth::id())->value('role');
        abort_unless(in_array($role, ['owner', 'admin']), 403, 'Недостаточно прав.');
    }
}

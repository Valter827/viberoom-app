<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerBan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerMemberController extends Controller
{
    /**
     * Сменить роль участника (owner/admin могут назначать admin/moderator/member,
     * назначить роль owner нельзя — она одна и передаётся отдельно).
     */
    public function updateRole(Request $request, Server $server, User $user): RedirectResponse
    {
        abort_unless($server->canManage(Auth::id()), 403, 'Недостаточно прав.');
        abort_if($server->roleOf($user->id) === 'owner', 403, 'Нельзя менять роль владельца сервера.');

        $validated = $request->validate([
            'role' => ['required', 'in:admin,moderator,member'],
        ]);

        $server->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('status', 'Роль пользователя ' . $user->name . ' обновлена.');
    }

    /**
     * Кикнуть участника с сервера (без бана — он сможет зайти повторно по инвайту).
     */
    public function kick(Server $server, User $user): RedirectResponse
    {
        abort_unless($server->canModerate(Auth::id()), 403, 'Недостаточно прав.');
        abort_if($server->roleOf($user->id) === 'owner', 403, 'Нельзя кикнуть владельца сервера.');

        $server->members()->detach($user->id);

        return back()->with('status', $user->name . ' был(а) исключён(а) с сервера.');
    }

    /**
     * Забанить участника — исключает и запрещает вход по инвайту.
     */
    public function ban(Server $server, User $user): RedirectResponse
    {
        abort_unless($server->canModerate(Auth::id()), 403, 'Недостаточно прав.');
        abort_if($server->roleOf($user->id) === 'owner', 403, 'Нельзя забанить владельца сервера.');

        $server->members()->detach($user->id);
        ServerBan::firstOrCreate(['server_id' => $server->id, 'user_id' => $user->id]);

        return back()->with('status', $user->name . ' забанен(а) на этом сервере.');
    }

    /**
     * Разбанить.
     */
    public function unban(Server $server, User $user): RedirectResponse
    {
        abort_unless($server->canManage(Auth::id()), 403, 'Недостаточно прав.');

        ServerBan::where('server_id', $server->id)->where('user_id', $user->id)->delete();

        return back()->with('status', $user->name . ' разбанен(а).');
    }
}

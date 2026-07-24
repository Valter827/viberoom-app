<?php
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServerMemberController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VoiceController;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});
// Стандартные маршруты аутентификации (auth::routes или Breeze/Jetstream)
 require __DIR__.'/auth.php';
Route::middleware(['auth'])->group(function () {

    // "Домашняя" страница: список друзей / ЛС. Больше не заставляем
    // пользователя сразу создавать сервер — сервер нужен только по желанию.
    Route::get('/dashboard', [FriendController::class, 'index'])->name('dashboard');

    // --- Профиль пользователя ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // --- Просмотр чужого профиля (карточка) ---
    Route::get('/users/{user}/card', [UserProfileController::class, 'card'])->name('users.card');
    // --- Друзья ---
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends', [FriendController::class, 'store'])->name('friends.store');
    Route::post('/friends/{user}/accept', [FriendController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/{user}/decline', [FriendController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/{user}', [FriendController::class, 'destroy'])->name('friends.destroy');
    // --- Упоминания (колокольчик уведомлений) ---
    Route::get('/mentions', [MentionController::class, 'index'])->name('mentions.index');
    Route::post('/mentions/read', [MentionController::class, 'markRead'])->name('mentions.read');
    // --- Серверы ---
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::post('/servers/join', [ServerController::class, 'join'])->name('servers.join');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/servers/{server}/settings', [ServerController::class, 'edit'])->name('servers.edit');
    Route::patch('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
    Route::delete('/servers/{server}/leave', [ServerController::class, 'leave'])->name('servers.leave');
    Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.destroy');
    Route::get('/servers/{server}/online-statuses', [ServerController::class, 'onlineStatuses'])->name('servers.online-statuses');
    // --- Участники сервера: роли/кик/бан ---
    Route::patch('/servers/{server}/members/{user}/role', [ServerMemberController::class, 'updateRole'])->name('members.role');
    Route::delete('/servers/{server}/members/{user}', [ServerMemberController::class, 'kick'])->name('members.kick');
    Route::post('/servers/{server}/members/{user}/ban', [ServerMemberController::class, 'ban'])->name('members.ban');
    Route::delete('/servers/{server}/bans/{user}', [ServerMemberController::class, 'unban'])->name('members.unban');
    // --- Каналы (вложены в сервер) ---
    Route::post('/servers/{server}/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/servers/{server}/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    // --- Сообщения (AJAX, JSON-ответ) ---
    Route::post('/channels/{channel}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/channels/{channel}/messages/poll', [MessageController::class, 'poll'])->name('messages.poll');
    Route::get('/channels/{channel}/messages/pinned', [MessageController::class, 'pinned'])->name('messages.pinned');
    Route::get('/channels/{channel}/messages/search', [MessageController::class, 'search'])->name('messages.search');
    Route::patch('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/messages/{message}/react', [MessageController::class, 'react'])->name('messages.react');
    Route::post('/messages/{message}/pin', [MessageController::class, 'pin'])->name('messages.pin');
    // --- Голосовые каналы (presence + polling-сигналинг WebRTC) ---
    Route::post('/channels/{channel}/voice/join', [VoiceController::class, 'join'])->name('voice.join');
    Route::post('/channels/{channel}/voice/heartbeat', [VoiceController::class, 'heartbeat'])->name('voice.heartbeat');
    Route::post('/channels/{channel}/voice/leave', [VoiceController::class, 'leave'])->name('voice.leave');
    Route::post('/channels/{channel}/voice/signal', [VoiceController::class, 'sendSignal'])->name('voice.signal');
    Route::get('/channels/{channel}/voice/signals', [VoiceController::class, 'pollSignals'])->name('voice.signals');
    Route::get('/servers/{server}/voice-participants', [VoiceController::class, 'serverParticipants'])->name('voice.server-participants');
});

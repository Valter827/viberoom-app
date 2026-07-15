<?php
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\UserProfileController;
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
    // --- Серверы ---
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::post('/servers/join', [ServerController::class, 'join'])->name('servers.join');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/servers/{server}/settings', [ServerController::class, 'edit'])->name('servers.edit');
    Route::patch('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
    // --- Каналы (вложены в сервер) ---
    Route::post('/servers/{server}/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/servers/{server}/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    // --- Сообщения (AJAX, JSON-ответ) ---
    Route::post('/channels/{channel}/messages', [MessageController::class, 'store'])->name('messages.store');
});
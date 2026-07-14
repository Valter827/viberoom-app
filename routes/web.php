<?php
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServerController;
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

    // Логика "дашборда": если у пользователя уже есть сервер — открываем первый,
    // если серверов нет — отправляем на страницу создания сервера
    Route::get('/dashboard', function () {
        $firstServer = auth()->user()->servers()->first();

        return $firstServer
            ? redirect()->route('servers.show', $firstServer)
            : redirect()->route('servers.create');
    })->name('dashboard');

    // --- Профиль пользователя ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // --- Серверы ---
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::post('/servers/join', [ServerController::class, 'join'])->name('servers.join');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::patch('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
    // --- Каналы (вложены в сервер) ---
    Route::post('/servers/{server}/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/servers/{server}/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    // --- Сообщения (AJAX, JSON-ответ) ---
    Route::post('/channels/{channel}/messages', [MessageController::class, 'store'])->name('messages.store');
});
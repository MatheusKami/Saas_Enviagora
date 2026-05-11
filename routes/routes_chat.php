<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Rotas do Chat — adicione este bloco no seu routes/web.php
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/chat',         [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send',   [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/clear',  [ChatController::class, 'clear'])->name('chat.clear');

});

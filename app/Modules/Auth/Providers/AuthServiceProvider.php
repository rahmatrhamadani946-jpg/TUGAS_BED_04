<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Dibungkus group 'web' agar session, cookie, dan CSRF aktif.
        // loadRoutesFrom() saja TIDAK menambahkan middleware group 'web',
        // sehingga middleware 'auth' tidak dapat membaca session.
        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}

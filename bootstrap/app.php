<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Actions\SyncPointagesFromZkteco;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->call(function () {
            try {
                (new SyncPointagesFromZkteco)();
                Log::info('Synchronisation ZKTeco réussie.');
            } catch (\Exception $e) {
                Log::error('Échec de la synchronisation ZKTeco : ' . $e->getMessage());
            }
        })->daily()
          ->name('zkteco-sync')
          ->withoutOverlapping()
          ->onSuccess(function () {
              Log::info('Tâche zkteco-sync exécutée avec succès.');
          })
          ->onFailure(function () {
              Log::error('Tâche zkteco-sync a échoué.');
          });
    })->create();

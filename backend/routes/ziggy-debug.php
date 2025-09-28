<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\Ziggy;

// Ruta de diagnóstico para verificar Ziggy

if (app()->environment('local')) {
    Route::get('/ziggy-debug', function () {
        $groups = [
            'public' => new Ziggy(group: 'public'),
            'staff' => new Ziggy(group: 'staff'),
        ];

        return response()->json([
            'groups' => [
                'public' => count($groups['public']->toArray()['routes']),
                'staff' => count($groups['staff']->toArray()['routes']),
            ],
            'routes' => [
                'public' => array_keys($groups['public']->toArray()['routes']),
                'staff' => array_keys($groups['staff']->toArray()['routes']),
            ],
        ]);
    })->name('ziggy-debug');
}

<?php

declare(strict_types=1);

it('returns no content from CSRF cookie route', function () {
    // Usa una ruta liviana que no renderiza la vista SPA con Vite
    $response = $this->get('/sanctum/csrf-cookie');

    $response->assertNoContent(); // 204
});

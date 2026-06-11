<?php

use Symfony\Component\HttpKernel\Exception\HttpException;

test('custom not found error page is rendered', function (): void {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSee('Halaman tidak ditemukan')
        ->assertSee('Error 404');
});

test('custom error views can be rendered', function (string $view, int $status): void {
    $html = view($view, [
        'exception' => new HttpException($status),
    ])->render();

    expect($html)
        ->toContain('Error')
        ->toContain((string) $status)
        ->toContain('Digital Repository');
})->with([
    ['errors.403', 403],
    ['errors.404', 404],
    ['errors.419', 419],
    ['errors.429', 429],
    ['errors.500', 500],
    ['errors.503', 503],
    ['errors.4xx', 418],
    ['errors.5xx', 502],
]);

@include('errors.layout', [
    'status' => '429',
    'title' => 'Terlalu banyak permintaan',
    'message' => 'Sistem menerima terlalu banyak permintaan dalam waktu singkat.',
    'detail' => 'Tunggu beberapa saat sebelum mencoba kembali.',
    'showReload' => true,
])

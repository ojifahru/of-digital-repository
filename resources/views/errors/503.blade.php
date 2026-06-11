@include('errors.layout', [
    'status' => '503',
    'title' => 'Layanan sementara tidak tersedia',
    'message' => 'Repository sedang dalam pemeliharaan atau menerima beban tinggi.',
    'detail' => 'Silakan coba lagi beberapa saat lagi.',
    'showReload' => true,
])

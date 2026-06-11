@include('errors.layout', [
    'status' => '500',
    'title' => 'Terjadi gangguan',
    'message' => 'Server mengalami kendala saat memproses permintaan Anda.',
    'detail' => 'Tim pengelola dapat memeriksa log aplikasi menggunakan kode error ini.',
    'showReload' => true,
])

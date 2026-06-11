@include('errors.layout', [
    'status' => '419',
    'title' => 'Sesi kedaluwarsa',
    'message' => 'Sesi halaman sudah habis sehingga permintaan tidak dapat dilanjutkan.',
    'detail' => 'Muat ulang halaman, lalu ulangi aksi terakhir Anda.',
    'primaryLabel' => 'Muat Ulang',
    'primaryUrl' => url()->current(),
    'secondaryLabel' => 'Ke Beranda',
    'secondaryUrl' => url('/'),
])

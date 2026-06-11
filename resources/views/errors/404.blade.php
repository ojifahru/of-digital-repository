@include('errors.layout', [
    'status' => '404',
    'title' => 'Halaman tidak ditemukan',
    'message' => 'Alamat yang Anda buka tidak tersedia, sudah dipindahkan, atau tidak pernah dipublikasikan.',
    'detail' => 'Coba mulai dari beranda atau telusuri kembali daftar dokumen repository.',
    'secondaryLabel' => 'Cari Dokumen',
])

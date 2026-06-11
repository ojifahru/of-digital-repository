@include('errors.layout', [
    'status' => '403',
    'title' => 'Akses dibatasi',
    'message' => 'Anda tidak memiliki izin untuk membuka halaman atau aksi ini.',
    'detail' => 'Pastikan akun Anda memiliki hak akses yang sesuai, lalu coba kembali dari area yang tersedia.',
    'secondaryLabel' => 'Lihat Dokumen',
])

<?php
require_once 'Pengajuan.php';
require_once 'HalamanStatusPengajuan.php';
require_once 'HalamanDetailPengajuan.php';

class ControlStatusPengajuan {

    // PC-CSP-01
    // Mengambil daftar pengajuan publikasi milik mahasiswa
    public function ambilDaftarPengajuan($idPengaju) {

        // 1. Query seluruh record pengajuan milik pengaju dari entitas Pengajuan
        daftarPengajuan=Pengajuan::getByPengaju(idPengaju);

        // 2. Evaluasi keberadaan data pengajuan
        if (daftarPengajuan==null||empty(daftarPengajuan)) {
            // Alur Alternatif: Pengaju belum pernah membuat pengajuan
            HalamanStatusPengajuan::tampilPesanBelumAda();
        } else {
            // Alur Normal: Render daftar riwayat pengajuan beserta label status
            HalamanStatusPengajuan::tampilDaftarPengajuan();
        }

        return $daftarPengajuan;
    }

    // PC-CSP-02
    // Mengambil rincian detail permohonan pengajuan spesifik
    public function ambilDetailPengajuan($idPengajuan) {

        // 1. Ambil rekaman data permohonan spesifik berdasarkan ID dari entitas Pengajuan
        detailPengajuan=Pengajuan::getById(idPengajuan);

        // 2. Tampilkan rincian data dan status permohonan ke HalamanDetailPengajuan
        HalamanDetailPengajuan::tampilDetailPengajuan();

        return $detailPengajuan;
    }
}
?>

<?php
require_once 'Pengajuan.php';
require_once 'RiwayatLog.php';
require_once 'Notifikasi.php';
require_once 'HalamanDetailRequest.php';

class ControlPemrosesanRequest {

    // PC-CPSR-01
    // Mengambil detail request berstatus 'Ditinjau' untuk diproses
    public function ambilDetailRequest($idPengajuan) {

        // 1. Ambil record pengajuan berdasarkan idPengajuan
        $pengajuan = Pengajuan::getById($idPengajuan);

        // 2. Evaluasi kelayakan record untuk diverifikasi
        if ($pengajuan === null || $pengajuan->statusPengajuan !== 'Ditinjau') {
            // Alur Alternatif: record tidak ada atau status tidak sesuai
            return null;
        } else {
            // Alur Normal: tampilkan data untuk verifikasi kepatuhan materi
            // terhadap SOP publikasi CMI
            HalamanDetailRequest::periksaKelengkapanMateri();
        }

        return $pengajuan;
    }

    // PC-CPSR-02
    // Memproses request publikasi
    public function prosesRequest($idPengajuan, $idCMI) {

        // 1. Perbarui status pengajuan menjadi 'Diproses'
        Pengajuan::updateStatus($idPengajuan, 'Diproses');

        // 2. Rekam jejak audit tindakan staf CMI
        $waktuSekarang = date('Y-m-d H:i:s');
        RiwayatLog::simpanRiwayat($idPengajuan, $idCMI, 'Memproses Request', $waktuSekarang);

        // 3. Ambil data pengajuan untuk mengetahui pengaju
        $pengajuan = Pengajuan::getById($idPengajuan);

        // 4. Kirim notifikasi sistem ke pengaju
        Notifikasi::kirimNotifikasiSistem(
            $idPengajuan,
            $pengajuan->idPengaju,
            'Permohonan Anda sedang diproses'
        );

        // 5. Tampilkan status terbaru
        HalamanDetailRequest::tampilStatusDiproses('Diproses');
    }

    // PC-CPSR-03
    // Menyimpan catatan kelengkapan materi
    public function simpanCatatanKelengkapan($idPengajuan, $catatan) {

        // 1. Skenario materi belum lengkap: simpan koreksi ke atribut
        // catatanKelengkapan pada entitas Pengajuan
        Pengajuan::setCatatanKelengkapan($idPengajuan, $catatan);

        // 2. Status pengajuan TIDAK diubah (tetap 'Ditinjau')
    }
}
?>

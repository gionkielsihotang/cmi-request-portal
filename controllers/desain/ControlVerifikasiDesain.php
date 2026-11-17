<?php
/**
 * SUBBAB 4.2 PROGRAM IMPLEMENTATION CODE
 * ID Kode     : [REV - SC02]
 * Nama Kelas  : ControlVerifikasiDesain
 * Acuan SDD   : Bab 3.4 Package Desain & Bab 3.6 (PC-CVD-01 s/d PC-CVD-03)
 * Penyusun    : Revalia Rahmadani
 */

require_once __DIR__ . '/../../entities/Pengajuan.php';
require_once __DIR__ . '/../../entities/BerkasLampiran.php';
require_once __DIR__ . '/../../boundaries/HalamanDetailPengajuan.php';

class ControlVerifikasiDesain
{

    // PC-CVD-01
    // Menampilkan hasil desain untuk diverifikasi
    public function ambilDataVerifikasi($idPengajuan)
    {

        // 1. Ambil sisa waktu verifikasi 1 jam dari entitas Pengajuan
        $sisaWaktu = Pengajuan::ambilTimerVerifikasi($idPengajuan);

        // 2. Ambil berkas hasil desain dari entitas BerkasLampiran
        $fileDesain = BerkasLampiran::ambilBerkasDesain($idPengajuan);

        // 3. Susun data verifikasi
        $dataVerifikasi = [];
        $dataVerifikasi["fileDesain"] = $fileDesain;
        $dataVerifikasi["sisaWaktu"] = $sisaWaktu;

        // 4. Tampilkan pratinjau desain dan hitung mundur ke halaman detail
        HalamanDetailPengajuan::tampilPratinjauDanTimer($fileDesain, $sisaWaktu);

        return $dataVerifikasi;
    }

    // PC-CVD-02
    // Menyetujui hasil desain
    public function setujuiDesain($idPengajuan)
    {

        // 1. Perbarui status pengajuan menjadi 'Fix' (desain lanjut ke tahap publikasi)
        Pengajuan::updateStatus($idPengajuan, "Fix");

        // 2. Perbarui tampilan: label status 'Fix' dan tombol verifikasi dinonaktifkan
        HalamanDetailPengajuan::perbaruiTampilanVerifikasi("Fix");

        return true;
    }

    // PC-CVD-03
    // Menyetujui desain otomatis saat waktu verifikasi habis
    public function cekTimeoutVerifikasi($idPengajuan, $sisaWaktu)
    {

        // Memeriksa apakah batas waktu verifikasi 1 jam telah habis
        if ($sisaWaktu <= 0) {
            // Alur Alternatif: pengaju tidak menanggapi, desain disetujui otomatis
            Pengajuan::autoApproveStatus($idPengajuan, "Fix");
            HalamanDetailPengajuan::kunciAksesVerifikasi("Fix");
            return true;
        } else {
            return false;
        }
    }
}
?>

<?php
/**
 * SUBBAB 4.2 PROGRAM IMPLEMENTATION CODE
 * ID Kode     : [GYS-SC02]
 * Nama Kelas  : ControlPreviewRequest
 * Acuan SDD   : Bab 3.4 Package Pengajuan & Bab 3.6 (PC-CPR-01 s/d PC-CPR-04)
 * Penyusun    : Gion Yehezkiel Sihotang
 */

require_once 'Pengajuan.php';
require_once 'BerkasLampiran.php';
require_once 'DialogPreviewRequest.php';
require_once 'FormRequestInternal.php';

class ControlPreviewRequest {

    // PC-CPR-01
    // Membuka modal pratinjau dan mengaktifkan timer hitung mundur
    public function kirimDataPreview($dataForm, $daftarBerkas = []) {

        // 1. Tampilkan modal dialog pratinjau permohonan
        DialogPreviewRequest::tampilPreview($dataForm, $daftarBerkas);

        // 2. Aktifkan timer hitung mundur toleransi pratinjau 180 detik (3 menit)
        DialogPreviewRequest::mulaiCountdown(180);
    }

    // PC-CPR-02
    // Menyimpan permohonan final dan berkas referensi ke basis data
    public function submitRequest($dataForm, $daftarBerkas = []) {

        // 1. Simpan permohonan ke entitas Pengajuan dengan status awal 'Dalam Antrian'
        $idPengajuan = Pengajuan::simpanPengajuan($dataForm, "Dalam Antrian");

        // 2. Simpan setiap berkas lampiran referensi ke entitas BerkasLampiran
        if (!empty($daftarBerkas) && is_array($daftarBerkas)) {
            foreach ($daftarBerkas as $berkas) {
                BerkasLampiran::simpanBerkas($idPengajuan, $berkas, "ReferensiInternal");
            }
        }

        // 3. Tampilkan notifikasi keberhasilan pengajuan pada antarmuka
        DialogPreviewRequest::tampilNotifikasiBerhasil("Dalam Antrian");

        return $idPengajuan;
    }

    // PC-CPR-03
    // Membatalkan pratinjau dan kembali ke formulir pengisian
    public function kirimBack() {

        // 1. Tutup modal dialog pratinjau
        DialogPreviewRequest::tutupPreview();

        // 2. Tampilkan kembali formulir pengisian request
        FormRequestInternal::tampilFormRequest();
    }

    // PC-CPR-04
    // Pengecekan batas waktu pratinjau (batal otomatis saat waktu habis)
    public function cekCountdown($sisaWaktu) {

        // Memeriksa apakah waktu toleransi 180 detik telah habis
        if ($sisaWaktu <= 0) {
            DialogPreviewRequest::tutupPreview();
            DialogPreviewRequest::tampilNotifWaktuHabis();
            return true;
        } else {
            return false;
        }
    }
}
?>
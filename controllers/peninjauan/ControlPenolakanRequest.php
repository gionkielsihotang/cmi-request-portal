<?php
require_once 'Pengajuan.php';
require_once 'Notifikasi.php';
require_once 'DialogAlasanPenolakan.php';
require_once 'HalamanDetailRequest.php';

class ControlPenolakanRequest {

    // PC-CPLR-01
    // Membuka form alasan penolakan
    public function bukaFormPenolakan($idPengajuan) {

        // 1. Inisialisasi context pengajuan yang akan ditolak
        $idPengajuanAktif = $idPengajuan;

        // 2. Buka antarmuka pop-up alasan penolakan
        DialogAlasanPenolakan::tampilFormAlasan();
    }

    // PC-CPLR-02
    // Membatalkan penolakan request
    public function batalkanPenolakan($idPengajuan) {

        // 1. Tutup modal pop-up alasan penolakan
        DialogAlasanPenolakan::tutupPopup();

        // 2. Kembalikan tampilan ke halaman detail request
        HalamanDetailRequest::tampilDetailRequest();

        // 3. Tidak ada perubahan data pada entitas Pengajuan
    }

    // PC-CPLR-03
    // Mengirim penolakan request beserta alasan
    public function kirimPenolakan($idPengajuan, $alasan) {

        // 1. Validasi alasan penolakan (self-call)
        $valid = $this->validasiAlasan($alasan);

        // 2. Evaluasi hasil validasi
        if ($valid === false) {
            // Alur Alternatif: alasan kosong, proses dihentikan
            return;
        } else {
            // Alur Normal: ubah status dan simpan alasan
            Pengajuan::updateStatus($idPengajuan, 'Ditolak');
            Pengajuan::simpanAlasanPenolakan($idPengajuan, $alasan);

            // 3. Ambil email pengaju lalu kirim email penolakan
            $email = Pengajuan::getEmailPengaju($idPengajuan);
            Notifikasi::kirimEmailPenolakan($email, $alasan);

            // 4. Tutup pop-up alasan penolakan
            DialogAlasanPenolakan::tutupPopup();
        }
    }

    // PC-CPLR-04
    // Memvalidasi alasan penolakan
    public function validasiAlasan($alasan) {

        // 1. Periksa teks alasan tidak boleh kosong atau hanya whitespace
        if ($alasan === null || trim($alasan) === '') {
            // Alur Alternatif: alasan tidak valid
            DialogAlasanPenolakan::tampilPeringatanAlasanKosong();
            return false;
        } else {
            // Alur Normal: alasan valid
            return true;
        }
    }
}
?>

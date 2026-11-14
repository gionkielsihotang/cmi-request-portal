<?php
/**
 * SUBBAB 4.2 PROGRAM IMPLEMENTATION CODE
 * ID Kode     : [REV - SC03]
 * Nama Kelas  : ControlRevisiDesain
 * Acuan SDD   : Bab 3.4 Package Desain & Bab 3.6 (PC-CRD-01 s/d PC-CRD-02)
 * Penyusun    : Revalia Rahmadani
 */

require_once __DIR__ . '/../../entities/Pengajuan.php';
require_once __DIR__ . '/../../entities/CatatanRevisi.php';
require_once __DIR__ . '/../../entities/PenaltiKementerian.php';
require_once __DIR__ . '/../../boundaries/FormRevisiDesain.php';
require_once __DIR__ . '/../../boundaries/DialogPenalti.php';

class ControlRevisiDesain
{

    // PC-CRD-01
    // Mengajukan revisi desain
    public function kirimRevisi($idPengajuan, $kementerianId, $teksRevisi)
    {

        // 1. Validasi catatan revisi tidak boleh kosong atau hanya whitespace
        if ($teksRevisi === null || trim($teksRevisi) === "") {
            // Alur Alternatif: catatan revisi kosong
            FormRevisiDesain::tampilPeringatanKosong("Catatan revisi wajib diisi");
            return false;
        }

        // 2. Cek apakah batas waktu verifikasi 1 jam sudah terlewati
        $lewatBatas = Pengajuan::cekBatasWaktuVerifikasi($idPengajuan);

        if ($lewatBatas === true) {
            // Alur Alternatif: revisi diberikan setelah 1 jam, minta konfirmasi penalti
            DialogPenalti::tampilDialogPenalti();
            return false;
        }

        // 3. Alur Normal: revisi masih dalam batas waktu
        $idRevisi = CatatanRevisi::simpanCatatanRevisi($idPengajuan, $teksRevisi);

        // 4. Perbarui status pengajuan
        Pengajuan::updateStatus($idPengajuan, "Dalam Revisi Desain");

        // 5. Tampilkan notifikasi sukses
        FormRevisiDesain::tampilNotifikasiRevisiSukses("Dalam Revisi Desain");

        return true;
    }

    // PC-CRD-02
    // Mengirim revisi dengan penalti keterlambatan
    public function konfirmasiKirimDenganPenalti($idPengajuan, $kementerianId, $teksRevisi)
    {

        // 1. Catat pengurangan poin kementerian akibat keterlambatan (5 poin)
        PenaltiKementerian::catatPenguranganPoin($kementerianId, $idPengajuan, 5);

        // 2. Simpan catatan revisi
        $idRevisi = CatatanRevisi::simpanCatatanRevisi($idPengajuan, $teksRevisi);

        // 3. Perbarui status pengajuan
        Pengajuan::updateStatus($idPengajuan, "Dalam Revisi Desain");

        // 4. Tampilkan notifikasi sukses beserta info pengurangan poin
        FormRevisiDesain::tampilNotifikasiRevisiSukses("Dalam Revisi Desain");

        return true;
    }
}
?>

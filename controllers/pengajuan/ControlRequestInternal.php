<?php
/**
 * SUBBAB 4.2 PROGRAM IMPLEMENTATION CODE
 * ID Kode     : [GYS-SC01]
 * Nama Kelas  : ControlRequestInternal
 * Acuan SDD   : Bab 3.4 Package Pengajuan & Bab 3.6 (PC-CRI-01, PC-CRI-02)
 * Penyusun    : Gion Yehezkiel Sihotang
 */

require_once __DIR__ . '/../../entities/Pengajuan.php';
require_once __DIR__ . '/../../entities/BerkasLampiran.php';
require_once __DIR__ . '/../../boundaries/FormRequestInternal.php';
require_once __DIR__ . '/ControlPreviewRequest.php';

class ControlRequestInternal
{

    // PC-CRI-01
    // Membuka formulir pengajuan publikasi internal
    public function bukaFormRequest()
    {
        // Menginstruksikan antarmuka formulir internal untuk dibuka dan siap menerima input
        FormRequestInternal::tampilFormRequest();
    }

    // PC-CRI-02
    // Memvalidasi data formulir dan berkas lampiran request internal
    // Target White Box Testing: Statement Coverage, Branch Coverage, & Basis Path Testing
    public function kirimDataRequest($dataForm, $daftarBerkas = [])
    {
        // [Node 1] // [Statement 1] Validasi kelengkapan isian kolom wajib ke entitas Pengajuan
        $lengkap = Pengajuan::cekKelengkapanForm($dataForm);

        // [Node 1] // [Statement 2] Evaluasi hasil pengecekan kelengkapan form
        $isLengkap = ($lengkap === true) || (is_array($lengkap) && !empty($lengkap['valid']));

        // [Node 2 (Predicate D1)] // [Statement 3] lengkap == FALSE ?
        if (!$isLengkap) {
            // [Node 3] // [Statement 4] Tampilkan indikator error pada form
            FormRequestInternal::tampilIndikatorError();

            // [Node 3 -> Node 8] // [Statement 5] Terminasi return false menuju titik keluar
            return false;
        }

        // [Node 4 (Predicate D2)] // [Statement 6] Loop FOR EACH berkas (Apakah ada berkas berikutnya?)
        if (!empty($daftarBerkas) && is_array($daftarBerkas)) {
            foreach ($daftarBerkas as $berkas) {
                // [Node 5] // [Statement 7 & 8] Validasi format, ukuran, dan status upload berkas
                $valid = BerkasLampiran::validasiBerkas($berkas);
                $isValidBerkas = ($valid === true) || (is_array($valid) && !empty($valid['valid']));

                // [Node 5 (Predicate D3)] // [Statement 9] valid == FALSE ?
                if (!$isValidBerkas) {
                    // [Node 6] // [Statement 10] Tampilkan peringatan berkas pada form
                    FormRequestInternal::tampilPeringatanBerkas("Format atau ukuran berkas tidak sesuai ketentuan");

                    // [Node 6 -> Node 8] // [Statement 11] Terminasi return false menuju titik keluar
                    return false;
                }
                // Jika valid (valid == TRUE), alur looping kembali ke [Node 4]
            }
        }

        // [Node 7] // [Statement 12] Inisialisasi controller preview request
        $controlPreview = new ControlPreviewRequest();

        // [Node 7] // [Statement 13] Teruskan seluruh data valid ke alur preview
        $controlPreview->kirimDataPreview($dataForm, $daftarBerkas);

        // [Node 7 -> Node 8] // [Statement 14] Terminasi return true menuju titik keluar
        return true;
        // [Node 8] Titik Keluar (Exit / End)
    }
}
?>
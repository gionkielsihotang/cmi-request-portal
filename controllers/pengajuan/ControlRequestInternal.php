<?php
/**
 * SUBBAB 4.2 PROGRAM IMPLEMENTATION CODE
 * ID Kode     : [GYS-SC01]
 * Nama Kelas  : ControlRequestInternal
 * Acuan SDD   : Bab 3.4 Package Pengajuan & Bab 3.6 (PC-CRI-01, PC-CRI-02)
 * Penyusun    : Gion Yehezkiel Sihotang
 */

require_once 'Pengajuan.php';
require_once 'BerkasLampiran.php';
require_once 'FormRequestInternal.php';
require_once 'ControlPreviewRequest.php';

class ControlRequestInternal {

    // PC-CRI-01
    // Membuka formulir pengajuan publikasi internal
    public function bukaFormRequest() {
        // Menginstruksikan antarmuka formulir internal untuk dibuka dan siap menerima input
        FormRequestInternal::tampilFormRequest();
    }

    // PC-CRI-02
    // Memvalidasi data formulir dan berkas lampiran request internal
    public function kirimDataRequest($dataForm, $daftarBerkas = []) {

        // 1. Validasi kelengkapan isian kolom wajib ke entitas Pengajuan
        $lengkap = Pengajuan::cekKelengkapanForm($dataForm);

        // 2. Evaluasi hasil pengecekan kelengkapan form
        if ($lengkap === false) {
            // Alur Alternatif: Kolom wajib masih ada yang kosong
            FormRequestInternal::tampilIndikatorError();
            return false;
        }

        // 3. Validasi berkas lampiran ke entitas BerkasLampiran jika ada berkas yang diunggah
        if (!empty($daftarBerkas) && is_array($daftarBerkas)) {
            foreach ($daftarBerkas as $berkas) {
                $valid = BerkasLampiran::validasiBerkas($berkas);
                if ($valid === false) {
                    // Alur Alternatif: Berkas tidak sesuai ketentuan (format bukan png/jpg/pdf/docx atau > 5MB/10MB)
                    FormRequestInternal::tampilPeringatanBerkas("Format atau ukuran berkas tidak sesuai ketentuan");
                    return false;
                }
            }
        }

        // 4. Alur Normal: Teruskan seluruh data valid ke ControlPreviewRequest
        $controlPreview = new ControlPreviewRequest();
        $controlPreview->kirimDataPreview($dataForm, $daftarBerkas);

        return true;
    }
}
?>
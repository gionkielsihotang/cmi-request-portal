<?php
require_once 'Pengajuan.php';
require_once 'BerkasLampiran.php';
require_once 'HalamanDaftarPermohonan.php';
require_once 'HalamanDetailRequest.php';

class ControlPeninjauanRequest {

    // PC-CPJR-01
    // Mengambil daftar request masuk berstatus 'Menunggu Peninjauan'
    public function ambilDaftarRequest() {

        // 1. Query seluruh request masuk berstatus 'Menunggu Peninjauan'
        $daftarRequest = Pengajuan::getSemuaPengajuan();

        // 2. Evaluasi keberadaan data request
        if ($daftarRequest === null || empty($daftarRequest)) {
            // Alur Alternatif: belum ada request masuk
            $daftarRequest = array();
        } else {
            // Alur Normal: render daftar request untuk staf CMI
            HalamanDaftarPermohonan::tampilDaftarRequest();
        }

        return $daftarRequest;
    }

    // PC-CPJR-02
    // Mengambil detail request beserta berkas lampiran
    public function ambilDetailRequest($idPengajuan) {

        // 1. Ambil data pengajuan berdasarkan idPengajuan
        $pengajuan = Pengajuan::getById($idPengajuan);

        // 2. Evaluasi keberadaan data pengajuan
        if ($pengajuan === null) {
            // Alur Alternatif: data tidak ditemukan
            return null;
        }

        // 3. Ambil berkas lampiran milik pengajuan
        $lampiran = BerkasLampiran::getByPengajuan($idPengajuan);

        // 4. Susun bundel data pengajuan dan lampiran
        $detailRequest = array();
        $detailRequest['pengajuan'] = $pengajuan;
        $detailRequest['lampiran'] = $lampiran;

        // 5. Perbarui status permohonan menjadi 'Ditinjau'
        Pengajuan::updateStatus($idPengajuan, 'Ditinjau');

        // 6. Render halaman detail request
        HalamanDetailRequest::tampilDetailRequest();

        return $detailRequest;
    }
}
?>

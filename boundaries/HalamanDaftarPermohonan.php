<?php
// gion
class HalamanDaftarPermohonan
{
    public function bukaDaftarPermohonan(): array
    {
        return ['redirect' => '../views/admin/daftar-permohonan.html'];
    }

    public function tampilDaftarRequest(array $daftarRequest): array
    {
        return [
            'success' => true,
            'total' => count($daftarRequest),
            'data' => $daftarRequest
        ];
    }

    public function pilihRequest(string $idPengajuan): array
    {
        return ['redirect' => "../views/admin/detail-review.html?id={$idPengajuan}"];
    }
}
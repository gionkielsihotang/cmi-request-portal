<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> HalamanDashboardCMI (Package Peninjauan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class HalamanDashboardCMI
{
    public function tampilDashboard(array $daftarRequest, string $filterStatus = 'Semua'): array
    {
        return [
            'success' => true,
            'filter' => $filterStatus,
            'data' => $daftarRequest
        ];
    }

    public function pilihRequestDitinjau(string $idPengajuan): array
    {
        return ['redirect' => "../views/admin/detail-review.html?id={$idPengajuan}"];
    }
}
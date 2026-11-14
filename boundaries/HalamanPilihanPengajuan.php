<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> HalamanPilihanPengajuan (Package Pengajuan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class HalamanPilihanPengajuan
{
    public function tampilHalamanPilihan(): array
    {
        return ['redirect' => '../views/pengaju/pilihan-pengajuan.html'];
    }
}
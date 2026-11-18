<?php
require_once __DIR__ . '/config/koneksi.php';

// Role Switcher / Direct Login Demo
if (isset($_GET['role'])) {
    if ($_GET['role'] === 'pengaju') {
        $_SESSION['user'] = [
            'idPengaju' => 'PGJ-001',
            'kementerianId' => 'KEM-01',
            'nama' => 'Gion Yehezkiel Sihotang',
            'nim' => '245150201111001',
            'email' => 'gion@student.ub.ac.id',
            'asalKementerian' => 'Kementerian Human Capital'
        ];
        header("Location: views/pengaju/status-pengajuan.php");
        exit;
    } elseif ($_GET['role'] === 'admin') {
        $_SESSION['admin'] = [
            'idCMI' => 'CMI-001',
            'namaAdmin' => 'Adinda Staf CMI',
            'accountAdmin' => 'cmi_admin@filkom.ub.ac.id'
        ];
        header("Location: views/admin/dashboard-cmi.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Request Publikasi &amp; Riset — CMI BEM FILKOM UB</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/portal.css">
    <style>
        .hero-box {
            text-align: center;
            padding: 48px 24px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(27, 58, 158, 0.06);
            margin: 40px auto;
            max-width: 860px;
            border: 1px solid #e2e8f0;
        }

        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 36px;
            text-align: left;
        }

        .role-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s ease;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .role-card:hover {
            border-color: #1b3a9e;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(27, 58, 158, 0.1);
        }

        .badge-role {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 99px;
            display: inline-block;
            margin-bottom: 12px;
        }


    </style>
</head>

<body>
    <nav>
        <div class="brand">
            <img src="assets/img/logo cmi.jpeg" alt="Logo CMI" class="logo">
            CMI BEM FILKOM UB
        </div>
        <div class="right">
            <span class="pill">Sistem Informasi Publikasi &amp; Riset</span>
        </div>
    </nav>

    <div class="wrap">
        <div class="hero-box">
            <img src="assets/img/logo cmi.jpeg" alt="Logo CMI"
                style="width: 72px; height: 72px; border-radius: 14px; margin-bottom: 16px; object-fit: cover;">
            <h1 style="color: #1b3a9e; font-size: 28px; margin-bottom: 8px;">Portal Request Publikasi &amp; Riset</h1>
            <p style="color: #64748b; font-size: 15px; max-width: 600px; margin: 0 auto;">
                Sistem informasi terintegrasi Biro Center of Media and Information (CMI) bersama seluruh Kementerian dan
                Lembaga BEM FILKOM Universitas Brawijaya.
            </p>

            <div class="role-cards">
                <!-- Role 1: Pengaju Internal -->
                <div class="role-card">
                    <div>
                        <span class="badge-role" style="background: #e0f2fe; color: #0369a1;">Role Pengaju
                            Internal</span>
                        <h2 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">Kementerian / Biro Pengaju</h2>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px;">
                            Akun demo: <strong>Gion Yehezkiel Sihotang</strong> (Kementerian Human Capital). Ajukan
                            request publikasi feeds/story, pantau status pengajuan, verifikasi desain hasil CMI, dan
                            ajukan revisi.
                        </p>
                    </div>
                    <a href="index.php?role=pengaju" class="btn primary"
                        style="text-align: center; text-decoration: none; padding: 12px; display: block;">
                        Masuk sebagai Pengaju &rarr;
                    </a>
                </div>

                <!-- Role 2: Admin CMI -->
                <div class="role-card">
                    <div>
                        <span class="badge-role" style="background: #fef3c7; color: #92400e;">Role Biro CMI</span>
                        <h2 style="font-size: 18px; color: #0f172a; margin-bottom: 8px;">Staf &amp; Desainer CMI</h2>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px;">
                            Akun demo: <strong>Adinda Staf CMI</strong>. Tinjau request masuk, periksa kesesuaian SOP
                            H-3, terima &amp; proses request, tolak dengan alasan, serta unggah desain final untuk
                            diverifikasi.
                        </p>
                    </div>
                    <a href="index.php?role=admin" class="btn primary"
                        style="text-align: center; text-decoration: none; padding: 12px; display: block; background: #1e293b;">
                        Masuk sebagai Admin CMI &rarr;
                    </a>
                </div>
            </div>


        </div>
    </div>

    <footer style="text-align: center; padding: 24px; color: #94a3b8; font-size: 12px;">
        &copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya
    </footer>
</body>

</html>
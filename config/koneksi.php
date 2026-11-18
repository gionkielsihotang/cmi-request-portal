<?php
/**
 * KONFIGURASI KONEKSI DATABASE & DEFAULT SESSION
 * File: config/koneksi.php
 * Mendukung koneksi MySQLi ($koneksi) dan PDO ($GLOBALS['pdo'])
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------
// 1. Inisialisasi Default Session (Role Pengaju & Admin CMI)
// ----------------------------------------------------------------------------
if (isset($_GET['role'])) {
    $_SESSION['active_role'] = ($_GET['role'] === 'admin') ? 'admin' : 'pengaju';
}

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'idPengaju' => 'PGJ-001',
        'nim' => '245150201111001',
        'nama' => 'Gion Yehezkiel Sihotang',
        'email' => 'gion@student.ub.ac.id',
        'kementerianId' => 'KEM-01',
        'asalKementerian' => 'Kementerian Human Capital',
        'totalPoinPenalti' => 0
    ];
}

if (!isset($_SESSION['admin'])) {
    $_SESSION['admin'] = [
        'idCMI' => 'CMI-001',
        'namaAdmin' => 'Adinda Staf CMI',
        'accountAdmin' => 'cmi_admin@filkom.ub.ac.id'
    ];
}

// ----------------------------------------------------------------------------
// 2. Parameter Koneksi Database MySQL
// ----------------------------------------------------------------------------
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'cmi_portal';
$db_port = 3306;

$koneksi = null;
$pdo = null;

try {
    // Hubungkan via MySQLi
    mysqli_report(MYSQLI_REPORT_OFF);
    $koneksi = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

    // Jika database belum ada, coba buat otomatis
    if (!$koneksi && mysqli_connect_errno() === 1049) {
        $init_conn = @mysqli_connect($db_host, $db_user, $db_pass, '', $db_port);
        if ($init_conn) {
            @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4");
            mysqli_close($init_conn);
            $koneksi = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

            // Eksekusi skema awal jika file schema_dan_seed.sql tersedia
            $seed_file = __DIR__ . '/../database/schema_dan_seed.sql';
            if ($koneksi && file_exists($seed_file)) {
                $sql_seed = file_get_contents($seed_file);
                @mysqli_multi_query($koneksi, $sql_seed);
                while (@mysqli_next_result($koneksi)) {
                    ;
                } // Bersihkan buffer multi query
            }
        }
    }

    if ($koneksi) {
        mysqli_set_charset($koneksi, 'utf8mb4');
    }

    // Hubungkan juga via PDO untuk backward compatibility dengan class di entities/
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $GLOBALS['pdo'] = $pdo;
    } catch (Throwable $e) {
        $pdo = null;
        $GLOBALS['pdo'] = null;
    }

} catch (Throwable $e) {
    $koneksi = null;
    $pdo = null;
    $GLOBALS['pdo'] = null;
}

// ----------------------------------------------------------------------------
// 3. Helper Functions (Query Aman)
// ----------------------------------------------------------------------------
function getDbConnection()
{
    global $koneksi;
    return $koneksi;
}

function escapeString($str)
{
    global $koneksi;
    if ($koneksi && is_string($str)) {
        return mysqli_real_escape_string($koneksi, $str);
    }
    return addslashes($str ?? '');
}


/**
 * SCRIPT FRONTEND ADMIN CMI - BEM FILKOM UB
 * File: assets/js/admin.js
 * Bertanggung jawab khusus untuk interaktivitas UI:
 * - Filter chip status tabel client-side (Semua, Waiting, Ditinjau)
 * - Pop-up modal konfirmasi proses request
 * - Pop-up modal alasan penolakan request
 * - Tidak menggunakan fetch() atau window.location.href (alur server-side PHP POST)
 */

// ----------------------------------------------------------------------------
// 1. Filter Chip Status pada Dashboard CMI (Semua, Waiting, Ditinjau)
// ----------------------------------------------------------------------------
function initDashboardFilter() {
    const filterChips = document.querySelectorAll('#filterStatus .f, [data-filter], [data-filter-status]');
    const tableRows = document.querySelectorAll('#daftarRequest tr[data-status], tbody tr[data-status]');

    if (!filterChips.length || !tableRows.length) return;

    filterChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            filterChips.forEach((c) => c.classList.remove('on', 'active'));
            chip.classList.add('on', 'active');

            const targetStatus = (chip.dataset.filter || chip.dataset.filterStatus || 'Semua').toLowerCase();

            tableRows.forEach((row) => {
                const rowStatus = (row.dataset.status || '').toLowerCase();
                if (targetStatus === 'semua' || rowStatus.includes(targetStatus)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
}

// ----------------------------------------------------------------------------
// 2. Modal Konfirmasi & Penolakan pada Detail Review (UI-11 & UI-12)
// ----------------------------------------------------------------------------
function initDetailReviewModals() {
    // Modal Konfirmasi Proses
    const btnProses = document.getElementById('btnProsesRequest');
    const modalProses = document.getElementById('modalKonfirmasiProses');
    const btnBatalProses = document.getElementById('btnBatalProses');

    if (btnProses && modalProses) {
        btnProses.addEventListener('click', (e) => {
            e.preventDefault();
            modalProses.hidden = false;
            modalProses.classList.add('active');
            modalProses.style.display = 'flex';
        });
    }

    if (btnBatalProses && modalProses) {
        btnBatalProses.addEventListener('click', () => {
            modalProses.hidden = true;
            modalProses.classList.remove('active');
            modalProses.style.display = 'none';
        });
    }

    // Modal Alasan Penolakan
    const btnTolak = document.getElementById('btnTolakRequest');
    const modalTolak = document.getElementById('modalAlasanPenolakan');
    const btnCancelTolak = document.getElementById('btnCancelTolak');
    const textareaAlasan = document.getElementById('textareaAlasanPenolakan');
    const alertTolakError = document.getElementById('alertPenolakanError');

    if (btnTolak && modalTolak) {
        btnTolak.addEventListener('click', (e) => {
            e.preventDefault();
            if (alertTolakError) alertTolakError.style.display = 'none';
            if (textareaAlasan) textareaAlasan.classList.remove('is-error');

            modalTolak.hidden = false;
            modalTolak.classList.add('active');
            modalTolak.style.display = 'flex';
        });
    }

    if (btnCancelTolak && modalTolak) {
        btnCancelTolak.addEventListener('click', () => {
            modalTolak.hidden = true;
            modalTolak.classList.remove('active');
            modalTolak.style.display = 'none';
        });
    }
}

// ----------------------------------------------------------------------------
// INISIALISASI SAAT DOM SIAP
// ----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initDashboardFilter();
    initDetailReviewModals();
});

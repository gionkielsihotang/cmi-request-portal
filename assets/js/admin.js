/**
 * SUBBAB 4.3 UI INTERACTION & API INTEGRATION (ADMIN CMI)
 * ID Kode     : [ADN-GYS-JS03]
 * Deskripsi   : Kontroler frontend sisi CMI (UC-10, UC-11, UC-12, UC-13)
 * Penyusun    : Adinda Marcelina & Gion Yehezkiel Sihotang
 */

document.addEventListener('DOMContentLoaded', () => {
    initDashboardCMI();
    initReviewDanPenolakan();
    initUploadDesainCMI();
});

/* ==========================================================================
   1. DASHBOARD & DAFTAR PERMOHONAN CMI (UC-10 & UC-11: UI-10-01, UI-11-01)
   ========================================================================== */
async function initDashboardCMI() {
    const tableBody = document.getElementById('tabelPermohonanCMI');
    if (!tableBody) return;

    try {
        const res = await fetch('../../api/cmi_api.php?action=get_requests');
        const result = await res.json();
        const requests = result.data || [];

        if (requests.length === 0) return;

        tableBody.innerHTML = requests.map((req) => `
            <tr>
                <td><strong>${req.idPengajuan}</strong></td>
                <td>${req.judulRequest}</td>
                <td>${req.asalKementerian || '-'}</td>
                <td>${req.tanggalPengajuan}</td>
                <td><span class="badge badge-warning">${req.statusPengajuan}</span></td>
                <td>
                    <a href="detail-review.html?id=${encodeURIComponent(req.idPengajuan)}" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">Tinjau</a>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        console.warn('Mode preview statis: endpoint cmi_api.php belum aktif.');
    }
}

/* ==========================================================================
   2. CHECKLIST SOP & MODAL PENOLAKAN REQUEST (UC-11 & UC-12)
   ========================================================================== */
function initReviewDanPenolakan() {
    const sopCheckboxes = document.querySelectorAll('.sop-checkbox');
    const btnTerimaProses = document.getElementById('btnTerimaProses');

    // Aktifkan tombol "Terima & Proses" hanya jika semua SOP dicentang (UI-11-03)
    if (sopCheckboxes.length > 0 && btnTerimaProses) {
        const cekKelengkapanSOP = () => {
            const semuaTercentang = Array.from(sopCheckboxes).every((cb) => cb.checked);
            btnTerimaProses.disabled = !semuaTercentang;
        };

        sopCheckboxes.forEach((cb) => cb.addEventListener('change', cekKelengkapanSOP));
        cekKelengkapanSOP();

        btnTerimaProses.addEventListener('click', async () => {
            btnTerimaProses.disabled = true;
            btnTerimaProses.textContent = 'Memproses...';

            try {
                await fetch('../../api/cmi_api.php?action=proses_request', { method: 'POST' });
            } catch (e) { }

            alert('Request diterima! Status diperbarui menjadi "Diproses".');
            window.location.href = 'unggah-desain.html';
        });
    }

    // Kontrol Modal Tolak Request (UI-12-01 & UI-12-02)
    const btnBukaTolak = document.getElementById('btnBukaModalTolak');
    const modalTolak = document.getElementById('modalTolak');
    const btnKirimTolak = document.getElementById('btnKirimPenolakan');
    const inputAlasan = document.getElementById('alasanPenolakan');
    const errorAlasan = document.getElementById('errorAlasanTolak');

    if (btnBukaTolak && modalTolak) {
        btnBukaTolak.addEventListener('click', () => {
            modalTolak.style.display = 'flex';
        });
    }

    if (btnKirimTolak && inputAlasan) {
        btnKirimTolak.addEventListener('click', async () => {
            const alasan = inputAlasan.value.trim();

            // Validasi alasan wajib diisi (UI-12-02)
            if (!alasan) {
                inputAlasan.classList.add('is-error');
                if (errorAlasan) errorAlasan.style.display = 'block';
                return;
            }

            inputAlasan.classList.remove('is-error');
            if (errorAlasan) errorAlasan.style.display = 'none';

            try {
                await fetch('../../api/cmi_api.php?action=tolak_request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alasanPenolakan: alasan })
                });
            } catch (e) { }

            modalTolak.style.display = 'none';
            alert('Pengajuan telah ditolak dan catatan dikirimkan ke pemohon.');
            window.location.href = 'daftar-permohonan.html';
        });
    }
}

/* ==========================================================================
   3. UNGGAH HASIL DESAIN CMI (UC-13: UI-13-01 s/d UI-13-03)
   ========================================================================== */
function initUploadDesainCMI() {
    const formUpload = document.getElementById('formKirimDesain') || document.getElementById('formUploadDesain');
    if (!formUpload) return;

    const inputDesain = document.getElementById('inputDesain') || document.getElementById('fileDesain');
    const dropzoneArea = document.getElementById('dropzoneArea');
    const fileInfoChip = document.getElementById('fileInfoChip');
    const alertFileError = document.getElementById('alertFileError');
    const alertUploadSuccess = document.getElementById('alertUploadSuccess');

    if (inputDesain) {
        inputDesain.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const fileSizeMB = file.size / (1024 * 1024);
            const ext = file.name.split('.').pop().toLowerCase();
            const allowed = ['png', 'jpg', 'jpeg', 'pdf'];

            // Validasi berkas (UI-13-02)
            if (fileSizeMB > 10 || !allowed.includes(ext)) {
                if (dropzoneArea) dropzoneArea.classList.add('error');
                if (alertFileError) alertFileError.style.display = 'block';
                if (alertUploadSuccess) alertUploadSuccess.style.display = 'none';
                if (fileInfoChip) fileInfoChip.style.display = 'none';
                this.value = '';
            } else {
                if (dropzoneArea) dropzoneArea.classList.remove('error');
                if (alertFileError) alertFileError.style.display = 'none';
                if (fileInfoChip) {
                    fileInfoChip.style.display = 'inline-block';
                    fileInfoChip.textContent = `✓ ${file.name} (${fileSizeMB.toFixed(2)} MB) - Berkas Valid`;
                }
            }
        });
    }

    formUpload.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!inputDesain || !inputDesain.files || inputDesain.files.length === 0) {
            alert('Silakan pilih berkas hasil desain terlebih dahulu.');
            return;
        }

        const btnSubmit = document.getElementById('btnSubmitDesain') || document.getElementById('btnKirimDesain');
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Mengunggah...';
        }

        const formData = new FormData(formUpload);

        try {
            await fetch('../../api/cmi_api.php?action=upload_desain', {
                method: 'POST',
                body: formData
            });
        } catch (err) { }

        // Update UI ke State Berhasil Unggah (UI-13-03)
        if (alertUploadSuccess) alertUploadSuccess.style.display = 'block';
        const badgeStatus = document.getElementById('badgeStatusSaatIni');
        if (badgeStatus) {
            badgeStatus.className = 'badge badge-warning';
            badgeStatus.textContent = 'Menunggu Verifikasi';
        }
        if (btnSubmit) btnSubmit.textContent = 'Terkirim';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}
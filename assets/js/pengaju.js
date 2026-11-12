/**
 * SUBBAB 4.3 UI INTERACTION & API INTEGRATION (PENGAJU INTERNAL)
 * ID Kode     : [GYS-CLS-JS02]
 * Deskripsi   : Kontroler frontend sisi Pengaju (UC-02, UC-03, UC-04, UC-05, UC-06)
 * Penyusun    : Gion Yehezkiel Sihotang & Callysta Fredelina
 */

document.addEventListener('DOMContentLoaded', () => {
    initFormRequestInternal();
    initPreviewRequest();
    initStatusPengajuan();
    initVerifikasiDanRevisi();
});

/* ==========================================================================
   1. FORM REQUEST INTERNAL (UC-04: UI-04-01 s/d UI-04-03)
   ========================================================================== */
function initFormRequestInternal() {
    const formRequest = document.getElementById('formPengajuan');
    if (!formRequest) return;

    const alertBanner = document.getElementById('alertFormRequest');
    const fileInput = document.getElementById('berkasReferensi');
    const dropzone = document.getElementById('dropzoneReferensi');
    const fileLabel = document.getElementById('namaFileReferensi');

    // Validasi real-time saat memilih berkas referensi
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const validExt = ['jpg', 'jpeg', 'png', 'pdf'];
            const ext = file.name.split('.').pop().toLowerCase();
            const sizeMB = file.size / (1024 * 1024);

            if (!validExt.includes(ext) || sizeMB > 10) {
                // Alt Flow 2 (UI-04-03): Format/Ukuran tidak valid
                if (dropzone) dropzone.classList.add('error');
                tampilkanAlert(alertBanner, 'danger', 'Format berkas tidak valid atau ukuran melebihi 10 MB.');
                if (fileLabel) fileLabel.textContent = `${file.name} (${sizeMB.toFixed(1)} MB) - Tidak valid`;
                this.value = '';
            } else {
                if (dropzone) dropzone.classList.remove('error');
                if (alertBanner) alertBanner.style.display = 'none';
                if (fileLabel) fileLabel.textContent = `📄 ${file.name} (${sizeMB.toFixed(2)} MB)`;
            }
        });
    }

    // Validasi saat tombol "Next" ditekan
    formRequest.addEventListener('submit', async function (e) {
        e.preventDefault();

        const requiredFields = formRequest.querySelectorAll('[data-required="true"]');
        let isFormValid = true;

        requiredFields.forEach((field) => {
            const errorMsg = field.parentElement.querySelector('.error-message');
            if (!field.value.trim()) {
                field.classList.add('is-error');
                if (errorMsg) errorMsg.style.display = 'block';
                isFormValid = false;
            } else {
                field.classList.remove('is-error');
                if (errorMsg) errorMsg.style.display = 'none';
            }
        });

        // Alt Flow 1 (UI-04-02): Kolom wajib belum terisi
        if (!isFormValid) {
            tampilkanAlert(alertBanner, 'danger', 'Lengkapi kolom yang ditandai sebelum melanjutkan.');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        // Simpan sementara ke sessionStorage agar bisa dirender di halaman Preview
        const draftData = {
            judulRequest: document.getElementById('judulRequest')?.value.trim() || '',
            kementerian: document.getElementById('kementerian')?.value || 'Internal Resource Empowerment',
            jenisPublikasi: formRequest.querySelector('input[name="jenisPublikasi"]:checked')?.value || 'Feeds',
            tanggalPublikasi: document.getElementById('tanggalPublikasi')?.value || '',
            detailKonten: document.getElementById('detailKonten')?.value.trim() || '',
            namaBerkas: fileInput?.files[0] ? `${fileInput.files[0].name} (${(fileInput.files[0].size / (1024 * 1024)).toFixed(2)} MB)` : 'Tidak ada lampiran'
        };

        sessionStorage.setItem('draft_request_cmi', JSON.stringify(draftData));

        // Lanjut ke halaman Preview (UC-03)
        window.location.href = 'modal-preview.html';
    });
}

/* ==========================================================================
   2. PREVIEW REQUEST & TIMER 180 DETIK (UC-03: UI-03-01)
   ========================================================================== */
function initPreviewRequest() {
    const timerEl = document.getElementById('timerPreview');
    if (!timerEl) return;

    // Muat data dari sessionStorage jika tersedia
    const savedDraft = sessionStorage.getItem('draft_request_cmi');
    if (savedDraft) {
        const data = JSON.parse(savedDraft);
        setText('previewJudul', data.judulRequest);
        setText('previewKementerian', data.kementerian);
        setText('previewJenis', data.jenisPublikasi);
        setText('previewTanggal', data.tanggalPublikasi);
        setText('previewRedaksional', data.detailKonten);
        setText('previewNamaFile', `📄 ${data.namaBerkas}`);
    }

    // Aktifkan Countdown 180 detik (PC-CPR-01 & PC-CPR-04)
    if (typeof PortalTimer !== 'undefined') {
        PortalTimer.start({
            key: 'preview_timer',
            durasiDetik: 180,
            elementId: 'timerPreview',
            format: 'MM:SS',
            onTimeout: () => {
                sessionStorage.removeItem('draft_request_cmi');
                alert('Waktu pratinjau 3 menit telah habis. Pengajuan dibatalkan otomatis.');
                window.location.href = 'form-request.html';
            }
        });
    }

    const btnBack = document.getElementById('btnBackToForm');
    if (btnBack) {
        btnBack.addEventListener('click', () => {
            if (typeof PortalTimer !== 'undefined') PortalTimer.stop('preview_timer');
            window.location.href = 'form-request.html';
        });
    }

    const btnSubmit = document.getElementById('btnSubmitRequest');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', async () => {
            if (typeof PortalTimer !== 'undefined') PortalTimer.stop('preview_timer');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Mengirim...';

            const payload = JSON.parse(sessionStorage.getItem('draft_request_cmi') || '{}');

            try {
                const res = await fetch('../../api/pengaju_api.php?action=submit_request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.status === 'success') {
                    sessionStorage.removeItem('draft_request_cmi');
                    window.location.href = 'detail-pengajuan.html?status=created';
                } else {
                    alert(result.message || 'Gagal menyimpan pengajuan.');
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Submit Request';
                }
            } catch (err) {
                // Fallback mode simulasi jika dibuka tanpa server PHP
                sessionStorage.removeItem('draft_request_cmi');
                alert('Request publikasi internal telah diajukan dan masuk antrian CMI.');
                window.location.href = 'status-pengajuan.html';
            }
        });
    }
}

/* ==========================================================================
   3. PELACAKAN STATUS PENGAJUAN (UC-02: UI-02-01 & UI-02-02)
   ========================================================================== */
async function initStatusPengajuan() {
    const tableBody = document.getElementById('daftarPengajuanBody');
    if (!tableBody) return;

    const emptyState = document.getElementById('emptyState');
    const tableContainer = document.getElementById('tableContainer');

    try {
        const response = await fetch('../../api/pengaju_api.php?action=get_status');
        const result = await response.json();
        const list = result.data || [];

        if (list.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            if (tableContainer) tableContainer.style.display = 'none';
            return;
        }

        if (emptyState) emptyState.style.display = 'none';
        if (tableContainer) tableContainer.style.display = 'block';

        tableBody.innerHTML = list.map((item) => `
            <tr>
                <td><strong>${escapeHtml(item.idPengajuan)}</strong></td>
                <td>${escapeHtml(item.judulRequest)}</td>
                <td>${escapeHtml(item.jenisPublikasi || 'Feeds')}</td>
                <td>${escapeHtml(item.tanggalPengajuan)}</td>
                <td><span class="badge ${getBadgeClass(item.statusPengajuan)}">${escapeHtml(item.statusPengajuan)}</span></td>
                <td>
                    <a href="detail-pengajuan.html?id=${encodeURIComponent(item.idPengajuan)}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Detail</a>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        console.warn('API belum terhubung, menggunakan data statis HTML.');
    }
}

/* ==========================================================================
   4. VERIFIKASI DESAIN & FORM REVISI (UC-05 & UC-06)
   ========================================================================== */
function initVerifikasiDanRevisi() {
    const timerVerifEl = document.getElementById('timerVerif');
    let isTimeoutVerifikasi = false;

    if (timerVerifEl && typeof PortalTimer !== 'undefined') {
        PortalTimer.start({
            key: 'verifikasi_timer',
            durasiDetik: 3600, // 1 jam toleransi
            elementId: 'timerVerif',
            format: 'HH:MM:SS',
            onTimeout: () => {
                isTimeoutVerifikasi = true;
                const badgeTimer = document.getElementById('badgeTimerVerifikasi');
                if (badgeTimer) {
                    badgeTimer.className = 'badge badge-danger';
                    badgeTimer.textContent = '⏱️ Batas Waktu 1 Jam Habis';
                }
            }
        });
    }

    // Handle Form Revisi (UI-06-01 s/d UI-06-04)
    const formRevisi = document.getElementById('formRevisi');
    if (formRevisi) {
        const teksRevisi = document.getElementById('teksRevisi');
        const charCount = document.getElementById('charCount');
        const alertError = document.getElementById('alertError');
        const alertSuccess = document.getElementById('alertSuccess');
        const modalPenalti = document.getElementById('modalPenalti');

        if (teksRevisi) {
            teksRevisi.addEventListener('input', () => {
                if (charCount) charCount.textContent = teksRevisi.value.length;
                if (teksRevisi.value.trim() !== '') {
                    teksRevisi.classList.remove('error-border');
                    if (alertError) alertError.style.display = 'none';
                }
            });
        }

        formRevisi.addEventListener('submit', (e) => {
            e.preventDefault();
            const catatan = teksRevisi ? teksRevisi.value.trim() : '';

            // Validasi kosong (UI-06-04)
            if (!catatan) {
                if (teksRevisi) teksRevisi.classList.add('error-border');
                if (alertError) alertError.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Cek apakah melewati 1 jam (UI-06-02)
            if (isTimeoutVerifikasi && modalPenalti) {
                modalPenalti.style.display = 'flex';
            } else {
                kirimRevisiKeServer(catatan, false, alertSuccess, teksRevisi);
            }
        });
    }
}

async function kirimRevisiKeServer(catatan, kenaPenalti, alertSuccessEl, textareaEl) {
    try {
        await fetch('../../api/verifikasi_api.php?action=kirim_revisi', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ teksRevisi: catatan, kenaPenalti })
        });
    } catch (e) {
        // Tetap update UI saat mode offline/testing
    }

    if (alertSuccessEl) alertSuccessEl.style.display = 'block';
    if (textareaEl) textareaEl.disabled = true;
    const btnKirim = document.getElementById('btnKirimRevisi');
    if (btnKirim) btnKirim.disabled = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ==========================================================================
   HELPER UTILITIES
   ========================================================================== */
function tampilkanAlert(element, type, message) {
    if (!element) return;
    element.className = `alert alert-${type}`;
    element.textContent = message;
    element.style.display = 'flex';
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el && value) el.textContent = value;
}

function getBadgeClass(status) {
    const s = (status || '').toLowerCase();
    if (s.includes('fix') || s.includes('selesai')) return 'badge-success';
    if (s.includes('tolak')) return 'badge-danger';
    if (s.includes('proses') || s.includes('tinjau')) return 'badge-process';
    return 'badge-warning';
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
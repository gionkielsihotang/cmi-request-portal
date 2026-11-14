const PENGAJU_API_URL = '../../api/pengaju_api.php';
const VERIFIKASI_API_URL = '../../api/verifikasi_api.php';

// ----------------------------------------------------------------------------
// 1. UI-02-01 & UI-02-02: Halaman Status Pengajuan & Empty State
// ----------------------------------------------------------------------------
function initStatusPengajuan() {
    const tabelBody = document.getElementById('tabelRiwayatBody');
    const emptyState = document.getElementById('emptyState');
    const btnBuat = document.getElementById('btnBuatPengajuan');

    if (btnBuat) {
        btnBuat.addEventListener('click', () => {
            window.location.href = 'pilihan-pengajuan.html';
        });
    }

    if (!tabelBody && !emptyState) return;

    function bindRowNavigation() {
        document.querySelectorAll('[data-detail-id]').forEach((el) => {
            el.addEventListener('click', () => {
                const id = el.dataset.detailId;
                const status = (el.dataset.status || '').toLowerCase();
                if (!id) return;

                if (status.includes('verifikasi')) {
                    window.location.href = `verifikasi-desain.html?id=${encodeURIComponent(id)}`;
                } else {
                    window.location.href = `detail-pengajuan.html?id=${encodeURIComponent(id)}`;
                }
            });
        });
    }

    bindRowNavigation();

    // Sinkronkan dengan API jika backend list_pengajuan tersedia
    fetch(`${PENGAJU_API_URL}?action=list_pengajuan&idPengaju=PGJ-001`)
        .then((res) => res.json())
        .then((result) => {
            if (!result.success || !Array.isArray(result.data)) return;

            if (result.data.length === 0) {
                if (tabelBody) tabelBody.closest('table').style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
                return;
            }

            if (emptyState) emptyState.style.display = 'none';
            if (tabelBody) {
                tabelBody.closest('table').style.display = 'table';
                tabelBody.innerHTML = result.data.map((item) => `
                    <tr data-detail-id="${item.idPengajuan}" data-status="${item.statusPengajuan}" style="cursor:pointer;">
                        <td><strong>${item.idPengajuan}</strong></td>
                        <td>${item.judulRequest}</td>
                        <td>${item.tanggalPengajuan}</td>
                        <td><span class="badge">${item.statusPengajuan}</span></td>
                        <td><button type="button" class="btn btn-sm" data-detail-id="${item.idPengajuan}" data-status="${item.statusPengajuan}">Detail &rarr;</button></td>
                    </tr>
                `).join('');
                bindRowNavigation();
            }
        })
        .catch(() => {
            // Tetap gunakan data statis pada HTML jika endpoint list belum aktif
        });
}

// ----------------------------------------------------------------------------
// 2. UI-02-03: Halaman Pilihan Jenis Pengajuan
// ----------------------------------------------------------------------------
function initPilihanPengajuan() {
    const btnInternal = document.getElementById('btnPilihInternal') || document.querySelector('[data-jenis="internal"]');
    const btnMedpart = document.getElementById('btnPilihMedpart') || document.querySelector('[data-jenis="medpart"]');

    if (btnInternal) {
        btnInternal.addEventListener('click', () => {
            window.location.href = 'form-request.html';
        });
    }

    if (btnMedpart) {
        btnMedpart.addEventListener('click', () => {
            alert('Alur Media Partner sedang dalam pengembangan. Silakan pilih Request Publikasi Internal.');
        });
    }
}

// ----------------------------------------------------------------------------
// 3. UI-02-04 & UI-03-02: Detail Pengajuan & Banner Notifikasi Berhasil
// ----------------------------------------------------------------------------
async function initDetailPengajuan() {
    const params = new URLSearchParams(window.location.search);
    const idPengajuan = params.get('id');
    const statusParam = params.get('status');

    const bannerBerhasil = document.getElementById('bannerRequestBerhasil');
    if (bannerBerhasil && statusParam === 'created') {
        bannerBerhasil.style.display = 'block';
    }

    const btnLihatStatus = document.getElementById('btnLihatStatus');
    if (btnLihatStatus) {
        btnLihatStatus.addEventListener('click', () => {
            window.location.href = 'status-pengajuan.html';
        });
    }

    const btnVerifikasi = document.getElementById('btnKeVerifikasi');
    if (btnVerifikasi && idPengajuan) {
        btnVerifikasi.addEventListener('click', () => {
            window.location.href = `verifikasi-desain.html?id=${encodeURIComponent(idPengajuan)}`;
        });
    }

    if (!idPengajuan) return;

    try {
        const res = await fetch(`${PENGAJU_API_URL}?action=get_detail&id=${encodeURIComponent(idPengajuan)}`);
        const result = await res.json();
        if (!result.success || !result.data) return;

        const d = result.data;
        const fieldMap = {
            detailIdPengajuan: d.idPengajuan,
            detailJudul: d.judulRequest,
            detailTanggal: d.tanggalPengajuan,
            detailKonten: d.detailKonten,
            detailStatus: d.statusPengajuan,
            detailCatatanKelengkapan: d.catatanKelengkapan || '-',
            detailAlasanPenolakan: d.alasanPenolakan || '-'
        };

        Object.entries(fieldMap).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        });
    } catch (err) {
        console.warn('Menggunakan tampilan default detail pengajuan:', err);
    }
}

// ----------------------------------------------------------------------------
// 4. UI-04-01 s/d UI-04-03 & UI-03-01: Form Request Internal + Modal Preview 180s
// ----------------------------------------------------------------------------
function initFormRequestInternal() {
    const form = document.getElementById('formRequestInternal') || document.querySelector('form[data-page="form-request"]');
    if (!form) return;

    const inputJudul = form.querySelector('#judulRequest, [name="judulRequest"], [name="judul"]');
    const inputKementerian = form.querySelector('#kementerianId, [name="kementerianId"], [name="kementerian"]');
    const inputTanggal = form.querySelector('#tanggalPublikasi, [name="tanggalPublikasi"], [name="tanggal"]');
    const inputRedaksional = form.querySelector('#detailKonten, [name="detailKonten"], [name="redaksional"]');
    const inputBerkas = form.querySelector('#berkasAcuan, input[type="file"]');
    const dropzone = form.querySelector('#dropzoneAcuan, .dropzone');

    const alertKosong = document.getElementById('alertErrorKosong');
    const alertBerkas = document.getElementById('alertErrorBerkas');
    const modalPreview = document.getElementById('modalPreviewRequest');
    const btnSubmitRequest = document.getElementById('btnSubmitRequest');
    const btnBackPreview = document.getElementById('btnBackPreview');

    let draftFormData = null;

    function resetErrors() {
        if (alertKosong) alertKosong.style.display = 'none';
        if (alertBerkas) alertBerkas.style.display = 'none';
        form.querySelectorAll('.is-error').forEach((el) => el.classList.remove('is-error'));
        if (dropzone) dropzone.classList.remove('error');
    }

    function showBanner(el, fallbackMsg) {
        if (el) {
            if (fallbackMsg) el.textContent = fallbackMsg;
            el.style.display = 'block';
        } else {
            alert(fallbackMsg);
        }
    }

    if (inputBerkas) {
        inputBerkas.addEventListener('change', () => {
            resetErrors();
            const file = inputBerkas.files[0];
            if (!file) return;

            const allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
            const ext = file.name.split('.').pop().toLowerCase();
            const maxBytes = 5 * 1024 * 1024;

            if (!allowedExt.includes(ext) || file.size > maxBytes) {
                if (dropzone) dropzone.classList.add('error');
                showBanner(alertBerkas, 'Ukuran berkas melebihi batas 5 MB atau format tidak valid (JPG/PNG/PDF).');
                inputBerkas.value = '';
            }
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        resetErrors();

        const jenisChecked = form.querySelector('[name="jenisPublikasi"]:checked, [name="jenis"]:checked');
        const judulVal = inputJudul ? inputJudul.value.trim() : '';
        const kementerianVal = inputKementerian ? inputKementerian.value.trim() : 'KEM-01';
        const jenisVal = jenisChecked ? jenisChecked.value : 'Feeds';
        const tanggalVal = inputTanggal ? inputTanggal.value.trim() : '';
        const redaksionalVal = inputRedaksional ? inputRedaksional.value.trim() : '';

        let hasEmpty = false;
        if (!judulVal) {
            if (inputJudul) inputJudul.classList.add('is-error');
            hasEmpty = true;
        }
        if (!redaksionalVal) {
            if (inputRedaksional) inputRedaksional.classList.add('is-error');
            hasEmpty = true;
        }
        if (hasEmpty) {
            showBanner(alertKosong, 'Lengkapi kolom yang ditandai.');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'validate_request');
        fd.append('idPengaju', 'PGJ-001');
        fd.append('judulRequest', judulVal);
        fd.append('kementerianId', kementerianVal);
        fd.append('jenisPublikasi', jenisVal);
        fd.append('tanggalPublikasi', tanggalVal || new Date().toISOString().slice(0, 10));
        fd.append('detailKonten', redaksionalVal);
        if (inputBerkas && inputBerkas.files[0]) {
            fd.append('berkasAcuan', inputBerkas.files[0]);
        }

        try {
            const res = await fetch(PENGAJU_API_URL, { method: 'POST', body: fd });
            const result = await res.json();

            if (!result.success) {
                if (result.errorType === 'file_error') {
                    if (dropzone) dropzone.classList.add('error');
                    showBanner(alertBerkas, result.message);
                } else {
                    showBanner(alertKosong, result.message || 'Lengkapi kolom yang ditandai.');
                }
                return;
            }

            draftFormData = fd;
            draftFormData.set('action', 'submit_request');

            const fileName = inputBerkas && inputBerkas.files[0] ? inputBerkas.files[0].name : 'Tidak ada lampiran';
            const summaryData = {
                judulRequest: judulVal,
                kementerianId: kementerianVal,
                jenisPublikasi: jenisVal,
                tanggalPublikasi: tanggalVal || '-',
                detailKonten: redaksionalVal,
                namaBerkas: fileName
            };

            if (modalPreview) {
                renderPreviewData(summaryData);
                modalPreview.classList.add('active');
                modalPreview.style.display = 'flex';
                startPreviewCountdown(modalPreview);
            } else {
                sessionStorage.setItem('cmi_preview_draft', JSON.stringify(summaryData));
                window.location.href = 'modal-preview.html';
            }
        } catch (err) {
            alert('Gagal terhubung ke server. Pastikan Apache & MySQL aktif: ' + err.message);
        }
    });

    function renderPreviewData(data) {
        Object.entries({
            prevJudul: data.judulRequest,
            prevKementerian: data.kementerianId,
            prevJenis: data.jenisPublikasi,
            prevTanggal: data.tanggalPublikasi,
            prevRedaksional: data.detailKonten,
            prevBerkas: data.namaBerkas
        }).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        });
    }

    function startPreviewCountdown(modalEl) {
        const timerEl = document.getElementById('countdownTimer') || document.getElementById('previewCountdown');
        if (typeof CMITimer !== 'undefined') {
            CMITimer.start('preview_180', 180, timerEl, {
                onTimeout: () => {
                    closePreviewModal(modalEl);
                    alert('Waktu pratinjau 180 detik telah habis. Silakan periksa kembali formulir Anda.');
                }
            });
        }
    }

    function closePreviewModal(modalEl) {
        if (typeof CMITimer !== 'undefined') CMITimer.stop('preview_180');
        if (modalEl) {
            modalEl.classList.remove('active');
            modalEl.style.display = 'none';
        }
    }

    if (btnBackPreview) {
        btnBackPreview.addEventListener('click', () => closePreviewModal(modalPreview));
    }

    if (btnSubmitRequest) {
        btnSubmitRequest.addEventListener('click', async () => {
            if (!draftFormData) return;
            btnSubmitRequest.disabled = true;
            btnSubmitRequest.textContent = 'Mengirim...';

            try {
                const res = await fetch(PENGAJU_API_URL, { method: 'POST', body: draftFormData });
                const result = await res.json();

                if (result.success) {
                    if (typeof CMITimer !== 'undefined') CMITimer.stop('preview_180');
                    window.location.href = result.redirect || `detail-pengajuan.html?id=${result.idPengajuan}&status=created`;
                } else {
                    alert(result.message || 'Gagal menyimpan pengajuan.');
                    btnSubmitRequest.disabled = false;
                    btnSubmitRequest.textContent = 'Submit Request';
                }
            } catch (err) {
                alert('Terjadi kesalahan saat menyimpan ke database: ' + err.message);
                btnSubmitRequest.disabled = false;
                btnSubmitRequest.textContent = 'Submit Request';
            }
        });
    }
}

function initStandalonePreviewPage() {
    const previewContainer = document.getElementById('standalonePreviewPage');
    if (!previewContainer) return;

    const raw = sessionStorage.getItem('cmi_preview_draft');
    const draft = raw ? JSON.parse(raw) : null;

    if (draft) {
        Object.entries({
            prevJudul: draft.judulRequest,
            prevKementerian: draft.kementerianId,
            prevJenis: draft.jenisPublikasi,
            prevTanggal: draft.tanggalPublikasi,
            prevRedaksional: draft.detailKonten,
            prevBerkas: draft.namaBerkas
        }).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        });
    }

    const timerEl = document.getElementById('countdownTimer') || document.getElementById('previewCountdown');
    if (typeof CMITimer !== 'undefined') {
        CMITimer.start('preview_standalone', 180, timerEl, {
            onTimeout: () => {
                alert('Waktu pratinjau 180 detik telah habis.');
                window.location.href = 'form-request.html';
            }
        });
    }

    const btnBack = document.getElementById('btnBackPreview');
    if (btnBack) {
        btnBack.addEventListener('click', () => {
            if (typeof CMITimer !== 'undefined') CMITimer.stop('preview_standalone');
            window.location.href = 'form-request.html';
        });
    }

    const btnSubmit = document.getElementById('btnSubmitRequest');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', async () => {
            if (!draft) {
                window.location.href = 'form-request.html';
                return;
            }
            btnSubmit.disabled = true;
            try {
                const res = await fetch(PENGAJU_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'submit_request', ...draft })
                });
                const result = await res.json();
                if (result.success) {
                    sessionStorage.removeItem('cmi_preview_draft');
                    window.location.href = result.redirect || `detail-pengajuan.html?id=${result.idPengajuan}&status=created`;
                } else {
                    alert(result.message || 'Gagal mengirim pengajuan.');
                    btnSubmit.disabled = false;
                }
            } catch (err) {
                alert('Gagal terhubung ke server: ' + err.message);
                btnSubmit.disabled = false;
            }
        });
    }
}

// ----------------------------------------------------------------------------
// 5. UI-05-01 s/d UI-05-04: Halaman Verifikasi Hasil Desain (Timer 1 Jam & Auto-Approve)
// ----------------------------------------------------------------------------
function initVerifikasiDesain() {
    const container = document.getElementById('halamanVerifikasiDesain') || document.querySelector('[data-page="verifikasi-desain"]');
    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const idPengajuan = params.get('id') || container.dataset.idPengajuan || 'REQ-001';

    const timerEl = document.getElementById('verifikasiCountdown') || document.getElementById('countdownTimer');
    const badgeStatus = document.getElementById('badgeStatusVerifikasi');
    const bannerFix = document.getElementById('bannerDesainFix');
    const bannerAutoApproved = document.getElementById('bannerAutoApproved');

    const btnSetujui = document.getElementById('btnSetujuiDesain');
    const btnRevisi = document.getElementById('btnAjukanRevisi');
    const modalKonfirmasi = document.getElementById('modalKonfirmasiSetujui');
    const btnBatalSetujui = document.getElementById('btnBatalSetujui');
    const btnYaSetujui = document.getElementById('btnYaSetujui');

    function kunciTombolAksi(statusText = 'Fix') {
        if (badgeStatus) badgeStatus.textContent = statusText;
        if (btnSetujui) btnSetujui.disabled = true;
        if (btnRevisi) btnRevisi.disabled = true;
    }

    async function jalankanPersetujuan(isAutoApprove = false) {
        if (typeof CMITimer !== 'undefined') CMITimer.stop('verifikasi_1jam');

        try {
            await fetch(VERIFIKASI_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: isAutoApprove ? 'auto_approve' : 'approve_desain',
                    idPengajuan: idPengajuan
                })
            });
        } catch (err) {
            console.warn('Endpoint verifikasi_api belum aktif, menerapkan state UI lokal.');
        }

        if (modalKonfirmasi) {
            modalKonfirmasi.classList.remove('active');
            modalKonfirmasi.style.display = 'none';
        }

        kunciTombolAksi('Fix');

        if (isAutoApprove) {
            if (bannerAutoApproved) bannerAutoApproved.style.display = 'block';
        } else if (bannerFix) {
            bannerFix.style.display = 'block';
        }
    }

    // Mulai countdown 1 jam (3600 detik)
    const durasiAwal = parseInt(container.dataset.sisaWaktu || '3600', 10);
    if (typeof CMITimer !== 'undefined') {
        CMITimer.start('verifikasi_1jam', durasiAwal, timerEl, {
            includeHours: true,
            onTimeout: () => jalankanPersetujuan(true)
        });
    }

    if (btnSetujui) {
        btnSetujui.addEventListener('click', () => {
            if (modalKonfirmasi) {
                modalKonfirmasi.classList.add('active');
                modalKonfirmasi.style.display = 'flex';
            } else if (confirm('Setujui desain ini? Status akan berubah menjadi Fix.')) {
                jalankanPersetujuan(false);
            }
        });
    }

    if (btnBatalSetujui && modalKonfirmasi) {
        btnBatalSetujui.addEventListener('click', () => {
            modalKonfirmasi.classList.remove('active');
            modalKonfirmasi.style.display = 'none';
        });
    }

    if (btnYaSetujui) {
        btnYaSetujui.addEventListener('click', () => jalankanPersetujuan(false));
    }

    if (btnRevisi) {
        btnRevisi.addEventListener('click', () => {
            window.location.href = `form-revisi.html?id=${encodeURIComponent(idPengajuan)}`;
        });
    }
}

// ----------------------------------------------------------------------------
// 6. UI-06-01 s/d UI-06-04: Form Pengajuan Revisi & Modal Penalti -5 Poin
// ----------------------------------------------------------------------------
function initFormRevisi() {
    const form = document.getElementById('formRevisiDesain') || document.querySelector('form[data-page="form-revisi"]');
    if (!form) return;

    const params = new URLSearchParams(window.location.search);
    const idPengajuan = params.get('id') || form.dataset.idPengajuan || 'REQ-001';

    const textareaRevisi = form.querySelector('#teksRevisi, #catatanRevisi, textarea');
    const charCounter = document.getElementById('charCounter');
    const alertKosong = document.getElementById('alertRevisiKosong');
    const bannerBerhasil = document.getElementById('bannerRevisiBerhasil');
    const badgeStatus = document.getElementById('badgeStatusRevisi');

    const modalPenalti = document.getElementById('modalPenaltiKeterlambatan');
    const btnSetujuiPenalti = document.getElementById('btnSetujuiPenalti');
    const btnBatalPenalti = document.getElementById('btnBatalPenalti');
    const btnKembali = document.getElementById('btnKembaliRevisi');

    if (textareaRevisi && charCounter) {
        textareaRevisi.addEventListener('input', () => {
            const len = textareaRevisi.value.length;
            charCounter.textContent = `${len}/1000`;
            textareaRevisi.classList.remove('is-error');
            if (alertKosong) alertKosong.style.display = 'none';
        });
    }

    if (btnKembali) {
        btnKembali.addEventListener('click', () => {
            window.location.href = 'status-pengajuan.html';
        });
    }

    async function kirimRevisiKeServer(konfirmasiPenalti = false) {
        const teks = textareaRevisi ? textareaRevisi.value.trim() : '';

        if (!teks) {
            if (textareaRevisi) textareaRevisi.classList.add('is-error');
            if (alertKosong) {
                alertKosong.textContent = 'Mohon lengkapi catatan revisi.';
                alertKosong.style.display = 'block';
            }
            return;
        }

        try {
            const res = await fetch(PENGAJU_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'submit_revisi',
                    idPengajuan: idPengajuan,
                    kementerianId: 'KEM-01',
                    teksRevisi: teks,
                    konfirmasiPenalti: konfirmasiPenalti
                })
            });
            const result = await res.json();

            if (result.requirePenalti) {
                if (modalPenalti) {
                    modalPenalti.classList.add('active');
                    modalPenalti.style.display = 'flex';
                } else if (confirm(result.message)) {
                    await kirimRevisiKeServer(true);
                }
                return;
            }

            if (result.success) {
                if (modalPenalti) {
                    modalPenalti.classList.remove('active');
                    modalPenalti.style.display = 'none';
                }
                if (badgeStatus) badgeStatus.textContent = 'Dalam Revisi Desain';
                if (bannerBerhasil) {
                    bannerBerhasil.textContent = result.message || 'Catatan revisi berhasil terkirim ke CMI.';
                    bannerBerhasil.style.display = 'block';
                } else {
                    alert(result.message);
                }
            } else {
                alert(result.message || 'Gagal mengirim catatan revisi.');
            }
        } catch (err) {
            alert('Gagal terhubung ke server: ' + err.message);
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await kirimRevisiKeServer(false);
    });

    if (btnSetujuiPenalti) {
        btnSetujuiPenalti.addEventListener('click', async () => {
            await kirimRevisiKeServer(true);
        });
    }

    if (btnBatalPenalti && modalPenalti) {
        btnBatalPenalti.addEventListener('click', () => {
            modalPenalti.classList.remove('active');
            modalPenalti.style.display = 'none';
        });
    }
}

// ----------------------------------------------------------------------------
// INISIALISASI OTOMATIS SAAT HALAMAN DIMUAT
// ----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initStatusPengajuan();
    initPilihanPengajuan();
    initDetailPengajuan();
    initFormRequestInternal();
    initStandalonePreviewPage();
    initVerifikasiDesain();
    initFormRevisi();
});
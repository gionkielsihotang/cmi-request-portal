//gion

const CMI_API_URL = '../../api/cmi_api.php';

// ----------------------------------------------------------------------------
// 1. UI-10-01 & UI-11-01: Daftar Permohonan CMI & Dashboard Filter Chip Status
// ----------------------------------------------------------------------------
function initDashboardDanDaftarCMI() {
    // Navigasi klik tombol "Lihat Detail" atau baris tabel menuju detail-review.html
    document.querySelectorAll('.btn-lihat-detail, [data-detail-id]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const row = btn.closest('tr');
            const id = btn.dataset.detailId || (row ? row.dataset.id : '');
            if (id) {
                window.location.href = `detail-review.html?id=${encodeURIComponent(id)}`;
            }
        });
    });

    // Filter chip status pada Dashboard CMI (Semua, Waiting, Ditinjau)
    const filterChips = document.querySelectorAll('#filterStatus .f, [data-filter], [data-filter-status]');
    const tableRows = document.querySelectorAll('#daftarRequest tr[data-status], tbody tr[data-status]');

    if (!filterChips.length) return;

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
// 2. UI-10-02, UI-11-02..05, UI-12-01..03: Detail Review, SOP, Proses & Tolak
// ----------------------------------------------------------------------------
function initDetailReviewCMI() {
    const params = new URLSearchParams(window.location.search);
    const idPengajuan = params.get('id') || 'MP-004';

    // Tampilkan ID request dari URL (?id=...) jika ada elemen penampungnya
    ['detailIdPengajuan', 'detailNoRequest', 'kodeRequest'].forEach((elId) => {
        const el = document.getElementById(elId);
        if (el) el.textContent = idPengajuan;
    });

    const badgeStatus = document.getElementById('badgeStatusReview');

    // Elemen Alur Proses & SOP (UI-11-02 s/d UI-11-05)
    const btnProses = document.getElementById('btnProsesRequest') || document.getElementById('btnTerimaRequest');
    const modalProses = document.getElementById('modalKonfirmasiProses');
    const btnBatalProses = document.getElementById('btnBatalProses');
    const btnYaProses = document.getElementById('btnYaProses');
    const bannerDiproses = document.getElementById('bannerRequestDiproses');
    const auditLogList = document.getElementById('auditLogList');

    const btnCatatKelengkapan = document.getElementById('btnCatatKelengkapan');
    const textareaKelengkapan = document.getElementById('textareaCatatanKelengkapan');

    // Elemen Alur Penolakan (UI-12-01 s/d UI-12-03)
    const btnTolak = document.getElementById('btnTolakRequest');
    const modalTolak = document.getElementById('modalAlasanPenolakan');
    const btnCancelTolak = document.getElementById('btnCancelTolak');
    const btnSendTolak = document.getElementById('btnSendTolak');
    const textareaAlasan = document.getElementById('textareaAlasanPenolakan');
    const alertTolakError = document.getElementById('alertPenolakanError');
    const bannerDitolak = document.getElementById('bannerRequestDitolak');
    const infoAlasanTerkirim = document.getElementById('infoAlasanPenolakan');

    // Tombol lanjut unggah desain jika status Diproses
    const btnKeUploadDesain = document.getElementById('btnKeUploadDesain');
    if (btnKeUploadDesain) {
        btnKeUploadDesain.addEventListener('click', () => {
            window.location.href = `unggah-desain.html?id=${encodeURIComponent(idPengajuan)}`;
        });
    }

    // Simpan Catatan Kelengkapan Materi (status tetap Ditinjau — UI-11-05)
    if (btnCatatKelengkapan) {
        btnCatatKelengkapan.addEventListener('click', async () => {
            const catatan = textareaKelengkapan ? textareaKelengkapan.value.trim() : '';
            if (!catatan) {
                alert('Isi catatan kelengkapan materi terlebih dahulu.');
                return;
            }

            try {
                await fetch(CMI_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'simpan_catatan',
                        idPengajuan: idPengajuan,
                        catatanKelengkapan: catatan
                    })
                });
            } catch (err) {
                console.warn('Mode tampilan lokal aktif.');
            }

            if (badgeStatus) badgeStatus.textContent = 'Ditinjau';
            alert('Catatan kelengkapan materi berhasil disimpan. Status permohonan tetap Ditinjau.');
        });
    }

    // Buka Modal Konfirmasi Proses (UI-11-03)
    if (btnProses) {
        btnProses.addEventListener('click', () => {
            if (modalProses) {
                modalProses.hidden = false;
                modalProses.classList.add('active');
                modalProses.style.display = 'flex';
            } else if (confirm('Proses request ini?')) {
                eksekusiProsesRequest();
            }
        });
    }

    if (btnBatalProses && modalProses) {
        btnBatalProses.addEventListener('click', () => {
            modalProses.hidden = true;
            modalProses.classList.remove('active');
            modalProses.style.display = 'none';
        });
    }

    async function eksekusiProsesRequest() {
        try {
            await fetch(CMI_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'proses_request',
                    idPengajuan: idPengajuan,
                    idCMI: 'CMI-001'
                })
            });
        } catch (err) {
            console.warn('Mode tampilan lokal aktif.');
        }

        if (modalProses) {
            modalProses.hidden = true;
            modalProses.classList.remove('active');
            modalProses.style.display = 'none';
        }
        if (badgeStatus) badgeStatus.textContent = 'Diproses';
        if (bannerDiproses) {
            bannerDiproses.hidden = false;
            bannerDiproses.style.display = 'block';
        }
        if (btnKeUploadDesain) {
            btnKeUploadDesain.hidden = false;
            btnKeUploadDesain.style.display = 'inline-block';
        }

        if (auditLogList) {
            const li = document.createElement('li');
            li.textContent = `[${new Date().toLocaleString('id-ID')}] Admin CMI memproses permohonan (${idPengajuan}) -> Status: Diproses`;
            auditLogList.prepend(li);
        }
    }

    if (btnYaProses) {
        btnYaProses.addEventListener('click', eksekusiProsesRequest);
    }

    // Buka Modal Alasan Penolakan (UI-12-01)
    if (btnTolak) {
        btnTolak.addEventListener('click', () => {
            if (alertTolakError) {
                alertTolakError.hidden = true;
                alertTolakError.style.display = 'none';
            }
            if (textareaAlasan) textareaAlasan.classList.remove('is-error');
            if (modalTolak) {
                modalTolak.hidden = false;
                modalTolak.classList.add('active');
                modalTolak.style.display = 'flex';
            }
        });
    }

    if (btnCancelTolak && modalTolak) {
        btnCancelTolak.addEventListener('click', () => {
            modalTolak.hidden = true;
            modalTolak.classList.remove('active');
            modalTolak.style.display = 'none';
        });
    }

    // Validasi & Kirim Alasan Penolakan (UI-12-02 & UI-12-03)
    if (btnSendTolak) {
        btnSendTolak.addEventListener('click', async () => {
            const alasan = textareaAlasan ? textareaAlasan.value.trim() : '';

            if (!alasan) {
                if (textareaAlasan) textareaAlasan.classList.add('is-error');
                if (alertTolakError) {
                    alertTolakError.textContent = 'Alasan penolakan wajib diisi.';
                    alertTolakError.hidden = false;
                    alertTolakError.style.display = 'block';
                }
                return;
            }

            try {
                await fetch(CMI_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'tolak_request',
                        idPengajuan: idPengajuan,
                        alasanPenolakan: alasan
                    })
                });
            } catch (err) {
                console.warn('Mode tampilan lokal aktif.');
            }

            if (modalTolak) {
                modalTolak.hidden = true;
                modalTolak.classList.remove('active');
                modalTolak.style.display = 'none';
            }
            if (badgeStatus) badgeStatus.textContent = 'Ditolak';
            if (bannerDitolak) {
                bannerDitolak.hidden = false;
                bannerDitolak.style.display = 'block';
            }
            if (infoAlasanTerkirim) infoAlasanTerkirim.textContent = alasan;
        });
    }
}

// ----------------------------------------------------------------------------
// 3. UI-13-01 s/d UI-13-03: Halaman Unggah Hasil Desain CMI & Timer 1 Jam
// ----------------------------------------------------------------------------
function initUploadDesainCMI() {
    const formUpload = document.getElementById('formUploadDesain') || document.querySelector('form[data-page="unggah-desain"]');
    if (!formUpload) return;

    const params = new URLSearchParams(window.location.search);
    const idPengajuan = params.get('id') || formUpload.dataset.idPengajuan || 'MP-004';

    const inputDesain = formUpload.querySelector('#fileDesain, #berkasDesain, input[type="file"]');
    const dropzone = formUpload.querySelector('#dropzoneDesain, .dropzone');
    const alertError = document.getElementById('alertUploadError');
    const bannerBerhasil = document.getElementById('bannerUploadBerhasil');
    const badgeStatus = document.getElementById('badgeStatusUpload');
    const timerEl = document.getElementById('uploadVerifikasiCountdown') || document.getElementById('countdownTimer');
    const btnSimpanDraf = document.getElementById('btnSimpanDraf');

    function validateDesignFile(file) {
        if (!file) {
            return { valid: false, message: 'Pilih berkas hasil desain terlebih dahulu.' };
        }
        const allowedExt = ['png', 'jpg', 'jpeg', 'pdf'];
        const ext = file.name.split('.').pop().toLowerCase();
        const maxBytes = 10 * 1024 * 1024;

        if (!allowedExt.includes(ext) || file.size > maxBytes) {
            return {
                valid: false,
                message: 'Format berkas tidak didukung atau ukuran melebihi batas maksimal 10 MB (PNG/JPG/PDF).'
            };
        }
        return { valid: true };
    }

    if (inputDesain) {
        inputDesain.addEventListener('change', () => {
            if (alertError) {
                alertError.hidden = true;
                alertError.style.display = 'none';
            }
            if (dropzone) dropzone.classList.remove('error');

            const file = inputDesain.files[0];
            if (!file) return;

            const check = validateDesignFile(file);
            if (!check.valid) {
                if (dropzone) dropzone.classList.add('error');
                if (alertError) {
                    alertError.textContent = check.message;
                    alertError.hidden = false;
                    alertError.style.display = 'block';
                }
                inputDesain.value = '';
            }
        });
    }

    if (btnSimpanDraf) {
        btnSimpanDraf.addEventListener('click', () => {
            alert('Draf unggahan hasil desain berhasil disimpan sementara.');
        });
    }

    formUpload.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (alertError) {
            alertError.hidden = true;
            alertError.style.display = 'none';
        }
        if (dropzone) dropzone.classList.remove('error');

        const file = inputDesain && inputDesain.files ? inputDesain.files[0] : null;
        const check = validateDesignFile(file);

        if (!check.valid) {
            if (dropzone) dropzone.classList.add('error');
            if (alertError) {
                alertError.textContent = check.message;
                alertError.hidden = false;
                alertError.style.display = 'block';
            }
            return;
        }

        const fd = new FormData();
        fd.append('action', 'upload_desain');
        fd.append('idPengajuan', idPengajuan);
        fd.append('fileDesain', file);

        try {
            await fetch(CMI_API_URL, { method: 'POST', body: fd });
        } catch (err) {
            console.warn('Mode tampilan lokal aktif.');
        }

        if (badgeStatus) badgeStatus.textContent = 'Menunggu Verifikasi';
        if (bannerBerhasil) {
            bannerBerhasil.hidden = false;
            bannerBerhasil.style.display = 'block';
        }

        if (typeof CMITimer !== 'undefined') {
            CMITimer.start('upload_verifikasi_1jam', 3600, timerEl, { includeHours: true });
        }
    });
}

// ----------------------------------------------------------------------------
// INISIALISASI OTOMATIS SAAT HALAMAN DIMUAT
// ----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initDashboardDanDaftarCMI();
    initDetailReviewCMI();
    initUploadDesainCMI();
});
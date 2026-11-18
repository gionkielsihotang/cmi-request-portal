/**
 * SCRIPT FRONTEND PENGAJU - CMI BEM FILKOM UB
 * File: assets/js/pengaju.js
 * Bertanggung jawab khusus untuk interaktivitas UI:
 * - Pop-up modal (buka/tutup)
 * - Countdown timer (menggunakan countdown.js)
 * - Pratinjau input form ke modal sebelum submit native POST
 * - Tidak menggunakan fetch(), sessionStorage, atau window.location.href (alur server-side PHP)
 */

// ----------------------------------------------------------------------------
// 1. Inisialisasi Form Request Internal & Modal Pratinjau 180 Detik (UI-04 & UI-03)
// ----------------------------------------------------------------------------
function initFormRequestInternal() {
    const form = document.getElementById('formRequestInternal');
    if (!form) return;

    const inputJudul = document.getElementById('judulRequest');
    const inputKementerian = document.getElementById('kementerianId');
    const inputTanggal = document.getElementById('tanggalPublikasi');
    const inputRedaksional = document.getElementById('detailKonten');
    const inputBerkas = document.getElementById('berkasAcuan');
    const dropzone = document.getElementById('dropzoneAcuan');
    const labelBerkasTerpilih = document.getElementById('namaBerkasTerpilih');

    const alertKosong = document.getElementById('alertErrorKosong');
    const alertBerkas = document.getElementById('alertErrorBerkas');
    const modalPreview = document.getElementById('modalPreviewRequest');
    const btnNextPreview = document.getElementById('btnNextPreview');
    const btnBackPreview = document.getElementById('btnBackToForm');
    const timerEl = document.getElementById('countdownTimer');

    function resetErrors() {
        if (alertKosong) alertKosong.style.display = 'none';
        if (alertBerkas) alertBerkas.style.display = 'none';
        form.querySelectorAll('.is-error').forEach((el) => el.classList.remove('is-error'));
        if (dropzone) dropzone.classList.remove('error');
    }

    // Deteksi perubahan file pada dropzone
    if (inputBerkas) {
        inputBerkas.addEventListener('change', () => {
            resetErrors();
            const file = inputBerkas.files[0];
            if (!file) {
                if (labelBerkasTerpilih) labelBerkasTerpilih.textContent = '';
                return;
            }

            const allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
            const ext = file.name.split('.').pop().toLowerCase();
            const maxBytes = 5 * 1024 * 1024; // 5 MB

            if (!allowedExt.includes(ext) || file.size > maxBytes) {
                if (dropzone) dropzone.classList.add('error');
                if (alertBerkas) {
                    alertBerkas.textContent = 'Ukuran berkas melebihi batas 5 MB atau format tidak valid (JPG/PNG/PDF).';
                    alertBerkas.style.display = 'block';
                }
                inputBerkas.value = '';
                if (labelBerkasTerpilih) labelBerkasTerpilih.textContent = '';
            } else {
                if (labelBerkasTerpilih) {
                    labelBerkasTerpilih.textContent = `Berkas dipilih: ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                }
            }
        });
    }

    // Tombol Next -> Validasi isian, salin nilai ke modal preview, lalu buka modal
    if (btnNextPreview) {
        btnNextPreview.addEventListener('click', (e) => {
            e.preventDefault();
            resetErrors();

            const judulVal = inputJudul ? inputJudul.value.trim() : '';
            const kementerianVal = inputKementerian ? inputKementerian.value.trim() : '';
            const redaksionalVal = inputRedaksional ? inputRedaksional.value.trim() : '';
            const jenisChecked = form.querySelector('input[name="jenisPublikasi"]:checked');
            const jenisVal = jenisChecked ? jenisChecked.value : 'Instagram Feeds';
            const tanggalVal = inputTanggal ? inputTanggal.value.trim() : '-';
            const fileObj = inputBerkas && inputBerkas.files[0];

            let hasEmpty = false;
            if (!judulVal) {
                if (inputJudul) inputJudul.classList.add('is-error');
                hasEmpty = true;
            }
            if (!kementerianVal) {
                if (inputKementerian) inputKementerian.classList.add('is-error');
                hasEmpty = true;
            }
            if (!redaksionalVal) {
                if (inputRedaksional) inputRedaksional.classList.add('is-error');
                hasEmpty = true;
            }

            if (hasEmpty) {
                if (alertKosong) alertKosong.style.display = 'block';
                return;
            }

            // Tampilkan teks nama kementerian yang dipilih
            let namaKementerian = kementerianVal;
            if (inputKementerian && inputKementerian.selectedIndex >= 0) {
                namaKementerian = inputKementerian.options[inputKementerian.selectedIndex].text;
            }

            // Pasang data ke tabel preview di dalam modal
            const elJudul = document.getElementById('prevJudul');
            const elKem = document.getElementById('prevKementerian');
            const elJenis = document.getElementById('prevJenis');
            const elTgl = document.getElementById('prevTanggal');
            const elRedaksi = document.getElementById('prevRedaksional');
            const elBerkas = document.getElementById('prevBerkas');

            if (elJudul) elJudul.textContent = judulVal;
            if (elKem) elKem.textContent = namaKementerian;
            if (elJenis) elJenis.textContent = jenisVal;
            if (elTgl) elTgl.textContent = tanggalVal;
            if (elRedaksi) elRedaksi.textContent = redaksionalVal;
            if (elBerkas) elBerkas.textContent = fileObj ? fileObj.name : 'Tidak ada lampiran';

            // Buka modal pratinjau
            if (modalPreview) {
                modalPreview.hidden = false;
                modalPreview.classList.add('active');
                modalPreview.style.display = 'flex';

                // Jalankan countdown timer 180 detik
                if (typeof CMITimer !== 'undefined' && timerEl) {
                    CMITimer.start('preview_180', 180, timerEl, {
                        onTimeout: () => {
                            closePreviewModal();
                            alert('Waktu toleransi pratinjau 180 detik telah habis. Silakan periksa kembali formulir.');
                        }
                    });
                }
            }
        });
    }

    function closePreviewModal() {
        if (typeof CMITimer !== 'undefined') CMITimer.stop('preview_180');
        if (modalPreview) {
            modalPreview.hidden = true;
            modalPreview.classList.remove('active');
            modalPreview.style.display = 'none';
        }
    }

    if (btnBackPreview) {
        btnBackPreview.addEventListener('click', closePreviewModal);
    }
}

// ----------------------------------------------------------------------------
// 2. Inisialisasi Verifikasi Desain: Modal Setujui & Timer 1 Jam (UI-05)
// ----------------------------------------------------------------------------
function initVerifikasiDesain() {
    const modalSetuju = document.getElementById('modalSetuju');
    const btnSetuju = document.getElementById('btnSetuju');
    const btnBatalSetuju = document.getElementById('btnBatalSetuju');
    const timerBadge = document.getElementById('timerBadge');

    // Countdown timer verifikasi 1 jam (3600 detik)
    if (typeof CMITimer !== 'undefined' && timerBadge) {
        const sisaDetikAttr = timerBadge.dataset.sisaDetik || '3600';
        const sisaDetik = parseInt(sisaDetikAttr, 10);
        
        CMITimer.start('verifikasi_1jam', sisaDetik, timerBadge, {
            includeHours: true,
            onTimeout: () => {
                timerBadge.textContent = 'Batas waktu habis';
            }
        });
    }

    if (btnSetuju && modalSetuju) {
        btnSetuju.addEventListener('click', () => {
            modalSetuju.hidden = false;
            modalSetuju.classList.add('active');
            modalSetuju.style.display = 'flex';
        });
    }

    if (btnBatalSetuju && modalSetuju) {
        btnBatalSetuju.addEventListener('click', () => {
            modalSetuju.hidden = true;
            modalSetuju.classList.remove('active');
            modalSetuju.style.display = 'none';
        });
    }
}

// ----------------------------------------------------------------------------
// 3. Inisialisasi Form Revisi: Karakter Counter & Modal Penalti (UI-06)
// ----------------------------------------------------------------------------
function initFormRevisi() {
    const textarea = document.getElementById('catatanRevisi') || document.querySelector('textarea[name="catatanRevisi"]');
    const charCounter = document.getElementById('charCounter');
    const modalPenalti = document.getElementById('modalPenalti');
    const btnBatalPenalti = document.getElementById('btnBatalPenalti');

    if (textarea && charCounter) {
        textarea.addEventListener('input', () => {
            const count = textarea.value.length;
            charCounter.textContent = `${count}/1000 karakter`;
        });
    }

    if (btnBatalPenalti && modalPenalti) {
        btnBatalPenalti.addEventListener('click', () => {
            modalPenalti.hidden = true;
            modalPenalti.classList.remove('active');
            modalPenalti.style.display = 'none';
        });
    }
}

// ----------------------------------------------------------------------------
// INISIALISASI SAAT DOM SIAP
// ----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initFormRequestInternal();
    initVerifikasiDesain();
    initFormRevisi();
});

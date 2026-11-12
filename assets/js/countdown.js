/**
 * SUBBAB 4.2 & 4.3 CLIENT-SIDE LOGIC
 * ID Kode     : [GYS-JS01]
 * Deskripsi   : Utilitas Timer Hitung Mundur (180s Preview & 3600s Verifikasi Desain)
 * Acuan SDD   : PC-CPR-01, PC-CPR-04, PC-CVD-03, PC-CRD-01
 * Penyusun    : Gion Yehezkiel Sihotang
 */

const PortalTimer = (() => {
    const activeTimers = {};

    // Format detik ke MM:SS (untuk Preview 3 Menit)
    function formatMenitDetik(totalDetik) {
        const menit = String(Math.floor(totalDetik / 60)).padStart(2, '0');
        const detik = String(totalDetik % 60).padStart(2, '0');
        return `${menit}:${detik}`;
    }

    // Format detik ke HH:MM:SS (untuk Verifikasi 1 Jam)
    function formatJamMenitDetik(totalDetik) {
        const jam = String(Math.floor(totalDetik / 3600)).padStart(2, '0');
        const sisaSetelahJam = totalDetik % 3600;
        const menit = String(Math.floor(sisaSetelahJam / 60)).padStart(2, '0');
        const detik = String(sisaSetelahJam % 60).padStart(2, '0');
        return `${jam}:${menit}:${detik}`;
    }

    /**
     * Menjalankan timer hitung mundur
     * @param {Object} config - { key, durasiDetik, elementId, format, onTick, onTimeout }
     */
    function start({ key, durasiDetik, elementId, format = 'MM:SS', onTick, onTimeout }) {
        stop(key); // Hentikan timer lama jika ada dengan key sama

        let sisaWaktu = durasiDetik;
        const targetEl = document.getElementById(elementId);

        const render = () => {
            if (targetEl) {
                targetEl.textContent = format === 'HH:MM:SS'
                    ? formatJamMenitDetik(sisaWaktu)
                    : formatMenitDetik(sisaWaktu);
            }
            if (typeof onTick === 'function') onTick(sisaWaktu);
        };

        render();

        activeTimers[key] = setInterval(() => {
            sisaWaktu--;

            if (sisaWaktu <= 0) {
                sisaWaktu = 0;
                render();
                stop(key);
                if (typeof onTimeout === 'function') onTimeout();
                return;
            }

            render();
        }, 1000);
    }

    function stop(key) {
        if (activeTimers[key]) {
            clearInterval(activeTimers[key]);
            delete activeTimers[key];
        }
    }

    return { start, stop };
})();
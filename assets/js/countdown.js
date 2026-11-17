const CMITimer = (() => {
    const activeIntervals = {};

    function formatTime(totalSeconds, includeHours = false) {
        const safeSeconds = Math.max(0, parseInt(totalSeconds, 10) || 0);
        const hrs = Math.floor(safeSeconds / 3600);
        const mins = Math.floor((safeSeconds % 3600) / 60);
        const secs = safeSeconds % 60;

        const pad = (num) => String(num).padStart(2, '0');
        if (includeHours || hrs > 0) {
            return `${pad(hrs)}:${pad(mins)}:${pad(secs)}`;
        }
        return `${pad(mins)}:${pad(secs)}`;
    }

    function start(timerKey, durationSeconds, targetElement, options = {}) {
        stop(timerKey);

        let remaining = parseInt(durationSeconds, 10) || 0;
        const includeHours = Boolean(options.includeHours);

        if (targetElement) {
            targetElement.textContent = formatTime(remaining, includeHours);
        }

        if (remaining <= 0) {
            if (typeof options.onTimeout === 'function') options.onTimeout();
            return;
        }

        activeIntervals[timerKey] = setInterval(() => {
            remaining -= 1;

            if (targetElement) {
                targetElement.textContent = formatTime(remaining, includeHours);
            }

            if (typeof options.onTick === 'function') {
                options.onTick(remaining);
            }

            if (remaining <= 0) {
                stop(timerKey);
                if (typeof options.onTimeout === 'function') {
                    options.onTimeout();
                }
            }
        }, 1000);
    }

    function stop(timerKey) {
        if (activeIntervals[timerKey]) {
            clearInterval(activeIntervals[timerKey]);
            delete activeIntervals[timerKey];
        }
    }

    return { start, stop, formatTime };
})();
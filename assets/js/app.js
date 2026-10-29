document.addEventListener('DOMContentLoaded', () => {
    // Grid Switcher
    const gridContainer = document.getElementById('cctvGrid');
    const gridButtons = document.querySelectorAll('.btn-grid');

    if (gridContainer && gridButtons.length) {
        const savedGrid = localStorage.getItem('cctv_grid_preference') || 'grid-4';
        setGrid(savedGrid);

        gridButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetGrid = btn.dataset.grid;
                setGrid(targetGrid);
            });
        });
    }

    function setGrid(gridClass) {
        if (!gridContainer) return;
        gridContainer.className = `cctv-grid ${gridClass}`;
        localStorage.setItem('cctv_grid_preference', gridClass);

        gridButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.grid === gridClass);
        });
    }

    // Fullscreen Entire Grid
    const btnFullscreenAll = document.getElementById('btnFullscreenAll');
    if (btnFullscreenAll && gridContainer) {
        btnFullscreenAll.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                gridContainer.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        });
    }

    // Periodic Unread Motion Events Badge
    function pollUnreadMotions() {
        fetch(`${window.APP_URL || ''}/api/motion-events/unread-count`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const badge = document.getElementById('motionUnreadBadge');
                if (badge) {
                    badge.textContent = res.count > 0 ? res.count : '';
                    badge.style.display = res.count > 0 ? 'inline-block' : 'none';
                }
            }
        })
        .catch(() => {});
    }

    setInterval(pollUnreadMotions, 10000);
});

// Modal Helpers
function openModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.add('active');
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.remove('active');
}


<?php
declare(strict_types=1);
$pageTitle = 'Riwayat Deteksi Gerakan';
$activePage = 'motion-events';
require __DIR__ . '/../layouts/header.php';
?>

<div class="toolbar">
    <div class="toolbar-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
        </svg>
        <span>Galeri Deteksi Gerakan (Motion Events)</span>
    </div>
</div>

<div class="card" style="padding: 1rem;">
    <form id="filterForm" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label class="form-label" for="filterCamera">Pilih Kamera</label>
            <select id="filterCamera" class="form-select">
                <option value="">Semua Kamera</option>
                <?php foreach ($cameras as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['group_name'] ?? 'Gedung A') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="width: 170px;">
            <label class="form-label" for="filterStartDate">Dari Tanggal</label>
            <input type="date" id="filterStartDate" class="form-input">
        </div>

        <div style="width: 170px;">
            <label class="form-label" for="filterEndDate">Sampai Tanggal</label>
            <input type="date" id="filterEndDate" class="form-input">
        </div>

        <div style="width: 150px;">
            <label class="form-label" for="filterViewed">Status Tinjau</label>
            <select id="filterViewed" class="form-select">
                <option value="">Semua</option>
                <option value="0">Belum Diperiksa</option>
                <option value="1">Sudah Diperiksa</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        <button type="button" id="btnResetFilter" class="btn btn-secondary" style="height: 38px;">Reset</button>
    </form>
</div>

<div id="motionGallery" class="motion-grid"></div>

<div id="paginationArea" style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.5rem;"></div>

<!-- Modal Detail Snapshot -->
<div class="modal-overlay" id="snapshotModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 1rem; font-weight: 600;" id="modalTitle">Detail Event Deteksi</h3>
            <button class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="closeModal('snapshotModal')">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalImg" src="" alt="Snapshot" style="width: 100%; border-radius: 4px; margin-bottom: 1rem; max-height: 500px; object-fit: contain; background: #000;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <div id="modalInfo" style="font-size: 0.85rem; color: var(--text-secondary);"></div>
                <div style="display: flex; gap: 0.5rem;">
                    <button id="btnMarkViewed" class="btn btn-primary" style="font-size: 0.8rem;">Tandai Diperiksa</button>
                    <a id="btnDownloadSnap" href="#" download class="btn btn-secondary" style="font-size: 0.8rem;">Unduh Gambar</a>
                    <button id="btnDeleteSnap" class="btn btn-danger" style="font-size: 0.8rem;">Hapus Capture</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentEvent = null;

function loadMotionEvents(page = 1) {
    currentPage = page;
    const camId = document.getElementById('filterCamera').value;
    const start = document.getElementById('filterStartDate').value;
    const end = document.getElementById('filterEndDate').value;
    const viewed = document.getElementById('filterViewed').value;

    const params = new URLSearchParams({ page });
    if (camId) params.append('camera_id', camId);
    if (start) params.append('start_date', start);
    if (end) params.append('end_date', end);
    if (viewed !== '') params.append('is_viewed', viewed);

    const container = document.getElementById('motionGallery');
    container.innerHTML = '<p style="color: var(--text-secondary); font-family: ui-monospace, monospace;">Memuat log deteksi...</p>';

    fetch(`${window.APP_URL}/api/motion-events?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.data.data.length) {
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-secondary); padding: 3rem;">Tidak ada rekaman deteksi gerakan yang ditemukan.</div>';
            document.getElementById('paginationArea').innerHTML = '';
            return;
        }

        container.innerHTML = res.data.data.map(item => `
            <div class="motion-card" onclick="viewSnapshot(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                <img class="motion-thumb" src="${window.APP_URL}/${item.snapshot_path}" alt="Capture ${item.id}" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'60\' fill=\'%231e293b\'><rect width=\'100%\' height=\'100%\'/></svg>'">
                <div class="motion-meta">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="color: #fff; font-size: 0.825rem;">${item.camera_name}</strong>
                        <div style="display: flex; gap: 0.35rem; align-items: center;">
                            ${!item.is_viewed ? '<span class="role-pill" style="background:#78350f;color:#fde68a;">BARU</span>' : '<span style="color:var(--text-muted);font-size:0.7rem;">Dilihat</span>'}
                            <button class="btn btn-danger" style="padding: 0.15rem 0.4rem; font-size: 0.68rem;" onclick="event.stopPropagation(); deleteSnapshot(${item.id});" title="Hapus snapshot ini">&times; Hapus</button>
                        </div>
                    </div>
                    <span style="color: var(--text-muted); font-family: ui-monospace, monospace; font-size: 0.75rem;">${item.detected_at}</span>
                    <span style="color: var(--status-motion); font-family: ui-monospace, monospace; font-size: 0.75rem;">Skor Gerakan: ${item.confidence_score}%</span>
                </div>
            </div>
        `).join('');

        renderPagination(res.data.current_page, res.data.total_pages);
    })
    .catch(() => {
        container.innerHTML = '<p style="color: var(--status-offline);">Gagal mengambil data dari server.</p>';
    });
}

function renderPagination(current, total) {
    const area = document.getElementById('paginationArea');
    if (total <= 1) {
        area.innerHTML = '';
        return;
    }
    let html = '';
    for (let i = 1; i <= total; i++) {
        html += `<button class="btn btn-secondary" style="${i === current ? 'background:var(--accent-blue);color:#fff;' : ''} padding: 0.35rem 0.75rem; font-family: ui-monospace, monospace;" onclick="loadMotionEvents(${i})">${i}</button>`;
    }
    area.innerHTML = html;
}

function viewSnapshot(event) {
    currentEvent = event;
    document.getElementById('modalTitle').textContent = `Tangkapan Gerakan // ${event.camera_name}`;
    document.getElementById('modalImg').src = `${window.APP_URL}/${event.snapshot_path}`;
    document.getElementById('btnDownloadSnap').href = `${window.APP_URL}/${event.snapshot_path}`;
    document.getElementById('modalInfo').innerHTML = `
        Waktu: <strong>${event.detected_at}</strong><br>
        Lokasi: ${event.camera_location || '-'}<br>
        Skor Perubahan Visual: ${event.confidence_score}%
    `;

    const btnMark = document.getElementById('btnMarkViewed');
    btnMark.style.display = event.is_viewed ? 'none' : 'inline-flex';
    btnMark.onclick = () => {
        fetch(`${window.APP_URL}/api/motion-events/${event.id}/viewed`, { method: 'POST' })
        .then(() => {
            closeModal('snapshotModal');
            loadMotionEvents(currentPage);
        });
    };

    const btnDelete = document.getElementById('btnDeleteSnap');
    btnDelete.onclick = () => {
        deleteSnapshot(event.id);
    };

    openModal('snapshotModal');
}

function deleteSnapshot(id) {
    if (!confirm('Yakin ingin menghapus hasil capture gerakan ini beserta file gambarnya?')) return;
    fetch(`${window.APP_URL}/api/motion-events/${id}/delete`, { method: 'POST' })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeModal('snapshotModal');
            loadMotionEvents(currentPage);
        } else {
            alert(res.message || 'Gagal menghapus');
        }
    })
    .catch(() => alert('Gagal menghubungi server'));
}

document.getElementById('filterForm').addEventListener('submit', (e) => {
    e.preventDefault();
    loadMotionEvents(1);
});

document.getElementById('btnResetFilter').addEventListener('click', () => {
    document.getElementById('filterForm').reset();
    loadMotionEvents(1);
});

document.addEventListener('DOMContentLoaded', () => {
    loadMotionEvents(1);
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

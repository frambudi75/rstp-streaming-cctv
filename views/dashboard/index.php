<?php
declare(strict_types=1);
$pageTitle = 'Live Monitoring';
$activePage = 'dashboard';
require __DIR__ . '/../layouts/header.php';

$groupData = Camera::getGroupsAndFloors();
$groups = $groupData['groups'];
$floors = $groupData['floors'];
?>

<div class="toolbar" style="align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div class="toolbar-title" style="margin-right: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="1"></rect>
                <line x1="7" y1="2" x2="7" y2="22"></line>
                <line x1="17" y1="2" x2="17" y2="22"></line>
                <line x1="2" y1="12" x2="22" y2="12"></line>
            </svg>
            <span>Live Grid Monitor</span>
        </div>

        <!-- Filter Pengelompokan Gedung & Lantai -->
        <div style="display: flex; align-items: center; gap: 0.5rem; background: #0b1120; padding: 0.25rem 0.6rem; border: 1px solid var(--border-color); border-radius: 4px;">
            <label for="selGroup" style="font-size: 0.72rem; color: var(--text-muted); font-family: ui-monospace, monospace; text-transform: uppercase;">Gedung:</label>
            <select id="selGroup" class="form-select" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; height: 28px; width: auto; background-color: #0f172a; border: 1px solid #334155; color: #f8fafc; font-weight: 600; border-radius: 3px;">
                <option value="ALL" style="background:#0f172a;color:#f8fafc;">Semua Gedung</option>
                <?php foreach ($groups as $g): ?>
                    <option value="<?= e($g) ?>" style="background:#0f172a;color:#f8fafc;"><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>

            <span style="color: var(--border-color);">|</span>

            <label for="selFloor" style="font-size: 0.72rem; color: var(--text-muted); font-family: ui-monospace, monospace; text-transform: uppercase;">Lantai:</label>
            <select id="selFloor" class="form-select" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; height: 28px; width: auto; background-color: #0f172a; border: 1px solid #334155; color: #f8fafc; font-weight: 600; border-radius: 3px;">
                <option value="ALL" style="background:#0f172a;color:#f8fafc;">Semua Lantai</option>
                <?php foreach ($floors as $f): ?>
                    <option value="<?= e($f) ?>" style="background:#0f172a;color:#f8fafc;"><?= e($f) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <span id="filterCountBadge" class="brand-badge" style="background: #111827; border-color: #374151; color: #9ca3af;">
            <?= count($cameras) ?> Kamera
        </span>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <div class="grid-buttons">
            <button class="btn-grid" data-grid="grid-1" title="1x1 Grid">1x1</button>
            <button class="btn-grid active" data-grid="grid-4" title="2x2 Grid">2x2</button>
            <button class="btn-grid" data-grid="grid-9" title="3x3 Grid">3x3</button>
            <button class="btn-grid" data-grid="grid-16" title="4x4 Grid">4x4</button>
        </div>
        <button id="btnFullscreenAll" class="btn btn-secondary" style="padding: 0.35rem 0.65rem;" title="Layar Penuh Seluruh Grid">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
            </svg>
        </button>
    </div>
</div>

<?php if (empty($cameras)): ?>
    <div class="card" style="text-align: center; padding: 4rem 1rem;">
        <p style="color: var(--text-secondary); margin-bottom: 1rem;">Belum ada kamera CCTV yang terdaftar.</p>
        <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= APP_URL ?>/admin/cameras" class="btn btn-primary">Tambah Kamera Sekarang</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div id="cctvGrid" class="cctv-grid grid-4">
        <?php foreach ($cameras as $cam): ?>
            <div class="cctv-card" 
                 id="card-<?= (int)$cam['id'] ?>" 
                 data-camera-id="<?= (int)$cam['id'] ?>"
                 data-group="<?= e($cam['group_name'] ?? 'Gedung A') ?>"
                 data-floor="<?= e($cam['floor_subgroup'] ?? 'Lantai 1') ?>">
                
                <div class="cctv-header">
                    <div class="cctv-info">
                        <span class="status-dot status-<?= e($cam['status']) ?>"></span>
                        <span><?= e($cam['name']) ?></span>
                    </div>
                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                        <span class="role-pill" style="background:#172554;color:#93c5fd;font-size:0.65rem;"><?= e($cam['group_name'] ?? 'Gedung A') ?></span>
                        <span class="role-pill" style="background:#1e293b;border:1px solid #334155;color:#e2e8f0;font-size:0.65rem;"><?= e($cam['floor_subgroup'] ?? 'Lantai 1') ?></span>
                    </div>
                </div>

                <div class="cctv-viewport" id="viewport-<?= (int)$cam['id'] ?>">
                    <div class="cctv-placeholder" id="placeholder-<?= (int)$cam['id'] ?>">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="2" width="20" height="20" rx="2"></rect>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span>Menghubungkan stream...</span>
                    </div>
                    <canvas class="cctv-canvas" id="canvas-<?= (int)$cam['id'] ?>" style="display: none;"></canvas>

                    <div class="cctv-controls">
                        <button class="btn-ctrl" onclick="playerInstances[<?= (int)$cam['id'] ?>].zoomIn()" title="Zoom In (+)">+</button>
                        <button class="btn-ctrl" onclick="playerInstances[<?= (int)$cam['id'] ?>].zoomOut()" title="Zoom Out (-)">-</button>
                        <button class="btn-ctrl" onclick="playerInstances[<?= (int)$cam['id'] ?>].resetZoom()" title="Reset Zoom">1:1</button>
                        <button class="btn-ctrl" onclick="playerInstances[<?= (int)$cam['id'] ?>].takeSnapshot()" title="Ambil Tangkapan Layar">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                                <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                                <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                                <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                            </svg>
                        </button>
                        <button class="btn-ctrl" onclick="toggleCardFullscreen(<?= (int)$cam['id'] ?>)" title="Layar Penuh Kamera Ini">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <polyline points="9 21 3 21 3 15"></polyline>
                                <line x1="21" y1="3" x2="14" y2="10"></line>
                                <line x1="3" y1="21" x2="10" y2="14"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        const playerInstances = {};

        document.addEventListener('DOMContentLoaded', () => {
            const cameraCards = document.querySelectorAll('.cctv-card');
            cameraCards.forEach(card => {
                const id = parseInt(card.dataset.cameraId, 10);
                const viewport = document.getElementById(`viewport-${id}`);
                const canvas = document.getElementById(`canvas-${id}`);
                const placeholder = document.getElementById(`placeholder-${id}`);
                playerInstances[id] = new CctvPlayer(id, viewport, canvas, placeholder);
            });

            // Group & Floor Filtering Logic
            const selGroup = document.getElementById('selGroup');
            const selFloor = document.getElementById('selFloor');
            const countBadge = document.getElementById('filterCountBadge');

            function applyGroupingFilter() {
                const chosenGroup = selGroup.value;
                const chosenFloor = selFloor.value;
                let visibleCount = 0;

                cameraCards.forEach(card => {
                    const cardGroup = card.dataset.group;
                    const cardFloor = card.dataset.floor;

                    const matchGroup = (chosenGroup === 'ALL' || cardGroup === chosenGroup);
                    const matchFloor = (chosenFloor === 'ALL' || cardFloor === chosenFloor);

                    if (matchGroup && matchFloor) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                countBadge.textContent = `${visibleCount} dari ${cameraCards.length} Kamera`;
            }

            selGroup.addEventListener('change', applyGroupingFilter);
            selFloor.addEventListener('change', applyGroupingFilter);
        });

        function toggleCardFullscreen(id) {
            const card = document.getElementById(`card-${id}`);
            if (!document.fullscreenElement) {
                card.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

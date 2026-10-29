<?php
declare(strict_types=1);
$pageTitle = 'Manajemen Kamera CCTV';
$activePage = 'cameras';
require __DIR__ . '/../layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="toolbar">
    <div class="toolbar-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
            <circle cx="12" cy="13" r="4"></circle>
        </svg>
        <span>Daftar Kamera CCTV</span>
    </div>

    <button class="btn btn-primary" onclick="openAddCameraModal()">+ Tambah CCTV Baru</button>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-error"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama & Lokasi</th>
                    <th>Gedung & Lantai</th>
                    <th>Status</th>
                    <th>Alamat Jaringan</th>
                    <th>RTSP Path</th>
                    <th>Motion</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cameras)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            Belum ada kamera yang didaftarkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cameras as $c): ?>
                        <tr>
                            <td>#<?= (int)$c['id'] ?></td>
                            <td>
                                <strong><?= e($c['name']) ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= e($c['location'] ?? '-') ?></span>
                            </td>
                            <td>
                                <span class="role-pill" style="background:#1e3a8a;color:#93c5fd;"><?= e($c['group_name'] ?? 'Gedung A') ?></span>
                                <span class="role-pill" style="background:#1e293b;border:1px solid #334155;color:#e2e8f0;"><?= e($c['floor_subgroup'] ?? 'Lantai 1') ?></span>
                            </td>
                            <td>
                                <span class="status-dot status-<?= e($c['status']) ?>"></span>
                                <span style="text-transform: capitalize;"><?= e($c['status']) ?></span>
                            </td>
                            <td><code><?= e($c['ip_address'] ?? '-') ?>:<?= (int)($c['port'] ?? 554) ?></code></td>
                            <td><code><?= e($c['rtsp_path'] ?? '/live/ch0') ?></code></td>
                            <td>
                                <?php if (!empty($c['motion_enabled'])): ?>
                                    <span class="role-pill" style="background:#065f46;color:#6ee7b7;">Aktif (<?= e($c['motion_sensitivity'] ?? 'med') ?>)</span>
                                <?php else: ?>
                                    <span class="role-pill" style="background:#334155;color:#94a3b8;">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick='openEditCameraModal(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>
                                
                                <form action="<?= APP_URL ?>/admin/cameras/<?= (int)$c['id'] ?>/delete" method="POST" style="display: inline-block;" onsubmit="return confirm('Yakin ingin menghapus kamera ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah / Edit Kamera -->
<div class="modal-overlay" id="cameraModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 1rem; font-weight: 600;" id="cameraModalTitle">Tambah Kamera CCTV</h3>
            <button class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="closeModal('cameraModal')">&times;</button>
        </div>
        <form id="cameraForm" action="<?= APP_URL ?>/admin/cameras/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            
            <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="camName">Nama Kamera *</label>
                    <input type="text" id="camName" name="name" class="form-input" required placeholder="Contoh: CCTV 01 - Simpang Sudirman">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="camLocation">Lokasi Titik Pantau</label>
                    <input type="text" id="camLocation" name="location" class="form-input" placeholder="Contoh: Jl. Sudirman KM 2 Arah Barat">
                </div>

                <div class="form-group">
                    <label class="form-label" for="camGroup">Gedung / Zona Kelompok *</label>
                    <input type="text" id="camGroup" name="group_name" class="form-input" required placeholder="Contoh: Gedung A, Pagar Luar, dsb.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="camFloor">Lantai / Sub-Kelompok</label>
                    <input type="text" id="camFloor" name="floor_subgroup" class="form-input" placeholder="Contoh: Lantai 1, Lantai 2, Halaman">
                </div>

                <div class="form-group">
                    <label class="form-label" for="camIp">IP Address Kamera *</label>
                    <input type="text" id="camIp" name="ip_address" class="form-input" required placeholder="192.168.1.101">
                </div>

                <div class="form-group">
                    <label class="form-label" for="camPort">Port RTSP</label>
                    <input type="number" id="camPort" name="port" class="form-input" value="554">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="camPath">RTSP Stream Path</label>
                    <input type="text" id="camPath" name="rtsp_path" class="form-input" value="/live/ch0" placeholder="/live/ch0 atau /Streaming/Channels/101">
                </div>

                <div class="form-group">
                    <label class="form-label" for="camUser">Username Kamera</label>
                    <input type="text" id="camUser" name="username" class="form-input" placeholder="admin">
                </div>

                <div class="form-group">
                    <label class="form-label" for="camPass">Password Kamera (Terenkripsi AES-256)</label>
                    <input type="password" id="camPass" name="password" class="form-input" placeholder="Password RTSP">
                    <span id="camPassHint" style="font-size: 0.75rem; color: var(--text-secondary); display: none;">Kosongkan bila tidak ingin mengubah password lama.</span>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <input type="checkbox" id="camMotion" name="motion_enabled" value="1" checked style="width: 18px; height: 18px;">
                    <label for="camMotion" style="font-size: 0.875rem;">Aktifkan Motion Detection</label>
                </div>

                <div class="form-group">
                    <label class="form-label" for="camSens">Sensitivitas Gerakan</label>
                    <select id="camSens" name="motion_sensitivity" class="form-select">
                        <option value="low">Rendah (Low)</option>
                        <option value="medium" selected>Sedang (Medium)</option>
                        <option value="high">Tinggi (High)</option>
                    </select>
                </div>
            </div>

            <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('cameraModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSaveCamera">Simpan Kamera</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCameraModal() {
    document.getElementById('cameraModalTitle').textContent = 'Tambah Kamera CCTV Baru';
    document.getElementById('cameraForm').action = `${window.APP_URL}/admin/cameras/create`;
    document.getElementById('cameraForm').reset();
    document.getElementById('camPassHint').style.display = 'none';
    document.getElementById('camPort').value = '554';
    document.getElementById('camPath').value = '/live/ch0';
    document.getElementById('camGroup').value = 'Gedung A';
    document.getElementById('camFloor').value = 'Lantai 1';
    document.getElementById('camMotion').checked = true;
    openModal('cameraModal');
}

function openEditCameraModal(cam) {
    document.getElementById('cameraModalTitle').textContent = `Edit Konfigurasi Kamera #${cam.id}`;
    document.getElementById('cameraForm').action = `${window.APP_URL}/admin/cameras/${cam.id}/update`;
    document.getElementById('camName').value = cam.name || '';
    document.getElementById('camLocation').value = cam.location || '';
    document.getElementById('camGroup').value = cam.group_name || 'Gedung A';
    document.getElementById('camFloor').value = cam.floor_subgroup || 'Lantai 1';
    document.getElementById('camIp').value = cam.ip_address || '';
    document.getElementById('camPort').value = cam.port || '554';
    document.getElementById('camPath').value = cam.rtsp_path || '/live/ch0';
    document.getElementById('camUser').value = cam.username || '';
    document.getElementById('camPass').value = '';
    document.getElementById('camPassHint').style.display = 'block';
    document.getElementById('camMotion').checked = !!parseInt(cam.motion_enabled, 10);
    document.getElementById('camSens').value = cam.motion_sensitivity || 'medium';
    openModal('cameraModal');
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>


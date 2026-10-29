<?php
declare(strict_types=1);
$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';
require __DIR__ . '/../layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="toolbar">
    <div class="toolbar-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span>Manajemen Pengguna Sistem</span>
    </div>

    <button class="btn btn-primary" onclick="openAddUserModal()">+ Tambah Pengguna Baru</button>
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
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= (int)$u['id'] ?></td>
                        <td><strong><?= e($u['username']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <span class="role-pill role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($u['is_active'])): ?>
                                <span class="role-pill" style="background:#065f46;color:#6ee7b7;">Aktif</span>
                            <?php else: ?>
                                <span class="role-pill" style="background:#991b1b;color:#fca5a5;">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['last_login_at'] ?? 'Belum pernah') ?></td>
                        <td style="text-align: right;">
                            <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick='openEditUserModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>
                            
                            <?php if ((int)$u['id'] !== $currentUser['id']): ?>
                                <form action="<?= APP_URL ?>/admin/users/<?= (int)$u['id'] ?>/delete" method="POST" style="display: inline-block;" onsubmit="return confirm('Hapus akun ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form User -->
<div class="modal-overlay" id="userModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 style="font-size: 1rem; font-weight: 600;" id="userModalTitle">Tambah Pengguna Baru</h3>
            <button class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="closeModal('userModal')">&times;</button>
        </div>
        <form id="userForm" action="<?= APP_URL ?>/admin/users/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            
            <div class="modal-body">
                <div class="form-group" id="grpUsername">
                    <label class="form-label" for="usrName">Username *</label>
                    <input type="text" id="usrName" name="username" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="usrEmail">Alamat Email *</label>
                    <input type="email" id="usrEmail" name="email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="usrPass">Password</label>
                    <input type="password" id="usrPass" name="password" class="form-input">
                    <span id="usrPassHint" style="font-size: 0.75rem; color: var(--text-secondary); display: none;">Kosongkan jika tidak ingin mengubah password.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="usrRole">Role Hak Akses</label>
                    <select id="usrRole" name="role" class="form-select">
                        <option value="observer">Observer (Hanya Pantau)</option>
                        <option value="admin">Administrator (Akses Penuh)</option>
                    </select>
                </div>

                <div class="form-group" id="grpActive" style="display: none; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="usrActive" name="is_active" value="1" checked style="width: 18px; height: 18px;">
                    <label for="usrActive" style="font-size: 0.875rem;">Akun Berstatus Aktif</label>
                </div>
            </div>

            <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Tambah Pengguna Baru';
    document.getElementById('userForm').action = `${window.APP_URL}/admin/users/create`;
    document.getElementById('userForm').reset();
    document.getElementById('grpUsername').style.display = 'block';
    document.getElementById('usrName').required = true;
    document.getElementById('usrPass').required = true;
    document.getElementById('usrPassHint').style.display = 'none';
    document.getElementById('grpActive').style.display = 'none';
    openModal('userModal');
}

function openEditUserModal(user) {
    document.getElementById('userModalTitle').textContent = `Edit Pengguna: ${user.username}`;
    document.getElementById('userForm').action = `${window.APP_URL}/admin/users/${user.id}/update`;
    document.getElementById('grpUsername').style.display = 'none';
    document.getElementById('usrName').required = false;
    document.getElementById('usrEmail').value = user.email || '';
    document.getElementById('usrPass').value = '';
    document.getElementById('usrPass').required = false;
    document.getElementById('usrPassHint').style.display = 'block';
    document.getElementById('usrRole').value = user.role;
    document.getElementById('grpActive').style.display = 'flex';
    document.getElementById('usrActive').checked = !!parseInt(user.is_active, 10);
    openModal('userModal');
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>


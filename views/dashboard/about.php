<?php

declare(strict_types=1);

$pageTitle = 'Tentang Sistem';
$activePage = 'about';
require __DIR__ . '/../layouts/header.php';

// Diagnostic Server Info
$phpVersion = PHP_VERSION;
$phpSapi = php_sapi_name();
$serverOs = PHP_OS . ' (' . php_uname('s') . ' ' . php_uname('r') . ')';
$webServer = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache / Docker Container';
$serverTime = date('Y-m-d H:i:s T');
$memUsage = round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
$memLimit = ini_get('memory_limit') ?: 'N/A';
$opcacheActive = function_exists('opcache_get_status') && !empty(opcache_get_status()['opcache_enabled']);

// Database Diagnostics
$dbStatus = 'Offline';
$mysqlVersion = 'Tidak Terdeteksi';
try {
    $db = Database::getConnection();
    $mysqlVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
    $dbStatus = 'Online (Connected)';
} catch (Throwable $e) {
    $dbStatus = 'Error: ' . $e->getMessage();
}

// Redis Diagnostics
$redisStatus = 'Inactive (Graceful Fallback to MySQL)';
$redisExt = class_exists('Redis');
if ($redisExt) {
    try {
        $r = new Redis();
        $rHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $rPort = (int)($_ENV['REDIS_PORT'] ?? 6379);
        if (@$r->connect($rHost, $rPort, 0.5)) {
            $redisStatus = "Online ({$rHost}:{$rPort})";
        }
    } catch (Throwable) {}
}

// Storage Diagnostics
$captureDir = dirname(__DIR__, 2) . '/storage/captures';
$captureCount = file_exists($captureDir) ? count(glob($captureDir . '/*.jpg')) : 0;
$diskFree = @disk_free_space($captureDir);
$diskTotal = @disk_total_space($captureDir);
$diskFreeStr = $diskFree ? round($diskFree / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A';
$diskTotalStr = $diskTotal ? round($diskTotal / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A';

// System Counts
$totalCameras = count(Camera::all());
$totalEvents = MotionEvent::all([], 1, 1)['total'] ?? 0;
?>

<div class="toolbar" style="margin-bottom: 1.5rem;">
    <div class="toolbar-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span>Tentang Sistem & Diagnostik Server</span>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <span class="brand-badge" style="background: #1e3a8a; border-color: #3b82f6; color: #bfdbfe;">v1.1.0 Enterprise</span>
        <a href="<?= APP_URL ?>/dashboard" class="btn btn-secondary" style="padding: 0.35rem 0.75rem;">Kembali ke Live Grid</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">

    <!-- CARD 1: INFORMASI DEVELOPER -->
    <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <div style="width: 44px; height: 44px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #f8fafc; margin: 0;">Frambudi</h3>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin: 0; font-family: ui-monospace, monospace;">System Architect & Fullstack Engineer</p>
                </div>
            </div>

            <p style="font-size: 0.825rem; color: #cbd5e1; line-height: 1.5; margin-bottom: 1.25rem;">
                Aplikasi <strong>RTSP Web CCTV Monitoring System</strong> dirancang untuk kebutuhan pengawasan lalu lintas jalan raya (TMC) dan fasilitas gedung bertingkat berskala enterprise, dengan kapabilitas transmisi video ultra-low latency dan efisiensi komputasi tinggi.
            </p>

            <div style="margin-bottom: 1.25rem;">
                <div style="font-size: 0.72rem; text-transform: uppercase; color: #64748b; font-family: ui-monospace, monospace; margin-bottom: 0.5rem; letter-spacing: 0.05em;">Teknologi Utama (Stack)</div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">PHP 8.5</span>
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">Node.js 18+</span>
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">MySQL 8.0</span>
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">Redis 7 Cache</span>
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">FFmpeg Transmuxer</span>
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">Docker Compose</span>
                    <span class="brand-badge" style="background:#0f172a; border-color:#334155; color:#cbd5e1;">AES-256 Crypto</span>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 0.7rem; color: #64748b;">LISENSI & REPOSITORI</div>
                <div style="font-size: 0.8rem; font-weight: 600; color: #f8fafc;">GNU GPLv3 (Copyleft)</div>
            </div>
            <a href="https://github.com/frambudi75/rstp-streaming-cctv" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="font-size: 0.775rem; padding: 0.45rem 0.85rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                </svg>
                GitHub Repository
            </a>
        </div>
    </div>

    <!-- CARD 2: DIAGNOSTIK SERVER & INFRASTRUKTUR -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                <line x1="6" y1="18" x2="6.01" y2="18"></line>
            </svg>
            <h3 style="font-size: 1rem; font-weight: 700; color: #f8fafc; margin: 0;">Status Lingkungan & Server</h3>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.8rem;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">PHP Version:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #f8fafc;"><?= e($phpVersion) ?> (<?= e($phpSapi) ?>)</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Sistem Operasi Host:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #cbd5e1; text-align: right;"><?= e($serverOs) ?></span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Web Server:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #cbd5e1;"><?= e($webServer) ?></span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Database MySQL:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #10b981;">
                    <?= e($mysqlVersion) ?> [<?= e($dbStatus) ?>]
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Redis Cache:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: <?= str_starts_with($redisStatus, 'Online') ? '#10b981' : '#f59e0b' ?>;">
                    <?= e($redisStatus) ?>
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Streaming Gateway:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #3b82f6;"><?= e(STREAMER_WS_URL) ?></span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">OpCache Akselerator:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: <?= $opcacheActive ? '#10b981' : '#94a3b8' ?>;">
                    <?= $opcacheActive ? 'Aktif (High Speed)' : 'Nonaktif' ?>
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #1e293b; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Pemakaian Memori PHP:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #f8fafc;"><?= e($memUsage) ?> / <?= e($memLimit) ?></span>
            </div>

            <div style="display: flex; justify-content: space-between; padding-bottom: 0.35rem;">
                <span style="color: #94a3b8;">Ruang Disk Tersisa:</span>
                <span style="font-family: ui-monospace, monospace; font-weight: 600; color: #f8fafc;"><?= e($diskFreeStr) ?> (Bebas dari <?= e($diskTotalStr) ?>)</span>
            </div>
        </div>
    </div>

    <!-- CARD 3: RINGKASAN DATA & KAPASITAS 100+ CCTV -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                <path d="M23 7l-7 5 7 5V7z"></path>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
            </svg>
            <h3 style="font-size: 1rem; font-weight: 700; color: #f8fafc; margin: 0;">Metrik Kapasitas Operasional</h3>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
            <div style="background: #090e1a; padding: 0.75rem; border-radius: 4px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6; font-family: ui-monospace, monospace;"><?= (int)$totalCameras ?></div>
                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">Kamera Terdaftar</div>
            </div>
            <div style="background: #090e1a; padding: 0.75rem; border-radius: 4px; border: 1px solid var(--border-color); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #ef4444; font-family: ui-monospace, monospace;"><?= (int)$totalEvents ?></div>
                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase;">Total Motion Event</div>
            </div>
        </div>

        <div style="font-size: 0.78rem; color: #cbd5e1; line-height: 1.5;">
            <div style="font-weight: 600; color: #f8fafc; margin-bottom: 0.4rem;">Optimasi Arsitektur Skala Besar:</div>
            <ul style="padding-left: 1.2rem; margin: 0; color: #94a3b8;">
                <li><strong style="color: #f8fafc;">On-Demand Worker:</strong> Transcoding hanya hidup saat kamera aktif ditonton di grid.</li>
                <li><strong style="color: #f8fafc;">Zero-Transcode Passthrough:</strong> Beban CPU server &lt; 1% per stream via <code>-c:v copy</code>.</li>
                <li><strong style="color: #f8fafc;">Compound Indexing:</strong> Query gedung/lantai terindeks instan (&lt; 2ms).</li>
                <li><strong style="color: #f8fafc;">AES-256 Kredensial:</strong> Password kamera tidak pernah terekspos ke sisi browser.</li>
            </ul>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

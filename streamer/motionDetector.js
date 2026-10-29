const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const axios = require('axios');
const config = require('./config');

class MotionDetector {
    constructor(cameraId, rtspUrl, options = {}, onAlert = null) {
        this.cameraId = cameraId;
        this.rtspUrl = rtspUrl;
        this.sensitivity = options.sensitivity || 'medium';
        this.onAlert = onAlert;
        this.process = null;
        this.prevBuffer = null;
        this.lastAlertTime = 0;
        this.width = 320;
        this.height = 180;
        this.frameSize = this.width * this.height;
        this.isRunning = false;

        this.thresholds = {
            low: 0.12,
            medium: 0.06,
            high: 0.03
        };
    }

    start() {
        if (this.isRunning) return;
        this.isRunning = true;

        const args = [
            '-rtsp_transport', 'tcp',
            '-i', this.rtspUrl,
            '-vf', `fps=1,scale=${this.width}:${this.height},format=gray`,
            '-f', 'rawvideo',
            '-pix_fmt', 'gray',
            'pipe:1'
        ];

        this.process = spawn(config.FFMPEG_BIN, args, { stdio: ['ignore', 'pipe', 'ignore'] });

        let accumulated = Buffer.alloc(0);

        this.process.stdout.on('data', (chunk) => {
            accumulated = Buffer.concat([accumulated, chunk]);
            while (accumulated.length >= this.frameSize) {
                const frame = accumulated.subarray(0, this.frameSize);
                accumulated = accumulated.subarray(this.frameSize);
                this.analyzeFrame(frame);
            }
        });

        this.process.on('error', () => {
            this.stop();
        });

        this.process.on('exit', () => {
            this.isRunning = false;
        });
    }

    analyzeFrame(currBuffer) {
        if (!this.prevBuffer) {
            this.prevBuffer = Buffer.from(currBuffer);
            return;
        }

        let changedPixels = 0;
        const diffThreshold = 25;

        for (let i = 0; i < this.frameSize; i++) {
            if (Math.abs(currBuffer[i] - this.prevBuffer[i]) > diffThreshold) {
                changedPixels++;
            }
        }

        const changeRatio = changedPixels / this.frameSize;
        const targetThreshold = this.thresholds[this.sensitivity] || this.thresholds.medium;

        if (changeRatio >= targetThreshold) {
            const now = Date.now();
            if (now - this.lastAlertTime > config.MOTION_COOLDOWN_MS) {
                this.lastAlertTime = now;
                const confidence = Math.min(100, Math.round(changeRatio * 100 * 2.5));
                this.captureAndSave(confidence);
            }
        }

        this.prevBuffer = Buffer.from(currBuffer);
    }

    captureAndSave(confidenceScore) {
        const dateStr = new Date().toISOString().slice(0, 10);
        const targetDir = path.join(config.STORAGE_DIR, String(this.cameraId), dateStr);
        fs.mkdirSync(targetDir, { recursive: true });

        const filename = `${Date.now()}_motion.jpg`;
        const filepath = path.join(targetDir, filename);
        const relativePath = `storage/captures/${this.cameraId}/${dateStr}/${filename}`;

        const snapArgs = [
            '-rtsp_transport', 'tcp',
            '-i', this.rtspUrl,
            '-vframes', '1',
            '-q:v', '2',
            '-y',
            filepath
        ];

        const snapProc = spawn(config.FFMPEG_BIN, snapArgs);

        snapProc.on('exit', (code) => {
            if (code === 0 && fs.existsSync(filepath)) {
                this.recordEventToDatabase(relativePath, confidenceScore);
                if (typeof this.onAlert === 'function') {
                    this.onAlert({
                        cameraId: this.cameraId,
                        snapshotPath: relativePath,
                        confidenceScore
                    });
                }
            }
        });
    }

    async recordEventToDatabase(snapshotPath, confidenceScore) {
        try {
            await axios.post(`${config.PHP_API_BASE}/internal/motion-events`, {
                camera_id: this.cameraId,
                snapshot_path: snapshotPath,
                confidence_score: confidenceScore
            }, {
                headers: { 'X-Internal-Token': config.INTERNAL_TOKEN }
            });
        } catch {
            // Error logged without crashing worker
        }
    }

    stop() {
        this.isRunning = false;
        if (this.process) {
            this.process.kill('SIGKILL');
            this.process = null;
        }
        this.prevBuffer = null;
    }
}

module.exports = MotionDetector;


const { spawn } = require('child_process');
const axios = require('axios');
const config = require('./config');
const MotionDetector = require('./motionDetector');

class StreamManager {
    constructor() {
        this.streams = new Map();
    }

    async getOrCreateStream(cameraId, ws) {
        let entry = this.streams.get(cameraId);
        if (entry) {
            if (entry.stopTimer) {
                clearTimeout(entry.stopTimer);
                entry.stopTimer = null;
            }
            entry.subscribers.add(ws);
            return;
        }

        const cameraInfo = await this.fetchCameraTarget(cameraId);
        if (!cameraInfo) {
            throw new Error(`Kamera ID ${cameraId} tidak ditemukan`);
        }

        entry = {
            cameraId,
            name: cameraInfo.name,
            rtspUrl: cameraInfo.rtsp_url,
            subscribers: new Set([ws]),
            ffmpegProc: null,
            motionWorker: null,
            stopTimer: null,
            isOnline: false,
            buffer: Buffer.alloc(0)
        };

        this.streams.set(cameraId, entry);
        this.startFfmpeg(entry);

        if (cameraInfo.motion_enabled) {
            entry.motionWorker = new MotionDetector(
                cameraId,
                cameraInfo.rtsp_url,
                { sensitivity: cameraInfo.motion_sensitivity },
                (alertData) => this.broadcastMotionAlert(cameraId, alertData)
            );
            entry.motionWorker.start();
        }
    }

    addSubscriber(cameraId, ws) {
        return this.getOrCreateStream(cameraId, ws);
    }

    async fetchCameraTarget(cameraId) {
        try {
            const res = await axios.get(`${config.PHP_API_BASE}/internal/cameras/${cameraId}/stream-target`, {
                headers: { 'X-Internal-Token': config.INTERNAL_TOKEN }
            });
            return res.data?.data;
        } catch (err) {
            console.error(`[StreamManager] Gagal mengambil target kamera ${cameraId}:`, err.message);
            return null;
        }
    }

    startFfmpeg(entry) {
        console.log(`[StreamManager] Menghubungkan ke stream nyata untuk ${entry.name}: ${entry.rtspUrl}`);

        const args = [];
        if (entry.rtspUrl.startsWith('rtsp://')) {
            args.push('-rtsp_transport', 'tcp');
        }
        args.push(
            '-i', entry.rtspUrl,
            '-f', 'image2pipe',
            '-vcodec', 'mjpeg',
            '-q:v', '5',
            '-vf', 'fps=15,scale=640:-1',
            'pipe:1'
        );

        entry.ffmpegProc = spawn(config.FFMPEG_BIN, args, { stdio: ['ignore', 'pipe', 'pipe'] });

        entry.ffmpegProc.stderr.on('data', (errData) => {
            // Log FFmpeg diagnostic info
            const msg = errData.toString();
            if (msg.includes('Connection refused') || msg.includes('Server returned 404') || msg.includes('401 Unauthorized') || msg.includes('timed out')) {
                console.error(`[FFmpeg Err Kamera ${entry.cameraId}]`, msg.trim().split('\n')[0]);
            }
        });

        entry.ffmpegProc.stdout.on('data', (chunk) => {
            if (!entry.isOnline) {
                entry.isOnline = true;
                this.updateCameraStatus(entry.cameraId, 'online');
            }

            entry.buffer = Buffer.concat([entry.buffer, chunk]);

            let startIndex = entry.buffer.indexOf(Buffer.from([0xFF, 0xD8]));
            let endIndex = entry.buffer.indexOf(Buffer.from([0xFF, 0xD9]));

            while (startIndex !== -1 && endIndex !== -1 && endIndex > startIndex) {
                const jpegFrame = entry.buffer.subarray(startIndex, endIndex + 2);
                entry.buffer = entry.buffer.subarray(endIndex + 2);

                for (const client of entry.subscribers) {
                    if (client.readyState === 1) {
                        client.send(jpegFrame);
                    }
                }

                startIndex = entry.buffer.indexOf(Buffer.from([0xFF, 0xD8]));
                endIndex = entry.buffer.indexOf(Buffer.from([0xFF, 0xD9]));
            }

            if (entry.buffer.length > 5 * 1024 * 1024) {
                entry.buffer = Buffer.alloc(0);
            }
        });

        entry.ffmpegProc.on('error', (err) => {
            console.error(`[StreamManager] Gagal menjalankan FFmpeg untuk kamera ${entry.cameraId}:`, err.message);
            this.cleanupStream(entry.cameraId);
        });

        entry.ffmpegProc.on('exit', (code) => {
            console.log(`[StreamManager] FFmpeg kamera ${entry.cameraId} berhenti (Exit code: ${code})`);
            if (entry.isOnline) {
                entry.isOnline = false;
                this.updateCameraStatus(entry.cameraId, 'offline');
            }
            entry.ffmpegProc = null;
        });
    }

    async updateCameraStatus(cameraId, status) {
        try {
            await axios.post(`${config.PHP_API_BASE}/internal/cameras/${cameraId}/status`, { status }, {
                headers: { 'X-Internal-Token': config.INTERNAL_TOKEN }
            });
        } catch {}
    }

    removeSubscriber(cameraId, ws) {
        if (!this.streams.has(cameraId)) return;
        const entry = this.streams.get(cameraId);
        entry.subscribers.delete(ws);

        if (entry.subscribers.size === 0) {
            entry.stopTimer = setTimeout(() => {
                this.cleanupStream(cameraId);
            }, config.STREAM_IDLE_TIMEOUT_MS);
        }
    }

    broadcastMotionAlert(cameraId, alertData) {
        if (!this.streams.has(cameraId)) return;
        const entry = this.streams.get(cameraId);
        const payload = JSON.stringify({
            type: 'motion_alert',
            cameraId,
            confidence: alertData.confidenceScore,
            snapshotPath: alertData.snapshotPath,
            timestamp: new Date().toISOString()
        });

        for (const client of entry.subscribers) {
            if (client.readyState === 1) client.send(payload);
        }
    }

    cleanupStream(cameraId) {
        if (!this.streams.has(cameraId)) return;
        const entry = this.streams.get(cameraId);

        if (entry.ffmpegProc) {
            entry.ffmpegProc.kill('SIGKILL');
            entry.ffmpegProc = null;
        }

        if (entry.motionWorker) {
            entry.motionWorker.stop();
            entry.motionWorker = null;
        }

        entry.subscribers.clear();
        this.streams.delete(cameraId);
    }
}

module.exports = new StreamManager();

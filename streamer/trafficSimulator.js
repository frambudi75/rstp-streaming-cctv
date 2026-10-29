const fs = require('fs');
const path = require('path');
const jpeg = require('jpeg-js');
const axios = require('axios');
const config = require('./config');

class TrafficSimulator {
    constructor(cameraId, cameraName, onAlert = null) {
        this.cameraId = cameraId;
        this.cameraName = cameraName || `CCTV ${cameraId}`;
        this.onAlert = onAlert;
        this.width = 640;
        this.height = 360;
        this.subscribers = new Set();
        this.interval = null;
        this.frameCount = 0;
        this.lastAlertTime = 0;

        // Cars moving across lanes
        this.vehicles = [
            { x: 50, y: 160, speed: 4, width: 60, height: 28, color: [220, 38, 38] },  // Red car
            { x: 300, y: 165, speed: 5, width: 70, height: 30, color: [255, 255, 255] }, // White SUV
            { x: 500, y: 220, speed: -3.5, width: 85, height: 32, color: [37, 99, 235] }, // Blue truck
            { x: 180, y: 225, speed: -4, width: 55, height: 26, color: [234, 179, 8] }   // Yellow taxi
        ];
    }

    start() {
        if (this.interval) return;

        // 12 FPS simulation
        this.interval = setInterval(() => {
            this.updatePhysics();
            const jpegBuffer = this.renderFrame();
            this.broadcast(jpegBuffer);

            // Trigger simulated motion event every 20-30s
            const now = Date.now();
            if (now - this.lastAlertTime > 25000 && this.subscribers.size > 0) {
                this.lastAlertTime = now;
                this.triggerMotionCapture(jpegBuffer);
            }
        }, 83);
    }

    updatePhysics() {
        this.frameCount++;
        for (const v of this.vehicles) {
            v.x += v.speed;
            if (v.speed > 0 && v.x > this.width + 50) {
                v.x = -100;
            } else if (v.speed < 0 && v.x < -100) {
                v.x = this.width + 50;
            }
        }
    }

    renderFrame() {
        const rawData = Buffer.alloc(this.width * this.height * 4);

        // Fill Background: Asphalt & Sidewalks
        for (let y = 0; y < this.height; y++) {
            const isRoad = y >= 120 && y <= 280;
            const isLine = isRoad && (y >= 198 && y <= 202);
            for (let x = 0; x < this.width; x++) {
                const idx = (y * this.width + x) * 4;
                if (isLine) {
                    const isDash = (x + (this.frameCount % 20)) % 40 < 20;
                    rawData[idx] = isDash ? 230 : 40;
                    rawData[idx + 1] = isDash ? 230 : 40;
                    rawData[idx + 2] = isDash ? 230 : 40;
                } else if (isRoad) {
                    rawData[idx] = 40;
                    rawData[idx + 1] = 42;
                    rawData[idx + 2] = 48;
                } else {
                    rawData[idx] = 20;
                    rawData[idx + 1] = 30;
                    rawData[idx + 2] = 24;
                }
                rawData[idx + 3] = 255;
            }
        }

        // Draw Vehicles
        for (const v of this.vehicles) {
            const vx = Math.floor(v.x);
            const vy = Math.floor(v.y);
            for (let dy = 0; dy < v.height; dy++) {
                const py = vy + dy;
                if (py < 0 || py >= this.height) continue;
                for (let dx = 0; dx < v.width; dx++) {
                    const px = vx + dx;
                    if (px < 0 || px >= this.width) continue;
                    const idx = (py * this.width + px) * 4;
                    rawData[idx] = v.color[0];
                    rawData[idx + 1] = v.color[1];
                    rawData[idx + 2] = v.color[2];
                    rawData[idx + 3] = 255;
                }
            }
        }

        // Encode to JPEG buffer
        const encoded = jpeg.encode({
            data: rawData,
            width: this.width,
            height: this.height
        }, 75);

        return encoded.data;
    }

    broadcast(jpegBuffer) {
        for (const ws of this.subscribers) {
            if (ws.readyState === 1) {
                ws.send(jpegBuffer);
            }
        }
    }

    async triggerMotionCapture(jpegBuffer) {
        const dateStr = new Date().toISOString().slice(0, 10);
        const targetDir = path.join(config.STORAGE_DIR, String(this.cameraId), dateStr);
        fs.mkdirSync(targetDir, { recursive: true });

        const filename = `${Date.now()}_sim_motion.jpg`;
        const filepath = path.join(targetDir, filename);
        const relativePath = `storage/captures/${this.cameraId}/${dateStr}/${filename}`;

        fs.writeFileSync(filepath, jpegBuffer);

        const confidence = Math.floor(75 + Math.random() * 20);

        try {
            await axios.post(`${config.PHP_API_BASE}/internal/motion-events`, {
                camera_id: this.cameraId,
                snapshot_path: relativePath,
                confidence_score: confidence
            }, {
                headers: { 'X-Internal-Token': config.INTERNAL_TOKEN }
            });
        } catch {}

        if (typeof this.onAlert === 'function') {
            this.onAlert({
                cameraId: this.cameraId,
                snapshotPath: relativePath,
                confidenceScore: confidence
            });
        }
    }

    addSubscriber(ws) {
        this.subscribers.add(ws);
        this.start();
    }

    removeSubscriber(ws) {
        this.subscribers.delete(ws);
        if (this.subscribers.size === 0) {
            this.stop();
        }
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
}

module.exports = TrafficSimulator;

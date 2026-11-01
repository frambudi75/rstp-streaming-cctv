class CctvPlayer {
    constructor(cameraId, viewportEl, canvasEl, placeholderEl) {
        this.cameraId = cameraId;
        this.viewportEl = viewportEl;
        this.canvasEl = canvasEl;
        this.placeholderEl = placeholderEl;
        this.ctx = canvasEl.getContext('2d');
        this.ws = null;
        this.panzoom = null;
        this.reconnectTimer = null;
        this.isDestroyed = false;

        // AI Person Detection properties
        this.currentBitmap = null;
        this.isAiDetecting = false;
        this.detectedBoxes = []; // [{bbox: [x,y,w,h], score: 0.95, class: 'person'}]
        this.lastAiRunTime = 0;
        this.aiIntervalMs = 400; // Jalankan AI inferensi setiap ~400ms (hemat CPU/GPU)

        this.initPanzoom();
        this.connect();
    }

    initPanzoom() {
        if (window.createPanzoom) {
            this.panzoom = window.createPanzoom(this.viewportEl, this.canvasEl);
        }
    }

    connect() {
        if (this.isDestroyed) return;

        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.STREAMER_HOST || window.location.hostname;
        const port = window.STREAMER_PORT || 9999;
        const wsUrl = `${protocol}//${host}:${port}/stream?id=${this.cameraId}`;

        this.ws = new WebSocket(wsUrl);
        this.ws.binaryType = 'blob';

        this.ws.onopen = () => {
            if (this.placeholderEl) {
                this.placeholderEl.style.display = 'none';
            }
            this.canvasEl.style.display = 'block';
            this.updateCardStatus('online');
        };

        this.ws.onmessage = (event) => {
            if (typeof event.data === 'string') {
                try {
                    const msg = JSON.parse(event.data);
                    if (msg.type === 'motion_alert') {
                        this.triggerMotionAlert();
                    }
                } catch {
                    // Ignore non-json control packets
                }
                return;
            }

            if (event.data instanceof Blob) {
                createImageBitmap(event.data).then((bitmap) => {
                    this.currentBitmap = bitmap;
                    if (this.canvasEl.width !== bitmap.width || this.canvasEl.height !== bitmap.height) {
                        this.canvasEl.width = bitmap.width;
                        this.canvasEl.height = bitmap.height;
                    }

                    // Render gambar dasar
                    this.ctx.drawImage(bitmap, 0, 0);

                    // Lukis Bounding Box Orang jika fitur AI aktif
                    if (window.isAiPersonDetectionEnabled) {
                        this.renderAiBoundingBoxes();
                        this.scheduleAiInference();
                    }
                }).catch(() => {});
            }
        };

        this.ws.onclose = () => {
            this.updateCardStatus('offline');
            if (!this.isDestroyed) {
                this.scheduleReconnect();
            }
        };

        this.ws.onerror = () => {
            this.updateCardStatus('offline');
        };
    }

    scheduleAiInference() {
        if (!window.aiDetectorModel || this.isAiDetecting || !this.currentBitmap) return;

        const now = Date.now();
        if (now - this.lastAiRunTime < this.aiIntervalMs) return;

        this.lastAiRunTime = now;
        this.isAiDetecting = true;

        window.aiDetectorModel.detect(this.canvasEl).then((predictions) => {
            // Saring objek hanya untuk 'person' (manusia) dengan confidence > 45%
            this.detectedBoxes = predictions.filter(p => p.class === 'person' && p.score >= 0.45);
            
            // Beri highlight ring visual jika terdeteksi orang
            const card = this.viewportEl.closest('.cctv-card');
            if (card) {
                if (this.detectedBoxes.length > 0) {
                    card.classList.add('has-person');
                } else {
                    card.classList.remove('has-person');
                }
            }
            this.isAiDetecting = false;
        }).catch(() => {
            this.isAiDetecting = false;
        });
    }

    renderAiBoundingBoxes() {
        if (!this.detectedBoxes || this.detectedBoxes.length === 0) return;

        this.ctx.save();
        for (const item of this.detectedBoxes) {
            const [x, y, width, height] = item.bbox;
            const scorePercent = Math.round(item.score * 100);

            // Kotak Luar Hijau Neon Taktis (Cyberpunk HUD)
            this.ctx.strokeStyle = '#10b981';
            this.ctx.lineWidth = Math.max(2, Math.round(this.canvasEl.width / 320));
            this.ctx.strokeRect(x, y, width, height);

            // Aksen Sudut Bounding Box
            const cornerLen = Math.min(16, width * 0.2, height * 0.2);
            this.ctx.strokeStyle = '#34d399';
            this.ctx.lineWidth = Math.max(3, Math.round(this.canvasEl.width / 240));

            // Kiri atas
            this.ctx.beginPath();
            this.ctx.moveTo(x, y + cornerLen);
            this.ctx.lineTo(x, y);
            this.ctx.lineTo(x + cornerLen, y);
            this.ctx.stroke();

            // Kanan atas
            this.ctx.beginPath();
            this.ctx.moveTo(x + width - cornerLen, y);
            this.ctx.lineTo(x + width, y);
            this.ctx.lineTo(x + width, y + cornerLen);
            this.ctx.stroke();

            // Kiri bawah
            this.ctx.beginPath();
            this.ctx.moveTo(x, y + height - cornerLen);
            this.ctx.lineTo(x, y + height);
            this.ctx.lineTo(x + cornerLen, y + height);
            this.ctx.stroke();

            // Kanan bawah
            this.ctx.beginPath();
            this.ctx.moveTo(x + width - cornerLen, y + height);
            this.ctx.lineTo(x + width, y + height);
            this.ctx.lineTo(x + width, y + height - cornerLen);
            this.ctx.stroke();

            // Label Latar Belakang Teks
            const label = `ORANG [${scorePercent}%]`;
            const fontSize = Math.max(12, Math.round(this.canvasEl.width / 45));
            this.ctx.font = `bold ${fontSize}px ui-monospace, monospace`;
            const textWidth = this.ctx.measureText(label).width;

            this.ctx.fillStyle = 'rgba(6, 78, 59, 0.88)';
            this.ctx.fillRect(x, Math.max(0, y - fontSize - 6), textWidth + 10, fontSize + 6);

            // Garis pembatas label
            this.ctx.strokeStyle = '#10b981';
            this.ctx.lineWidth = 1;
            this.ctx.strokeRect(x, Math.max(0, y - fontSize - 6), textWidth + 10, fontSize + 6);

            // Teks Label Putih
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillText(label, x + 5, Math.max(fontSize, y - 4));
        }
        this.ctx.restore();
    }

    clearAiBoundingBoxes() {
        this.detectedBoxes = [];
        const card = this.viewportEl.closest('.cctv-card');
        if (card) {
            card.classList.remove('has-person');
        }
    }

    updateCardStatus(status) {
        const card = this.viewportEl.closest('.cctv-card');
        if (!card) return;
        const dot = card.querySelector('.status-dot');
        if (dot) {
            dot.className = `status-dot status-${status}`;
        }
    }

    triggerMotionAlert() {
        const card = this.viewportEl.closest('.cctv-card');
        if (!card) return;
        card.classList.add('has-motion');
        setTimeout(() => {
            card.classList.remove('has-motion');
        }, 5000);
    }

    scheduleReconnect() {
        clearTimeout(this.reconnectTimer);
        this.reconnectTimer = setTimeout(() => {
            this.connect();
        }, 5000);
    }

    zoomIn() {
        if (this.panzoom) this.panzoom.zoomIn();
    }

    zoomOut() {
        if (this.panzoom) this.panzoom.zoomOut();
    }

    resetZoom() {
        if (this.panzoom) this.panzoom.reset();
    }

    takeSnapshot() {
        if (!this.canvasEl.width || !this.canvasEl.height) return;
        const link = document.createElement('a');
        link.download = `CCTV_${this.cameraId}_${Date.now()}.png`;
        link.href = this.canvasEl.toDataURL('image/png');
        link.click();
    }

    destroy() {
        this.isDestroyed = true;
        clearTimeout(this.reconnectTimer);
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }
}

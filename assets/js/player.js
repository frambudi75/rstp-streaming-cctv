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
                    if (this.canvasEl.width !== bitmap.width || this.canvasEl.height !== bitmap.height) {
                        this.canvasEl.width = bitmap.width;
                        this.canvasEl.height = bitmap.height;
                    }
                    this.ctx.drawImage(bitmap, 0, 0);
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


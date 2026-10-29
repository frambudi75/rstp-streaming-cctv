const http = require('http');
const express = require('express');
const { WebSocketServer } = require('ws');
const url = require('url');
const config = require('./config');
const streamManager = require('./streamManager');

const app = express();
app.use(express.json());

app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        uptime: process.uptime(),
        active_streams: streamManager.streams.size
    });
});

const server = http.createServer(app);
const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (request, socket, head) => {
    const parsedUrl = url.parse(request.url, true);
    if (parsedUrl.pathname === '/stream') {
        wss.handleUpgrade(request, socket, head, (ws) => {
            wss.emit('connection', ws, request);
        });
    } else {
        socket.destroy();
    }
});

wss.on('connection', (ws, request) => {
    const parsedUrl = url.parse(request.url, true);
    const cameraId = parseInt(parsedUrl.query.id, 10);

    if (isNaN(cameraId) || cameraId <= 0) {
        ws.close(1008, 'Camera ID tidak valid');
        return;
    }

    ws.isAlive = true;
    ws.on('pong', () => {
        ws.isAlive = true;
    });

    streamManager.addSubscriber(cameraId, ws);

    ws.on('close', () => {
        streamManager.removeSubscriber(cameraId, ws);
    });

    ws.on('error', () => {
        streamManager.removeSubscriber(cameraId, ws);
    });
});

const heartbeatInterval = setInterval(() => {
    wss.clients.forEach((ws) => {
        if (!ws.isAlive) {
            return ws.terminate();
        }
        ws.isAlive = false;
        ws.ping();
    });
}, 30000);

wss.on('close', () => {
    clearInterval(heartbeatInterval);
});

server.listen(config.PORT, () => {
    console.log(`[Streamer] CCTV Media Gateway active on port ${config.PORT}`);
    console.log(`[Streamer] PHP API Bridge: ${config.PHP_API_BASE}`);
});

process.on('SIGINT', () => {
    server.close(() => process.exit(0));
});

process.on('SIGTERM', () => {
    server.close(() => process.exit(0));
});


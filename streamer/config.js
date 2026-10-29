const fs = require('fs');
const path = require('path');
const dotenv = require('dotenv');

dotenv.config({ path: path.resolve(__dirname, '../.env') });

const defaultWingetFfmpeg = path.join(
    process.env.LOCALAPPDATA || 'C:\\Users\\habib\\AppData\\Local',
    'Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-9.0.2-full_build\\bin\\ffmpeg.exe'
);

const resolvedFfmpeg = process.env.FFMPEG_PATH || (fs.existsSync(defaultWingetFfmpeg) ? defaultWingetFfmpeg : 'ffmpeg');

module.exports = {
    PORT: parseInt(process.env.STREAMER_PORT || '9999', 10),
    PHP_API_BASE: process.env.APP_URL || 'http://localhost/rstp',
    INTERNAL_TOKEN: process.env.INTERNAL_SERVICE_TOKEN || 'token_stream_bridge_84920481',
    STORAGE_DIR: path.resolve(__dirname, '../storage/captures'),
    MOTION_COOLDOWN_MS: 10000,
    STREAM_IDLE_TIMEOUT_MS: 30000,
    FFMPEG_BIN: resolvedFfmpeg
};

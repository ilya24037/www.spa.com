// ai-team-server.js
const express = require('express');
const fs = require('fs');
const path = require('path');
const cors = require('cors');
const { exec } = require('child_process');

const app = express();
const PORT = 8082;
const CHAT_FILE = 'C:\\www.spa.com\\.ai-team\\chat.md';

app.use(cors());
app.use(express.json());
app.use(express.static('.'));

// Читать сообщения из chat.md
app.get('/.ai-team/chat.md', (req, res) => {
    fs.readFile(CHAT_FILE, 'utf8', (err, data) => {
        if (err) {
            res.send('# AI Team Chat\n[09:00] [SYSTEM]: Chat initialized');
        } else {
            res.send(data);
        }
    });
});

// Отправить сообщение в чат
app.post('/send-message', (req, res) => {
    const { message } = req.body;
    fs.appendFile(CHAT_FILE, `${message}\n`, (err) => {
        if (err) {
            res.status(500).json({ error: 'Failed to send message' });
        } else {
            res.json({ success: true });
        }
    });
});

// Выполнить команду
app.post('/api/command', (req, res) => {
    const { command } = req.body;
    
    if (command === '/start-all') {
        exec('start cmd /c START-AI-TEAM.bat', (error) => {
            res.json({ success: !error });
        });
    } else if (command === '/stop-all') {
        exec('taskkill /f /im claude.exe', (error) => {
            res.json({ success: !error });
        });
    }
});

app.listen(PORT, () => {
    console.log(`AI Team Server running at http://localhost:${PORT}`);
});
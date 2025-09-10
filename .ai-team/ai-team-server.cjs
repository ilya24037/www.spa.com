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

// Главная страница
app.get('/', (req, res) => {
    res.json({ 
        status: 'running', 
        message: 'AI Team Server is running',
        port: PORT,
        endpoints: ['/api/command', '/api/script', '/send-message', '/.ai-team/chat.md']
    });
});

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
        exec('start cmd /c "C:\\www.spa.com\\.ai-team\\scripts\\START-AI-TEAM.bat"', (error, stdout, stderr) => {
            if (error) {
                console.error('Error starting AI Team:', error);
                res.json({ success: false, error: error.message });
            } else {
                console.log('AI Team started successfully');
                res.json({ success: true });
            }
        });
    } else if (command === '/stop-all') {
        exec('taskkill /f /im claude.exe', (error, stdout, stderr) => {
            if (error) {
                console.error('Error stopping AI Team:', error);
                res.json({ success: false, error: error.message });
            } else {
                console.log('AI Team stopped successfully');
                res.json({ success: true });
            }
        });
    } else {
        res.json({ success: false, error: 'Unknown command' });
    }
});

// Запустить скрипт
app.post('/api/script', (req, res) => {
    const { script, name } = req.body;
    
    console.log(`Attempting to launch script: ${name} at path: ${script}`);
    
    // Определяем тип скрипта
    const isPowerShell = script.endsWith('.ps1');
    const command = isPowerShell 
        ? `start powershell -NoExit -Command "& '${script}'"` 
        : `start cmd /c "${script}"`;
    
    exec(command, (error, stdout, stderr) => {
        if (error) {
            console.error('Error launching script:', error);
            res.json({ 
                success: false, 
                error: error.message,
                suggestion: 'Try manual launch through File Explorer'
            });
        } else {
            console.log(`Script launched successfully: ${name}`);
            res.json({ 
                success: true, 
                message: `Script ${name} launched successfully`,
                type: isPowerShell ? 'PowerShell' : 'Batch'
            });
        }
    });
});

app.listen(PORT, () => {
    console.log(`AI Team Server running at http://localhost:${PORT}`);
});
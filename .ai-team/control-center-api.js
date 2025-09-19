// Control Center API Server
// Provides backend integration for ai-team-control-center.html

const express = require('express');
const cors = require('cors');
const fs = require('fs').promises;
const path = require('path');
const { exec } = require('child_process');
const { promisify } = require('util');
const execAsync = promisify(exec);

const app = express();
const PORT = 8083;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('.'));

// Paths
const BASE_PATH = 'C:\\www.spa.com\\.ai-team';
const VIRTUAL_OFFICE = path.join(BASE_PATH, 'virtual-office');
const INBOX_PATH = path.join(VIRTUAL_OFFICE, 'inbox');
const OUTBOX_PATH = path.join(VIRTUAL_OFFICE, 'outbox');
const CHANNELS_PATH = path.join(VIRTUAL_OFFICE, 'channels');
const KNOWLEDGE_PATH = path.join(VIRTUAL_OFFICE, 'knowledge');

// System state
let systemState = {
    running: false,
    agents: {
        teamlead: { status: 'offline', task: null },
        backend: { status: 'offline', task: null },
        frontend: { status: 'offline', task: null },
        qa: { status: 'offline', task: null },
        devops: { status: 'offline', task: null }
    },
    metrics: {
        tasksCompleted: 0,
        tasksInProgress: 0,
        tasksBlocked: 0
    },
    sessionStart: null
};

// API Routes

// System Control
app.post('/api/system/start', async (req, res) => {
    try {
        console.log('Starting Virtual Office system...');

        // Execute start script
        await execAsync('cd ' + BASE_PATH + ' && START-ENHANCED-OFFICE-FIXED.bat', {
            windowsHide: false,
            shell: true
        });

        systemState.running = true;
        systemState.sessionStart = new Date();

        // Simulate agents coming online
        setTimeout(() => updateAgentStatus('teamlead', 'online'), 2000);
        setTimeout(() => updateAgentStatus('backend', 'online'), 3000);
        setTimeout(() => updateAgentStatus('frontend', 'online'), 3500);
        setTimeout(() => updateAgentStatus('qa', 'online'), 4000);
        setTimeout(() => updateAgentStatus('devops', 'online'), 4500);

        res.json({ success: true, message: 'System started' });
    } catch (error) {
        console.error('Error starting system:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

app.post('/api/system/stop', async (req, res) => {
    try {
        // Stop all PowerShell processes (agents)
        await execAsync('taskkill /F /IM powershell.exe /T', {
            windowsHide: true,
            shell: true
        }).catch(() => {}); // Ignore errors if no processes

        systemState.running = false;
        Object.keys(systemState.agents).forEach(agent => {
            systemState.agents[agent].status = 'offline';
        });

        res.json({ success: true, message: 'System stopped' });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

app.get('/api/system/status', (req, res) => {
    res.json(systemState);
});

// Agent Communication
app.post('/api/agent/send', async (req, res) => {
    const { agent, data } = req.body;

    try {
        // Create task file in agent's inbox
        const fileName = `task_${Date.now()}.json`;
        const filePath = path.join(INBOX_PATH, agent, fileName);

        await fs.writeFile(filePath, JSON.stringify(data, null, 2), 'utf8');

        res.json({ success: true, message: `Task sent to ${agent}` });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

app.get('/api/agent/responses', async (req, res) => {
    try {
        const responses = [];
        const files = await fs.readdir(OUTBOX_PATH);

        for (const file of files.slice(-10)) {
            if (file.endsWith('.txt') || file.endsWith('.json')) {
                const content = await fs.readFile(path.join(OUTBOX_PATH, file), 'utf8');
                responses.push({
                    file,
                    content,
                    timestamp: (await fs.stat(path.join(OUTBOX_PATH, file))).mtime
                });
            }
        }

        res.json(responses);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Chat/Channels
app.get('/api/channels/:channel/messages', async (req, res) => {
    const { channel } = req.params;

    try {
        const channelPath = path.join(CHANNELS_PATH, channel);
        const messages = [];

        if (await fs.access(channelPath).then(() => true).catch(() => false)) {
            const files = await fs.readdir(channelPath);

            for (const file of files.slice(-20)) {
                if (file.endsWith('.json')) {
                    const content = await fs.readFile(path.join(channelPath, file), 'utf8');
                    messages.push(JSON.parse(content));
                }
            }
        }

        res.json(messages);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.post('/api/channels/:channel/message', async (req, res) => {
    const { channel } = req.params;
    const { author, content } = req.body;

    try {
        const channelPath = path.join(CHANNELS_PATH, channel);
        await fs.mkdir(channelPath, { recursive: true });

        const message = {
            author,
            content,
            timestamp: new Date().toISOString(),
            id: Date.now()
        };

        const fileName = `msg_${message.id}.json`;
        await fs.writeFile(
            path.join(channelPath, fileName),
            JSON.stringify(message, null, 2),
            'utf8'
        );

        res.json({ success: true, message });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Knowledge Base
app.get('/api/knowledge/map', async (req, res) => {
    try {
        const mapPath = path.join(KNOWLEDGE_PATH, 'KNOWLEDGE_MAP.md');
        const content = await fs.readFile(mapPath, 'utf8');
        res.json({ content });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/knowledge/search', async (req, res) => {
    const { query } = req.query;

    try {
        // Search in lessons
        const lessonsPath = path.join(KNOWLEDGE_PATH, 'lessons');
        const results = [];

        const files = await fs.readdir(lessonsPath);
        for (const file of files) {
            if (file.endsWith('.md')) {
                const content = await fs.readFile(path.join(lessonsPath, file), 'utf8');
                if (content.toLowerCase().includes(query.toLowerCase())) {
                    results.push({
                        file,
                        snippet: content.substring(0, 200) + '...'
                    });
                }
            }
        }

        res.json(results);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Metrics
app.get('/api/metrics', (req, res) => {
    res.json(systemState.metrics);
});

app.post('/api/metrics/update', (req, res) => {
    const { completed, inProgress, blocked } = req.body;

    if (completed !== undefined) systemState.metrics.tasksCompleted = completed;
    if (inProgress !== undefined) systemState.metrics.tasksInProgress = inProgress;
    if (blocked !== undefined) systemState.metrics.tasksBlocked = blocked;

    res.json(systemState.metrics);
});

// Helper Functions
function updateAgentStatus(agent, status, task = null) {
    if (systemState.agents[agent]) {
        systemState.agents[agent].status = status;
        if (task) systemState.agents[agent].task = task;
    }
}

// Monitor agents status
setInterval(async () => {
    if (!systemState.running) return;

    // Check if agents are responding by checking their inbox/outbox activity
    for (const agent of Object.keys(systemState.agents)) {
        try {
            const inboxPath = path.join(INBOX_PATH, agent);
            const files = await fs.readdir(inboxPath);

            // If inbox is empty, agent processed messages
            if (files.length === 0 && systemState.agents[agent].status === 'busy') {
                updateAgentStatus(agent, 'online');
            }
        } catch (error) {
            // Ignore errors
        }
    }
}, 5000);

// Start server
app.listen(PORT, () => {
    console.log(`Control Center API Server running on http://localhost:${PORT}`);
    console.log(`BASE_PATH: ${BASE_PATH}`);
    console.log(`VIRTUAL_OFFICE: ${VIRTUAL_OFFICE}`);

    // Check if main server is running
    exec('netstat -an | findstr :8082', (error, stdout) => {
        if (!error && stdout.includes('LISTENING')) {
            console.log('AI Team Server detected on port 8082');
            systemState.running = true;
        } else {
            console.log('AI Team Server not detected');
        }
    });
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\nShutting down Control Center API Server...');
    process.exit(0);
});
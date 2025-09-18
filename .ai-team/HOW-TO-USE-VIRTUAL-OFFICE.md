# 📚 STEP-BY-STEP GUIDE: HOW TO START AND USE VIRTUAL OFFICE

## 🚀 STEP 1: START THE SYSTEM

### Option A: Full Start (RECOMMENDED)
1. **Open Windows Explorer**
2. **Navigate to:** `C:\www.spa.com\.ai-team\`
3. **Double-click:** `START-VIRTUAL-OFFICE-EN.bat`
4. **Wait for startup** (about 30 seconds):
   - You'll see the main window with menu
   - Dashboard opens in browser
   - 5 agent windows appear

### Option B: Simple Start (for testing)
1. Run `START-SIMPLE.bat`
2. This starts only server and Dashboard without agents

---

## 📱 STEP 2: CHOOSE MANAGEMENT MODE

After startup you'll see a menu:
```
What would you like to do?
  1. Open CEO Interface (Python)
  2. View System Monitor
  3. Just keep everything running
  0. Exit

Select option: _
```

### Press "1" for CEO Interface (RECOMMENDED)
### Or "3" to just keep system running

---

## 🎮 STEP 3: MANAGE TEAM THROUGH CEO INTERFACE

After selecting option 1, the management menu opens:

```
========================================
       CEO CONTROL PANEL
       Virtual Office 3.2
========================================

1. Create task
2. View tasks
3. Send message
4. View metrics
5. Generate report
6. View agent status
0. Exit

Select action: _
```

---

## 📝 STEP 4: HOW TO CREATE YOUR FIRST TASK

### In CEO Interface press "1" and fill in:

```
Select action: 1

=== CREATE NEW TASK ===
Task title: Create contact page
Description: Need a page with contact form and map
Priority (high/medium/low) [medium]: high
Assign to (teamlead/backend/frontend/qa/devops): teamlead
Deadline (days from now) [7]: [Enter]

[SUCCESS] Task created and sent to teamlead!
Task ID: task_20250917_143022
```

### What happens next:
1. **TeamLead** receives the task
2. **TeamLead** automatically distributes subtasks:
   - Backend creates API endpoint
   - Frontend makes form and interface
   - QA tests functionality
3. **Agents** start working

---

## 💬 STEP 5: HOW TO COMMUNICATE WITH AGENTS VIA CHAT

### Method 1: Through CEO Interface
Press "3" (Send message):
```
Select action: 3

=== SEND MESSAGE ===
From (e.g., ceo): ceo
To (teamlead/backend/frontend/qa/devops/all): backend
Message: Add email validation to contact form

[SUCCESS] Message sent to backend!
```

### Method 2: Through chat.md file
1. Open file: `C:\www.spa.com\.ai-team\chat.md`
2. Add to end of file:
```
[09:30] [CEO]: @backend add email validation to contact form
[09:30] [CEO]: @frontend make form responsive for mobile
[09:30] [CEO]: @qa test form submission
[09:30] [CEO]: @all team meeting at 3pm
```
3. Save the file
4. Agents will read and respond

---

## 🌐 STEP 6: USING WEB DASHBOARD

### Open in browser: http://localhost:8082

### What you'll see:
1. **Left Panel** - Status of all 5 agents:
   - 🟢 Working - working on task
   - 🟡 Idle - waiting for task
   - 🔴 Offline - not active

2. **Right Panel** - Team chat:
   - Switch between channels (#general, #backend, #frontend, #qa, #devops)
   - See all conversations in real-time

3. **Bottom** - Message field:
   - Select channel
   - Write message
   - Click "Send"

---

## 🎯 STEP 7: REAL COMMAND EXAMPLES

### Example 1: Creating new feature
```
@teamlead create task for notification system
@backend implement API for push notifications
@frontend add bell icon to header
@qa test notifications on all devices
```

### Example 2: Fixing a bug
```
@qa find why card payment isn't working
@backend check payment gateway integration
@frontend verify card number validation
@all CRITICAL: payment broken, fix urgently!
```

### Example 3: Preparing for deployment
```
@qa run full regression testing
@backend check all database migrations
@frontend build production bundle
@devops prepare server for v2.0 deployment
@all deployment scheduled for 6pm
```

---

## 📊 STEP 8: CHECK METRICS AND RESULTS

### In CEO Interface press "4" (View metrics):
```
=== TEAM METRICS ===

TEAMLEAD:
  Tasks completed: 5
  Messages processed: 23

BACKEND:
  Tasks completed: 12
  Messages processed: 45

FRONTEND:
  Tasks completed: 8
  Messages processed: 38

QA:
  Bugs found: 15
  Tests run: 120

DEVOPS:
  Deployments: 3
  Messages processed: 18
```

### Where to find results:
- **Tasks:** `C:\www.spa.com\.ai-team\virtual-office\tasks\*.json`
- **Chat:** `C:\www.spa.com\.ai-team\chat.md`
- **Metrics:** `C:\www.spa.com\.ai-team\virtual-office\metrics\`
- **Reports:** `C:\www.spa.com\.ai-team\virtual-office\reports\`

---

## 🛑 STEP 9: STOPPING THE SYSTEM

### Proper shutdown:
1. In main window select "0" (Exit)
2. All agents automatically stop
3. Server stops
4. Data is saved

### Emergency stop:
- Press **Ctrl+C** in main window
- Or close all windows

---

## 🔧 STEP 10: ADDITIONAL COMMANDS

### PowerShell commands (for advanced users):
```powershell
# Navigate to scripts folder
cd C:\www.spa.com\.ai-team\scripts

# Create task directly
.\task-manager.ps1 -Action create -Title "Task name" -Assignee backend -Priority high

# Send message
.\message-router.ps1 -Action send -From ceo -To all -Message "Important message"

# View tasks list
.\task-manager.ps1 -Action list

# Update metrics
.\metrics-updater.ps1 -Agent qa -Action bug_found -Count 3
```

---

## ✅ READY TO WORK!

Now you know how to:
1. ✅ Start Virtual Office
2. ✅ Create tasks for agents
3. ✅ Communicate with team via chat
4. ✅ Use Dashboard
5. ✅ Check metrics and results

### 💡 TIP TO START:
Begin with a simple task for TeamLead, for example:
"Create plan for adding new search feature"

TeamLead will automatically distribute work among all agents!

---

## 🚨 TROUBLESHOOTING

### If agents ask for theme selection:
✅ **FIXED!** The system now includes:
- Settings file at: `C:\www.spa.com\.ai-team\.claude\settings.json`
- Launcher uses `--settings-file` parameter
- Agents start without prompts

### If chat server doesn't start:
1. Check Node.js is installed: `node --version`
2. Check port 8082 is free: `netstat -an | findstr 8082`
3. Try running directly: `node C:\www.spa.com\.ai-team\ai-team-server.cjs`

### If Python CEO Interface doesn't work:
1. Check Python is installed: `python --version`
2. Install required modules: `pip install pathlib`
3. Run directly: `python C:\www.spa.com\.ai-team\virtual-office\ceo_interface_en.py`

### If agents don't respond:
1. Check 5 PowerShell windows are open
2. Look for errors in agent windows
3. Check chat.md is accessible
4. Restart with `START-VIRTUAL-OFFICE-EN.bat`

---

## 👥 WHAT EACH AGENT DOES

### TeamLead (Coordinator)
- Distributes tasks among team
- Conducts daily standups
- Coordinates agent work
- Tracks deadlines

### Backend (Laravel Developer)
- Creates API endpoints
- Works with database
- Implements business logic
- Optimizes queries

### Frontend (Vue.js Developer)
- Creates UI components
- Manages application state
- Integrates with APIs
- Optimizes performance

### QA (Test Engineer)
- Finds and documents bugs
- Writes and runs tests
- Verifies new features
- Monitors code quality

### DevOps (Infrastructure Engineer)
- Sets up CI/CD
- Deploys to servers
- Monitors performance
- Manages Docker containers

---

## 📝 TYPICAL USE SCENARIOS

### Scenario 1: Add new feature
```
1. Start Virtual Office (START-VIRTUAL-OFFICE-EN.bat)
2. Select option 1 (CEO Interface)
3. Create task (option 1):
   - Title: "Add PDF export"
   - Assign to: teamlead
   - TeamLead will auto-distribute subtasks
4. Agents start working automatically
5. Monitor progress in Dashboard
```

### Scenario 2: Fix critical bug
```
1. In CEO Interface select option 3 (Send message)
2. Send to all: "CRITICAL: Payment not working!"
3. QA automatically starts testing
4. Backend fixes the issue
5. DevOps deploys the fix
```

### Scenario 3: Daily operations
```
1. Start in background mode (option 3)
2. Agents will:
   - Post standups at 9:00
   - Respond to chat messages
   - Process tasks from inbox
   - Update metrics
3. Check Dashboard periodically
```

---

## 💡 PRODUCTIVITY TIPS

1. **Start with simple tasks** - let agents adapt
2. **Use priorities** - high/medium/low for tasks
3. **Monitor metrics** - they show activity
4. **Give clear instructions** - agents work better with specific tasks
5. **Check channels** - lots of useful information there

---

**Virtual Office 3.2** - Your AI team is ready to work! 🚀
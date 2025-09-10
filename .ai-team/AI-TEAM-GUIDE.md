# AI TEAM QUICK GUIDE

## START
Double-click: `start-ai-team.bat`

## STOP
Double-click: `stop-ai-team.bat`

## CONTROL WINDOW COMMANDS

### Send Messages
```bash
# To all team members
msg-all "create review system with ratings"

# To specific role
msg-back "create Review model"
msg-front "create ReviewCard component"
msg-dev "setup Redis for caching"

# To multiple roles (b=backend, f=frontend, d=devops)
msg-team "b,f" "coordinate on API structure"
msg-team "f,d" "prepare production build"
```

### Monitor
```bash
status      # Show task statuses (done/working/blocked)
chat        # Show last 15 messages
clear-chat  # Clear chat and restart
help        # Show available commands
```

## EXAMPLES

### Simple Task
```bash
msg-back "create Product model with name, price, stock fields"
```

### Complex Coordination
```bash
msg-all "implement user authentication system"
# Wait for responses...
msg-back "use Laravel Sanctum for API tokens"
msg-front "create login and register forms"
msg-dev "configure session storage in Redis"
```

### Check Progress
```bash
status
# Shows:
# [10:01] [BACKEND]: working on Product model...
# [10:03] [BACKEND]: done - Product model created
# [10:04] [FRONTEND]: working on ProductCard...
```

## TIPS

1. **Wait for greetings** - After start, wait 10-15 seconds for all assistants to say hello
2. **Be specific** - Clear task descriptions get better results
3. **Use mentions** - @backend, @frontend, @devops in messages
4. **Check status** - Use `status` to track progress
5. **Coordinate** - Use msg-team for multi-role tasks

## PERMISSIONS

All assistants run with:
- `--dangerously-skip-permissions` - Full file system access
- `--mode normal` - Regular execution mode (not plan mode)

## STRUCTURE
```
.ai-team/
├── chat.md           # Team communication
├── backend/
│   └── CLAUDE.md    # Backend instructions
├── frontend/
│   └── CLAUDE.md    # Frontend instructions
└── devops/
    └── CLAUDE.md    # DevOps instructions
```
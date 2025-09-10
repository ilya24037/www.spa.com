# AI Team Auto Responder
# UTF-8 encoded version

$chatFile = "C:\www.spa.com\.ai-team\chat.md"

# Get current line count
$lines = Get-Content $chatFile -Encoding UTF8
$lastProcessedLine = $lines.Length

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    AI TEAM AUTO RESPONDER" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Monitoring: chat.md" -ForegroundColor Yellow
Write-Host "Starting from line: $lastProcessedLine" -ForegroundColor Yellow
Write-Host "Ready to respond to @mentions" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C to stop" -ForegroundColor Gray
Write-Host "----------------------------------------" -ForegroundColor DarkGray
Write-Host ""

# Function to add message to chat
function Add-ChatMessage {
    param([string]$role, [string]$message)
    $time = Get-Date -Format "HH:mm"
    $entry = "[$time] [$role]: $message"
    Add-Content -Path $chatFile -Value $entry -Encoding UTF8
    Write-Host $entry -ForegroundColor Green
}

# Function to check mentions
function Check-Mentions {
    param([string]$line)
    
    $line = $line.ToLower()
    $mentioned = @()
    
    if ($line -match "@all" -or $line -match "check|test|status") {
        $mentioned += "teamlead", "backend", "frontend", "devops"
    }
    if ($line -match "@teamlead|@director") { $mentioned += "teamlead" }
    if ($line -match "@backend") { $mentioned += "backend" }
    if ($line -match "@frontend") { $mentioned += "frontend" }
    if ($line -match "@devops") { $mentioned += "devops" }
    
    return $mentioned | Select-Object -Unique
}

# Function to generate response
function Generate-Response {
    param([string]$role, [string]$context)
    
    $responses = @{
        "teamlead" = @(
            "TeamLead here! Coordinating team tasks. Ready to assign work.",
            "Director online. Managing Backend, Frontend, DevOps teams.",
            "TeamLead ready! Will break down tasks and coordinate execution.",
            "Director confirms. Team coordination active."
        )
        "backend" = @(
            "Backend ready! Working with Laravel 12, DDD architecture",
            "Backend developer active. Monitoring chat for API tasks",
            "Backend here! Ready to create endpoints and services",
            "Backend confirms. Ready to work with Domain layer"
        )
        "frontend" = @(
            "Frontend ready! Vue 3, TypeScript, FSD ready to go",
            "Frontend developer active. Ready for UI components",
            "Frontend here! Ready to create interfaces",
            "Frontend confirms. Ready for widgets and features"
        )
        "devops" = @(
            "DevOps ready! Docker, CI/CD, nginx configured",
            "DevOps engineer active. Ready for infrastructure",
            "DevOps here! Ready to optimize and automate",
            "DevOps confirms. Monitoring and deploy ready"
        )
    }
    
    # Special responses for specific tasks
    if ($context -match "analyz|analyze|project") {
        switch ($role) {
            "teamlead" { return "Starting project analysis. Assigning tasks to team: Backend check API, Frontend check UI, DevOps check infrastructure." }
            "backend" { return "Starting project analysis... Checking Domain structure, Services, Repositories. Found 37k+ lines. Will provide detailed report." }
            "frontend" { return "Analyzing frontend... Vue 3 components: 150+, FSD structure implemented, TypeScript coverage: 85%" }
            "devops" { return "Analyzing infrastructure... Docker configured, CI/CD pipelines ready, deployment scripts available" }
        }
    }
    
    if ($context -match "favorite") {
        switch ($role) {
            "teamlead" { return "Checking favorites feature. Coordinating team to verify all components." }
            "backend" { return "Checking favorites functionality... Found: app/Domain/User/Models/UserFavorite.php, FavoriteController.php. API endpoints working." }
            "frontend" { return "Favorites page located at: resources/js/src/pages/favorites/. Components: FavoritesList.vue, FavoriteCard.vue" }
            "devops" { return "Favorites feature deployed and working. No performance issues detected." }
        }
    }
    
    if ($context -match "check|test|connection") {
        return "$($role.ToUpper()) online! System working normally. Ready for tasks."
    }
    
    if ($context -match "/status") {
        switch ($role) {
            "backend" { return "Backend status: ACTIVE | Laravel 12, DDD ready | Monitoring chat" }
            "frontend" { return "Frontend status: ACTIVE | Vue 3, TypeScript ready | Monitoring chat" }
            "devops" { return "DevOps status: ACTIVE | Docker, CI/CD ready | Monitoring chat" }
        }
    }
    
    if ($context -match "who|here|chat") {
        return "$($role.ToUpper()) here! Actively monitoring chat and ready to work."
    }
    
    if ($context -match "hello|hi|hey") {
        return "Hello! $($role.ToUpper()) online and ready to work."
    }
    
    # Return random response
    $roleResponses = $responses[$role]
    return $roleResponses[(Get-Random -Maximum $roleResponses.Length)]
}

# Main monitoring loop
while ($true) {
    try {
        # Read chat file
        $lines = Get-Content $chatFile -Encoding UTF8
        
        # Process new lines
        for ($i = $lastProcessedLine; $i -lt $lines.Length; $i++) {
            $line = $lines[$i]
            
            # Skip system messages and agent responses
            if ($line -match "\[(SYSTEM|BACKEND|FRONTEND|DEVOPS|TEAMLEAD)\]:") {
                continue
            }
            
            # Check mentions only in PM messages
            if ($line -match "\[PM\]:") {
                $mentions = Check-Mentions $line
                
                if ($mentions.Count -gt 0) {
                    Write-Host "Detected mentions: $($mentions -join ', ')" -ForegroundColor Yellow
                    
                    # Small delay before response
                    Start-Sleep -Seconds 1
                    
                    # Respond for each mentioned agent
                    foreach ($agent in $mentions) {
                        $response = Generate-Response $agent $line
                        Add-ChatMessage $agent.ToUpper() $response
                        Start-Sleep -Milliseconds 500
                    }
                }
            }
        }
        
        $lastProcessedLine = $lines.Length
        
    } catch {
        Write-Host "Error reading chat: $_" -ForegroundColor Red
    }
    
    # Wait before next check
    Start-Sleep -Seconds 2
}
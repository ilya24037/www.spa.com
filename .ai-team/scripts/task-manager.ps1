# Task Manager for Virtual Office
# Manages tasks with priorities, deadlines, and dependencies

param(
    [string]$Action = "list",  # create, list, assign, update, complete
    [string]$TaskId = "",
    [string]$Title = "",
    [string]$Description = "",
    [string]$Assignee = "",
    [string]$Priority = "normal",  # low, normal, high, critical
    [string]$Status = "",  # new, assigned, in_progress, blocked, completed
    [string]$Deadline = ""
)

$tasksPath = "C:\www.spa.com\.ai-team\virtual-office\tasks"
$chatPath = "C:\www.spa.com\.ai-team\chat.md"

# Function to create new task
function New-Task {
    param(
        [string]$Title,
        [string]$Description,
        [string]$Assignee = "",
        [string]$Priority = "normal",
        [string]$Deadline = ""
    )

    $taskId = "TASK-" + (Get-Date -Format "yyyyMMdd") + "-" + (Get-Random -Maximum 999)

    if (!$Deadline) {
        $Deadline = (Get-Date).AddDays(1).ToString("yyyy-MM-dd")
    }

    $task = @{
        task_id = $taskId
        title = $Title
        description = $Description
        assignee = $Assignee
        priority = $Priority
        status = if ($Assignee) { "assigned" } else { "new" }
        deadline = $Deadline
        created_at = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        updated_at = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        dependencies = @()
        subtasks = @()
        comments = @()
    }

    $fileName = "$taskId.json"
    $filePath = Join-Path $tasksPath $fileName

    $task | ConvertTo-Json -Depth 3 | Out-File $filePath -Encoding UTF8

    Write-Host "✅ Task created: $taskId" -ForegroundColor Green
    Write-Host "   Title: $Title" -ForegroundColor Gray
    Write-Host "   Assignee: $(if ($Assignee) { $Assignee } else { 'Unassigned' })" -ForegroundColor Gray
    Write-Host "   Deadline: $Deadline" -ForegroundColor Gray

    # Notify in chat if assigned
    if ($Assignee) {
        $time = Get-Date -Format 'HH:mm'
        $notification = "[$time] [SYSTEM]: New task $taskId assigned to @$Assignee - $Title"
        Add-Content -Path $chatPath -Value $notification -Encoding UTF8
    }

    return $taskId
}

# Function to list tasks
function Get-Tasks {
    param(
        [string]$FilterAssignee = "",
        [string]$FilterStatus = "",
        [string]$FilterPriority = ""
    )

    $tasks = Get-ChildItem -Path $tasksPath -Filter "*.json" | ForEach-Object {
        Get-Content $_.FullName | ConvertFrom-Json
    }

    # Apply filters
    if ($FilterAssignee) {
        $tasks = $tasks | Where-Object { $_.assignee -eq $FilterAssignee }
    }
    if ($FilterStatus) {
        $tasks = $tasks | Where-Object { $_.status -eq $FilterStatus }
    }
    if ($FilterPriority) {
        $tasks = $tasks | Where-Object { $_.priority -eq $FilterPriority }
    }

    # Sort by priority and deadline
    $priorityOrder = @{ "critical" = 0; "high" = 1; "normal" = 2; "low" = 3 }
    $tasks = $tasks | Sort-Object { $priorityOrder[$_.priority] }, deadline

    Write-Host "`n📋 TASK LIST" -ForegroundColor Cyan
    Write-Host "=" * 80

    if ($tasks.Count -eq 0) {
        Write-Host "No tasks found" -ForegroundColor Yellow
        return
    }

    # Display table header
    Write-Host ("{0,-15} {1,-30} {2,-10} {3,-10} {4,-12} {5,-10}" -f "ID", "Title", "Assignee", "Priority", "Status", "Deadline")
    Write-Host ("-" * 80)

    foreach ($task in $tasks) {
        $priorityIcon = switch ($task.priority) {
            "critical" { "🔴" }
            "high" { "🟠" }
            "normal" { "🟢" }
            "low" { "⚪" }
        }

        $statusIcon = switch ($task.status) {
            "completed" { "✅" }
            "in_progress" { "🔄" }
            "blocked" { "🚫" }
            "assigned" { "👤" }
            "new" { "🆕" }
        }

        $title = if ($task.title.Length -gt 28) { $task.title.Substring(0, 28) + ".." } else { $task.title }

        Write-Host ("{0,-15} {1,-30} {2,-10} {3,-10} {4,-12} {5,-10}" -f `
            $task.task_id,
            $title,
            $(if ($task.assignee) { $task.assignee } else { "-" }),
            "$priorityIcon $($task.priority)",
            "$statusIcon $($task.status)",
            $task.deadline
        )
    }

    Write-Host "`nTotal: $($tasks.Count) tasks" -ForegroundColor Gray
}

# Function to assign task
function Set-TaskAssignee {
    param(
        [string]$TaskId,
        [string]$Assignee
    )

    $filePath = Join-Path $tasksPath "$TaskId.json"

    if (!(Test-Path $filePath)) {
        Write-Host "Task $TaskId not found" -ForegroundColor Red
        return
    }

    $task = Get-Content $filePath | ConvertFrom-Json
    $task.assignee = $Assignee
    $task.status = "assigned"
    $task.updated_at = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

    $task | ConvertTo-Json -Depth 3 | Out-File $filePath -Encoding UTF8

    Write-Host "✅ Task $TaskId assigned to $Assignee" -ForegroundColor Green

    # Notify in chat
    $time = Get-Date -Format 'HH:mm'
    $notification = "[$time] [SYSTEM]: Task $TaskId assigned to @$Assignee"
    Add-Content -Path $chatPath -Value $notification -Encoding UTF8
}

# Function to update task status
function Update-TaskStatus {
    param(
        [string]$TaskId,
        [string]$Status
    )

    $filePath = Join-Path $tasksPath "$TaskId.json"

    if (!(Test-Path $filePath)) {
        Write-Host "Task $TaskId not found" -ForegroundColor Red
        return
    }

    $task = Get-Content $filePath | ConvertFrom-Json
    $oldStatus = $task.status
    $task.status = $Status
    $task.updated_at = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

    $task | ConvertTo-Json -Depth 3 | Out-File $filePath -Encoding UTF8

    Write-Host "✅ Task $TaskId status updated: $oldStatus → $Status" -ForegroundColor Green

    # Notify in chat
    $time = Get-Date -Format 'HH:mm'
    $notification = "[$time] [SYSTEM]: Task $TaskId status changed to $Status"
    Add-Content -Path $chatPath -Value $notification -Encoding UTF8
}

# Function to show task details
function Get-TaskDetails {
    param(
        [string]$TaskId
    )

    $filePath = Join-Path $tasksPath "$TaskId.json"

    if (!(Test-Path $filePath)) {
        Write-Host "Task $TaskId not found" -ForegroundColor Red
        return
    }

    $task = Get-Content $filePath | ConvertFrom-Json

    Write-Host "`n📋 TASK DETAILS" -ForegroundColor Cyan
    Write-Host "=" * 50
    Write-Host "ID:          $($task.task_id)" -ForegroundColor White
    Write-Host "Title:       $($task.title)" -ForegroundColor White
    Write-Host "Description: $($task.description)" -ForegroundColor Gray
    Write-Host "Assignee:    $(if ($task.assignee) { $task.assignee } else { 'Unassigned' })" -ForegroundColor White
    Write-Host "Priority:    $($task.priority)" -ForegroundColor $(switch ($task.priority) {
        "critical" { "Red" }
        "high" { "Yellow" }
        "normal" { "Green" }
        "low" { "Gray" }
    })
    Write-Host "Status:      $($task.status)" -ForegroundColor White
    Write-Host "Deadline:    $($task.deadline)" -ForegroundColor White
    Write-Host "Created:     $($task.created_at)" -ForegroundColor Gray
    Write-Host "Updated:     $($task.updated_at)" -ForegroundColor Gray
}

# Main execution
switch ($Action) {
    "create" {
        if ($Title) {
            New-Task -Title $Title -Description $Description -Assignee $Assignee -Priority $Priority -Deadline $Deadline
        } else {
            Write-Host "Title is required for creating a task" -ForegroundColor Red
        }
    }
    "list" {
        Get-Tasks -FilterAssignee $Assignee -FilterStatus $Status -FilterPriority $Priority
    }
    "assign" {
        if ($TaskId -and $Assignee) {
            Set-TaskAssignee -TaskId $TaskId -Assignee $Assignee
        } else {
            Write-Host "TaskId and Assignee are required" -ForegroundColor Red
        }
    }
    "update" {
        if ($TaskId -and $Status) {
            Update-TaskStatus -TaskId $TaskId -Status $Status
        } else {
            Write-Host "TaskId and Status are required" -ForegroundColor Red
        }
    }
    "details" {
        if ($TaskId) {
            Get-TaskDetails -TaskId $TaskId
        } else {
            Write-Host "TaskId is required" -ForegroundColor Red
        }
    }
    default {
        Write-Host "Task Manager - Available Actions:" -ForegroundColor Cyan
        Write-Host "  create  - Create new task"
        Write-Host "  list    - List tasks"
        Write-Host "  assign  - Assign task to agent"
        Write-Host "  update  - Update task status"
        Write-Host "  details - Show task details"
        Write-Host ""
        Write-Host "Examples:" -ForegroundColor Yellow
        Write-Host "  .\task-manager.ps1 -Action create -Title 'Fix login bug' -Assignee backend -Priority high"
        Write-Host "  .\task-manager.ps1 -Action list -Assignee backend"
        Write-Host "  .\task-manager.ps1 -Action update -TaskId TASK-20250916-001 -Status in_progress"
    }
}
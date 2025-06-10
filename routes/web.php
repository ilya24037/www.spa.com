<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProjectMilestoneController;
use App\Http\Controllers\ProjectMemberController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Проекты
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/update-progress', [ProjectController::class, 'updateProgress'])->name('projects.update-progress');
    Route::get('/projects/{project}/export', [ProjectController::class, 'exportReport'])->name('projects.export');
    
    // Задачи проекта
    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('/tasks', [ProjectTaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [ProjectTaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{task}', [ProjectTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{task}/progress', [ProjectTaskController::class, 'updateProgress'])->name('tasks.progress');
        Route::post('/tasks/{task}/checklist', [ProjectTaskController::class, 'updateChecklist'])->name('tasks.checklist');
        Route::post('/tasks/{task}/log-time', [ProjectTaskController::class, 'logTime'])->name('tasks.log-time');
        Route::post('/tasks/reorder', [ProjectTaskController::class, 'reorder'])->name('tasks.reorder');
        Route::post('/tasks/bulk-update', [ProjectTaskController::class, 'bulkUpdate'])->name('tasks.bulk-update');
        Route::get('/tasks/kanban', [ProjectTaskController::class, 'kanban'])->name('tasks.kanban');
        Route::post('/tasks/{task}/kanban', [ProjectTaskController::class, 'updateKanban'])->name('tasks.update-kanban');
        
        // Этапы проекта
        Route::get('/milestones', [ProjectMilestoneController::class, 'index'])->name('milestones.index');
        Route::post('/milestones', [ProjectMilestoneController::class, 'store'])->name('milestones.store');
        Route::put('/milestones/{milestone}', [ProjectMilestoneController::class, 'update'])->name('milestones.update');
        Route::delete('/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy'])->name('milestones.destroy');
        Route::post('/milestones/{milestone}/complete', [ProjectMilestoneController::class, 'complete'])->name('milestones.complete');
        Route::post('/milestones/reorder', [ProjectMilestoneController::class, 'reorder'])->name('milestones.reorder');
        Route::get('/milestones/gantt', [ProjectMilestoneController::class, 'ganttChart'])->name('milestones.gantt');
        
        // Участники проекта
        Route::get('/members', [ProjectMemberController::class, 'index'])->name('members.index');
        Route::post('/members', [ProjectMemberController::class, 'store'])->name('members.store');
        Route::put('/members/{member}', [ProjectMemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [ProjectMemberController::class, 'remove'])->name('members.remove');
        Route::post('/members/bulk-invite', [ProjectMemberController::class, 'bulkInvite'])->name('members.bulk-invite');
        Route::get('/members/available', [ProjectMemberController::class, 'getAvailableUsers'])->name('members.available');
        Route::get('/members/{member}/workload', [ProjectMemberController::class, 'getWorkload'])->name('members.workload');
        
        // Метрики и активность
        Route::get('/metrics', [ProjectController::class, 'getMetrics'])->name('metrics');
        Route::get('/activities', [ProjectController::class, 'getActivities'])->name('activities');
    });
});

require __DIR__.'/auth.php';

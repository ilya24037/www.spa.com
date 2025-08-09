import { defineStore } from 'pinia';
import axios from 'axios';

export const useProjectStore = defineStore('project', {
    state: () => ({
        projects: [],
        currentProject: null,
        tasks: [],
        milestones: [],
        members: [],
        activities: [],
        metrics: [],
        loading: false,
        error: null,
        filters: {
            status: '',
            search: '',
            assignee: null,
            priority: '',
        },
        stats: {
            total: 0,
            active: 0,
            completed: 0,
            on_hold: 0,
        },
        kanban: {
            todo: [],
            in_progress: [],
            review: [],
            done: [],
            blocked: [],
        },
        updateInterval: null,
    }),

    getters: {
        activeProjects: (state) => state.projects.filter(p => p.status === 'active'),
        
        overdueTasks: (state) => state.tasks.filter(task => {
            if (!task.due_date || ['done', 'blocked'].includes(task.status)) return false;
            return new Date(task.due_date) < new Date();
        }),
        
        upcomingMilestones: (state) => state.milestones
            .filter(m => m.status !== 'completed')
            .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
            .slice(0, 5),
        
        projectProgress: (state) => {
            if (!state.currentProject) return 0;
            return state.currentProject.progress || 0;
        },
        
        projectHealth: (state) => {
            if (!state.currentProject) return 100;
            return state.currentProject.health_score || 100;
        },
        
        tasksByStatus: (state) => {
            const grouped = {
                todo: 0,
                in_progress: 0,
                review: 0,
                done: 0,
                blocked: 0,
            };
            
            state.tasks.forEach(task => {
                if (grouped.hasOwnProperty(task.status)) {
                    grouped[task.status]++;
                }
            });
            
            return grouped;
        },
        
        teamVelocity: (state) => {
            if (state.metrics.length === 0) return 0;
            const recentMetrics = state.metrics.slice(-7);
            const avgVelocity = recentMetrics.reduce((sum, m) => sum + m.team_velocity, 0) / recentMetrics.length;
            return Math.round(avgVelocity * 10) / 10;
        },
    },

    actions: {
        // Загрузка списка проектов
        async fetchProjects(filters = {}) {
            this.loading = true;
            try {
                const response = await axios.get('/projects', { params: filters });
                this.projects = response.data.projects.data;
                this.stats = response.data.stats;
                this.error = null;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки проектов';
                console.error('Error fetching projects:', error);
            } finally {
                this.loading = false;
            }
        },

        // Загрузка детальной информации о проекте
        async fetchProject(projectId) {
            this.loading = true;
            try {
                const response = await axios.get(`/projects/${projectId}`);
                this.currentProject = response.data.project;
                this.tasks = response.data.project.tasks || [];
                this.milestones = response.data.project.milestones || [];
                this.members = response.data.project.members || [];
                this.activities = response.data.activities || [];
                this.metrics = response.data.metrics || [];
                
                // Запускаем автообновление
                this.startAutoUpdate(projectId);
                
                this.error = null;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка загрузки проекта';
                console.error('Error fetching project:', error);
            } finally {
                this.loading = false;
            }
        },

        // Создание проекта
        async createProject(projectData) {
            try {
                const response = await axios.post('/projects', projectData);
                this.projects.unshift(response.data.project);
                return response.data.project;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка создания проекта';
                throw error;
            }
        },

        // Обновление проекта
        async updateProject(projectId, data) {
            try {
                const response = await axios.put(`/projects/${projectId}`, data);
                const index = this.projects.findIndex(p => p.id === projectId);
                if (index !== -1) {
                    this.projects[index] = response.data.project;
                }
                if (this.currentProject?.id === projectId) {
                    this.currentProject = response.data.project;
                }
                return response.data.project;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка обновления проекта';
                throw error;
            }
        },

        // Загрузка задач проекта
        async fetchTasks(projectId, filters = {}) {
            try {
                const response = await axios.get(`/projects/${projectId}/tasks`, { params: filters });
                this.tasks = response.data.tasks;
                this.updateKanban();
            } catch (error) {
                console.error('Error fetching tasks:', error);
            }
        },

        // Создание задачи
        async createTask(projectId, taskData) {
            try {
                const response = await axios.post(`/projects/${projectId}/tasks`, taskData);
                this.tasks.push(response.data.task);
                this.updateKanban();
                return response.data.task;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка создания задачи';
                throw error;
            }
        },

        // Обновление задачи
        async updateTask(projectId, taskId, data) {
            try {
                const response = await axios.put(`/projects/${projectId}/tasks/${taskId}`, data);
                const index = this.tasks.findIndex(t => t.id === taskId);
                if (index !== -1) {
                    this.tasks[index] = response.data.task;
                }
                this.updateKanban();
                return response.data.task;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка обновления задачи';
                throw error;
            }
        },

        // Обновление прогресса задачи
        async updateTaskProgress(projectId, taskId, progress) {
            try {
                const response = await axios.post(`/projects/${projectId}/tasks/${taskId}/progress`, { progress });
                const index = this.tasks.findIndex(t => t.id === taskId);
                if (index !== -1) {
                    this.tasks[index] = response.data.task;
                }
                // Обновляем прогресс проекта
                await this.updateProjectProgress(projectId);
                return response.data.task;
            } catch (error) {
                console.error('Error updating task progress:', error);
            }
        },

        // Обновление канбан-доски
        updateKanban() {
            this.kanban = {
                todo: this.tasks.filter(t => t.status === 'todo'),
                in_progress: this.tasks.filter(t => t.status === 'in_progress'),
                review: this.tasks.filter(t => t.status === 'review'),
                done: this.tasks.filter(t => t.status === 'done'),
                blocked: this.tasks.filter(t => t.status === 'blocked'),
            };
        },

        // Перемещение задачи в канбане
        async moveTask(taskId, newStatus, newOrder) {
            const projectId = this.currentProject.id;
            try {
                const response = await axios.post(`/projects/${projectId}/tasks/${taskId}/kanban`, {
                    status: newStatus,
                    order: newOrder,
                });
                
                const task = this.tasks.find(t => t.id === taskId);
                if (task) {
                    task.status = newStatus;
                    task.order = newOrder;
                }
                
                this.updateKanban();
                await this.updateProjectProgress(projectId);
            } catch (error) {
                console.error('Error moving task:', error);
            }
        },

        // Загрузка этапов
        async fetchMilestones(projectId) {
            try {
                const response = await axios.get(`/projects/${projectId}/milestones`);
                this.milestones = response.data.milestones;
            } catch (error) {
                console.error('Error fetching milestones:', error);
            }
        },

        // Создание этапа
        async createMilestone(projectId, data) {
            try {
                const response = await axios.post(`/projects/${projectId}/milestones`, data);
                this.milestones.push(response.data.milestone);
                return response.data.milestone;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка создания этапа';
                throw error;
            }
        },

        // Завершение этапа
        async completeMilestone(projectId, milestoneId) {
            try {
                const response = await axios.post(`/projects/${projectId}/milestones/${milestoneId}/complete`);
                const index = this.milestones.findIndex(m => m.id === milestoneId);
                if (index !== -1) {
                    this.milestones[index] = response.data.milestone;
                }
                await this.updateProjectProgress(projectId);
                return response.data;
            } catch (error) {
                console.error('Error completing milestone:', error);
                throw error;
            }
        },

        // Загрузка участников
        async fetchMembers(projectId) {
            try {
                const response = await axios.get(`/projects/${projectId}/members`);
                this.members = response.data.members;
            } catch (error) {
                console.error('Error fetching members:', error);
            }
        },

        // Добавление участника
        async addMember(projectId, data) {
            try {
                const response = await axios.post(`/projects/${projectId}/members`, data);
                this.members.push(response.data.member);
                return response.data.member;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка добавления участника';
                throw error;
            }
        },

        // Обновление прогресса проекта
        async updateProjectProgress(projectId) {
            try {
                const response = await axios.post(`/projects/${projectId}/update-progress`);
                if (this.currentProject?.id === projectId) {
                    this.currentProject.progress = response.data.progress;
                    this.currentProject.health_score = response.data.health_score;
                }
                
                const index = this.projects.findIndex(p => p.id === projectId);
                if (index !== -1) {
                    this.projects[index].progress = response.data.progress;
                    this.projects[index].health_score = response.data.health_score;
                }
            } catch (error) {
                console.error('Error updating project progress:', error);
            }
        },

        // Автоматическое обновление данных
        startAutoUpdate(projectId) {
            // Останавливаем предыдущий интервал, если есть
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }

            // Обновляем каждые 30 секунд
            this.updateInterval = setInterval(async () => {
                try {
                    // Обновляем прогресс
                    await this.updateProjectProgress(projectId);
                    
                    // Обновляем активности
                    const response = await axios.get(`/projects/${projectId}/activities`);
                    this.activities = response.data.activities;
                    
                    // Обновляем метрики
                    const metricsResponse = await axios.get(`/projects/${projectId}/metrics`);
                    this.metrics = metricsResponse.data.metrics;
                } catch (error) {
                    console.error('Auto-update error:', error);
                }
            }, 30000); // 30 секунд
        },

        // Остановка автообновления
        stopAutoUpdate() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        },

        // Очистка store
        clearProject() {
            this.currentProject = null;
            this.tasks = [];
            this.milestones = [];
            this.members = [];
            this.activities = [];
            this.metrics = [];
            this.kanban = {
                todo: [],
                in_progress: [],
                review: [],
                done: [],
                blocked: [],
            };
            this.stopAutoUpdate();
        },
    },
});
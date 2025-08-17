// Игровые константы
const CANVAS_WIDTH = 800;
const CANVAS_HEIGHT = 600;
const GAME_SPEED = 60;

// Класс игры
class KolobokGame {
    constructor() {
        this.canvas = document.getElementById('gameCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.score = 0;
        this.level = 1;
        this.gameRunning = false;
        this.gamePaused = false;
        this.gameOver = false;
        
        // Колобок
        this.kolobok = {
            x: 100,
            y: 300,
            size: 20,
            speed: 3,
            color: '#FFD700',
            power: 1
        };
        
        // Враги
        this.enemies = [
            { x: 300, y: 200, size: 25, speed: 2, color: '#808080', type: 'wolf' },      // Волк
            { x: 500, y: 400, size: 30, speed: 1.5, color: '#8B4513', type: 'bear' },    // Медведь
            { x: 600, y: 150, size: 22, speed: 2.5, color: '#FFA500', type: 'fox' },     // Лиса
            { x: 200, y: 450, size: 20, speed: 3, color: '#A9A9A9', type: 'rabbit' }     // Заяц
        ];
        
        // Еда (ягоды)
        this.food = [];
        this.generateFood();
        
        // Управление
        this.keys = {};
        this.setupEventListeners();
        
        // Начало игры
        this.start();
    }
    
    // Генерация еды
    generateFood() {
        this.food = [];
        for (let i = 0; i < 8; i++) {
            this.food.push({
                x: Math.random() * (CANVAS_WIDTH - 40) + 20,
                y: Math.random() * (CANVAS_HEIGHT - 40) + 20,
                size: 15,
                color: Math.random() > 0.5 ? '#FF0000' : '#FFFF00', // Красные или желтые
                type: Math.random() > 0.5 ? 'red' : 'yellow'
            });
        }
    }
    
    // Настройка обработчиков событий
    setupEventListeners() {
        // Клавиатура
        document.addEventListener('keydown', (e) => {
            this.keys[e.key] = true;
            
            // Пауза
            if (e.key === ' ') {
                e.preventDefault();
                this.togglePause();
            }
        });
        
        document.addEventListener('keyup', (e) => {
            this.keys[e.key] = false;
        });
        
        // Кнопки меню
        document.getElementById('resumeBtn').addEventListener('click', () => {
            this.togglePause();
        });
        
        document.getElementById('restartBtn').addEventListener('click', () => {
            this.restart();
        });
        
        document.getElementById('playAgainBtn').addEventListener('click', () => {
            this.restart();
        });
    }
    
    // Переключение паузы
    togglePause() {
        if (this.gameOver) return;
        
        this.gamePaused = !this.gamePaused;
        const pauseMenu = document.getElementById('pauseMenu');
        
        if (this.gamePaused) {
            pauseMenu.classList.remove('hidden');
        } else {
            pauseMenu.classList.add('hidden');
        }
    }
    
    // Обновление состояния игры
    update() {
        if (this.gamePaused || this.gameOver) return;
        
        this.updateKolobok();
        this.updateEnemies();
        this.checkCollisions();
        this.updateUI();
    }
    
    // Обновление Колобка
    updateKolobok() {
        // Движение
        if (this.keys['ArrowLeft'] && this.kolobok.x > this.kolobok.size) {
            this.kolobok.x -= this.kolobok.speed;
        }
        if (this.keys['ArrowRight'] && this.kolobok.x < CANVAS_WIDTH - this.kolobok.size) {
            this.kolobok.x += this.kolobok.speed;
        }
        if (this.keys['ArrowUp'] && this.kolobok.y > this.kolobok.size) {
            this.kolobok.y -= this.kolobok.speed;
        }
        if (this.keys['ArrowDown'] && this.kolobok.y < CANVAS_HEIGHT - this.kolobok.size) {
            this.kolobok.y += this.kolobok.speed;
        }
        
        // Проверка достижения моря (правый край)
        if (this.kolobok.x >= CANVAS_WIDTH - this.kolobok.size) {
            this.win();
        }
    }
    
    // Обновление врагов
    updateEnemies() {
        this.enemies.forEach(enemy => {
            // Простое преследование Колобка
            const dx = this.kolobok.x - enemy.x;
            const dy = this.kolobok.y - enemy.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance > 0) {
                enemy.x += (dx / distance) * enemy.speed;
                enemy.y += (dy / distance) * enemy.speed;
            }
            
            // Ограничение движения в пределах поля
            enemy.x = Math.max(enemy.size, Math.min(CANVAS_WIDTH - enemy.size, enemy.x));
            enemy.y = Math.max(enemy.size, Math.min(CANVAS_HEIGHT - enemy.size, enemy.y));
        });
    }
    
    // Проверка столкновений
    checkCollisions() {
        // Столкновение с едой
        this.food = this.food.filter(food => {
            const dx = this.kolobok.x - food.x;
            const dy = this.kolobok.y - food.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < this.kolobok.size + food.size) {
                this.eatFood(food);
                return false; // Удаляем съеденную еду
            }
            return true;
        });
        
        // Столкновение с врагами
        this.enemies.forEach(enemy => {
            const dx = this.kolobok.x - enemy.x;
            const dy = this.kolobok.y - enemy.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < this.kolobok.size + enemy.size) {
                this.collideWithEnemy(enemy);
            }
        });
        
        // Генерация новой еды если мало
        if (this.food.length < 3) {
            this.generateFood();
        }
    }
    
    // Поедание еды
    eatFood(food) {
        this.score += 10;
        
        // Увеличение размера и силы
        if (food.type === 'red') {
            this.kolobok.size += 2;
            this.kolobok.power += 0.5;
            this.kolobok.speed += 0.2;
        } else {
            this.kolobok.size += 1;
            this.kolobok.power += 0.3;
            this.kolobok.speed += 0.1;
        }
        
        // Ограничение максимального размера
        this.kolobok.size = Math.min(this.kolobok.size, 50);
        this.kolobok.speed = Math.min(this.kolobok.speed, 6);
        
        // Проверка уровня
        if (this.score >= this.level * 100) {
            this.level++;
            this.increaseDifficulty();
        }
    }
    
    // Столкновение с врагом
    collideWithEnemy(enemy) {
        // Если Колобок достаточно силен, он может победить врага
        if (this.kolobok.power >= 2) {
            this.score += 50;
            this.enemies = this.enemies.filter(e => e !== enemy);
            
            // Увеличение силы после победы
            this.kolobok.power += 0.5;
        } else {
            this.gameOver = true;
            this.showGameOver('Проиграл! Враги поймали Колобка!');
        }
    }
    
    // Увеличение сложности
    increaseDifficulty() {
        this.enemies.forEach(enemy => {
            enemy.speed += 0.5;
        });
    }
    
    // Победа
    win() {
        this.gameOver = true;
        this.score += 200; // Бонус за победу
        this.showGameOver('Победа! Колобок добрался до моря!');
    }
    
    // Показать меню окончания игры
    showGameOver(message) {
        document.getElementById('gameOverTitle').textContent = message.includes('Победа') ? 'Победа!' : 'Игра окончена!';
        document.getElementById('gameOverMessage').textContent = `Вы набрали ${this.score} очков`;
        document.getElementById('finalScore').textContent = this.score;
        document.getElementById('gameOverMenu').classList.remove('hidden');
    }
    
    // Обновление интерфейса
    updateUI() {
        document.getElementById('score').textContent = this.score;
        document.getElementById('level').textContent = this.level;
        document.getElementById('size').textContent = Math.floor(this.kolobok.power);
    }
    
    // Отрисовка игры
    render() {
        // Очистка canvas
        this.ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
        
        // Отрисовка фона
        this.drawBackground();
        
        // Отрисовка еды
        this.drawFood();
        
        // Отрисовка врагов
        this.drawEnemies();
        
        // Отрисовка Колобка
        this.drawKolobok();
        
        // Отрисовка UI элементов
        this.drawUI();
    }
    
    // Отрисовка фона
    drawBackground() {
        // Дом (левая сторона)
        this.ctx.fillStyle = '#8B4513';
        this.ctx.fillRect(20, 250, 80, 100);
        this.ctx.fillStyle = '#CD5C5C';
        this.ctx.fillRect(20, 200, 80, 50);
        
        // Морская линия (правая сторона)
        this.ctx.fillStyle = '#4169E1';
        this.ctx.fillRect(CANVAS_WIDTH - 20, 0, 20, CANVAS_HEIGHT);
        
        // Дорожка
        this.ctx.strokeStyle = '#228B22';
        this.ctx.lineWidth = 8;
        this.ctx.beginPath();
        this.ctx.moveTo(100, 300);
        this.ctx.quadraticCurveTo(400, 300, CANVAS_WIDTH - 20, 300);
        this.ctx.stroke();
    }
    
    // Отрисовка еды
    drawFood() {
        this.food.forEach(food => {
            this.ctx.fillStyle = food.color;
            this.ctx.beginPath();
            this.ctx.arc(food.x, food.y, food.size, 0, Math.PI * 2);
            this.ctx.fill();
            
            // Блики
            this.ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
            this.ctx.beginPath();
            this.ctx.arc(food.x - 3, food.y - 3, 4, 0, Math.PI * 2);
            this.ctx.fill();
        });
    }
    
    // Отрисовка врагов
    drawEnemies() {
        this.enemies.forEach(enemy => {
            // Основное тело
            this.ctx.fillStyle = enemy.color;
            this.ctx.beginPath();
            this.ctx.arc(enemy.x, enemy.y, enemy.size, 0, Math.PI * 2);
            this.ctx.fill();
            
            // Глаза
            this.ctx.fillStyle = '#000';
            this.ctx.beginPath();
            this.ctx.arc(enemy.x - 5, enemy.y - 5, 3, 0, Math.PI * 2);
            this.ctx.arc(enemy.x + 5, enemy.y - 5, 3, 0, Math.PI * 2);
            this.ctx.fill();
            
            // Рот
            this.ctx.strokeStyle = '#000';
            this.ctx.lineWidth = 2;
            this.ctx.beginPath();
            this.ctx.arc(enemy.x, enemy.y + 5, 5, 0, Math.PI);
            this.ctx.stroke();
        });
    }
    
    // Отрисовка Колобка
    drawKolobok() {
        // Основное тело
        this.ctx.fillStyle = this.kolobok.color;
        this.ctx.beginPath();
        this.ctx.arc(this.kolobok.x, this.kolobok.y, this.kolobok.size, 0, Math.PI * 2);
        this.ctx.fill();
        
        // Глаза
        this.ctx.fillStyle = '#000';
        this.ctx.beginPath();
        this.ctx.arc(this.kolobok.x - 6, this.kolobok.y - 6, 4, 0, Math.PI * 2);
        this.ctx.arc(this.kolobok.x + 6, this.kolobok.y - 6, 4, 0, Math.PI * 2);
        this.ctx.fill();
        
        // Рот (улыбка)
        this.ctx.strokeStyle = '#000';
        this.ctx.lineWidth = 3;
        this.ctx.beginPath();
        this.ctx.arc(this.kolobok.x, this.kolobok.y + 2, 8, 0, Math.PI);
        this.ctx.stroke();
        
        // Анимация качения
        this.ctx.strokeStyle = '#8B4513';
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.arc(this.kolobok.x, this.kolobok.y + this.kolobok.size, 3, 0, Math.PI * 2);
        this.ctx.stroke();
    }
    
    // Отрисовка UI элементов
    drawUI() {
        // Индикатор силы
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
        this.ctx.fillRect(10, 10, 150, 20);
        this.ctx.fillStyle = '#FF4500';
        this.ctx.fillRect(10, 10, (this.kolobok.power / 3) * 150, 20);
        
        // Текст силы
        this.ctx.fillStyle = '#000';
        this.ctx.font = '14px Arial';
        this.ctx.fillText(`Сила: ${this.kolobok.power.toFixed(1)}`, 15, 25);
    }
    
    // Игровой цикл
    gameLoop() {
        if (!this.gameRunning) return;
        
        this.update();
        this.render();
        
        setTimeout(() => {
            requestAnimationFrame(() => this.gameLoop());
        }, 1000 / GAME_SPEED);
    }
    
    // Запуск игры
    start() {
        this.gameRunning = true;
        this.gameLoop();
    }
    
    // Перезапуск игры
    restart() {
        // Сброс состояния
        this.score = 0;
        this.level = 1;
        this.gameOver = false;
        this.gamePaused = false;
        
        // Сброс Колобка
        this.kolobok.x = 100;
        this.kolobok.y = 300;
        this.kolobok.size = 20;
        this.kolobok.speed = 3;
        this.kolobok.power = 1;
        
        // Сброс врагов
        this.enemies = [
            { x: 300, y: 200, size: 25, speed: 2, color: '#808080', type: 'wolf' },
            { x: 500, y: 400, size: 30, speed: 1.5, color: '#8B4513', type: 'bear' },
            { x: 600, y: 150, size: 22, speed: 2.5, color: '#FFA500', type: 'fox' },
            { x: 200, y: 450, size: 20, speed: 3, color: '#A9A9A9', type: 'rabbit' }
        ];
        
        // Генерация новой еды
        this.generateFood();
        
        // Скрытие меню
        document.getElementById('pauseMenu').classList.add('hidden');
        document.getElementById('gameOverMenu').classList.add('hidden');
        
        // Обновление UI
        this.updateUI();
        
        // Запуск игры
        this.start();
    }
}

// Запуск игры после загрузки страницы
document.addEventListener('DOMContentLoaded', () => {
    new KolobokGame();
});

#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
CEO Interface for Virtual Office
Управление AI командой через Python интерфейс
"""

import json
import os
import sys
import subprocess
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, List, Optional

class CEOInterface:
    """CEO интерфейс для управления виртуальным офисом"""

    def __init__(self):
        try:
            self.base_path = Path(r"C:\www.spa.com\.ai-team")
            self.virtual_office = self.base_path / "virtual-office"
            self.tasks_dir = self.virtual_office / "tasks"
            self.inbox_dir = self.virtual_office / "inbox"
            self.reports_dir = self.virtual_office / "reports"
            self.chat_file = self.base_path / "chat.md"
            self.agents_config = self.base_path / "system" / "agents.json"

            # Create directories if not exist
            self.tasks_dir.mkdir(parents=True, exist_ok=True)
            self.inbox_dir.mkdir(parents=True, exist_ok=True)
            self.reports_dir.mkdir(parents=True, exist_ok=True)
            (self.base_path / "system").mkdir(parents=True, exist_ok=True)

            # Load agents configuration
            self.agents = self.load_agents()
        except Exception as e:
            print(f"❌ Ошибка инициализации: {e}")
            sys.exit(1)

    def load_agents(self) -> Dict:
        """Загрузить конфигурацию агентов"""
        if self.agents_config.exists():
            with open(self.agents_config, 'r', encoding='utf-8') as f:
                return json.load(f)
        return {}

    def create_task(self, title: str, description: str, assignee: str = "",
                   priority: str = "normal", deadline: str = "") -> str:
        """Создать новую задачу"""
        try:
            task_id = f"TASK-{datetime.now().strftime('%Y%m%d')}-{os.urandom(2).hex()}"

            if not deadline:
                deadline = (datetime.now() + timedelta(days=1)).strftime("%Y-%m-%d")

            task = {
                "task_id": task_id,
                "title": title,
                "description": description,
                "assignee": assignee,
                "priority": priority,
                "status": "assigned" if assignee else "new",
                "deadline": deadline,
                "created_at": datetime.now().isoformat(),
                "updated_at": datetime.now().isoformat(),
                "dependencies": [],
                "comments": []
            }

            # Save task
            task_file = self.tasks_dir / f"{task_id}.json"
            with open(task_file, 'w', encoding='utf-8') as f:
                json.dump(task, f, indent=2, ensure_ascii=False)

            # Notify in chat
            if assignee:
                self.send_to_chat(f"[SYSTEM]: New task {task_id} assigned to @{assignee} - {title}")

            print(f"✅ Задача создана: {task_id}")
            return task_id
        except Exception as e:
            print(f"❌ Ошибка создания задачи: {e}")
            return ""

    def send_message(self, to_agent: str, message: str, priority: str = "normal"):
        """Отправить сообщение агенту"""
        msg_id = f"MSG-{datetime.now().strftime('%Y%m%d%H%M%S')}"

        msg = {
            "id": msg_id,
            "from": "CEO",
            "to": to_agent,
            "message": message,
            "priority": priority,
            "timestamp": datetime.now().isoformat(),
            "status": "unread"
        }

        # Save to agent's inbox
        inbox_path = self.inbox_dir / to_agent
        inbox_path.mkdir(parents=True, exist_ok=True)

        msg_file = inbox_path / f"{msg_id}.json"
        with open(msg_file, 'w', encoding='utf-8') as f:
            json.dump(msg, f, indent=2, ensure_ascii=False)

        print(f"📤 Сообщение отправлено {to_agent}")

    def broadcast_message(self, message: str):
        """Отправить сообщение всей команде"""
        for agent in ["teamlead", "backend", "frontend", "qa", "devops"]:
            self.send_message(agent, message)

        self.send_to_chat(f"[CEO]: @all {message}")
        print(f"📢 Сообщение разослано всей команде")

    def send_to_chat(self, message: str):
        """Добавить сообщение в общий чат"""
        timestamp = datetime.now().strftime("[%H:%M]")
        with open(self.chat_file, 'a', encoding='utf-8') as f:
            f.write(f"{timestamp} {message}\n")

    def get_tasks_summary(self) -> Dict:
        """Получить сводку по задачам"""
        if not self.tasks_dir.exists():
            return {}

        tasks = []
        for task_file in self.tasks_dir.glob("*.json"):
            with open(task_file, 'r', encoding='utf-8') as f:
                tasks.append(json.load(f))

        summary = {
            "total": len(tasks),
            "by_status": {},
            "by_assignee": {},
            "by_priority": {}
        }

        for task in tasks:
            # By status
            status = task.get("status", "unknown")
            summary["by_status"][status] = summary["by_status"].get(status, 0) + 1

            # By assignee
            assignee = task.get("assignee", "unassigned")
            summary["by_assignee"][assignee] = summary["by_assignee"].get(assignee, 0) + 1

            # By priority
            priority = task.get("priority", "normal")
            summary["by_priority"][priority] = summary["by_priority"].get(priority, 0) + 1

        return summary

    def view_inbox_summary(self) -> Dict:
        """Просмотр сводки по inbox агентов"""
        summary = {}

        for agent in ["teamlead", "backend", "frontend", "qa", "devops"]:
            inbox_path = self.inbox_dir / agent
            if inbox_path.exists():
                messages = list(inbox_path.glob("*.json"))
                unread = 0

                for msg_file in messages:
                    with open(msg_file, 'r', encoding='utf-8') as f:
                        msg = json.load(f)
                        if msg.get("status") == "unread":
                            unread += 1

                summary[agent] = {
                    "total": len(messages),
                    "unread": unread
                }
            else:
                summary[agent] = {"total": 0, "unread": 0}

        return summary

    def generate_daily_report(self) -> str:
        """Генерация ежедневного отчета"""
        report = []
        report.append("=" * 50)
        report.append(f"📊 DAILY REPORT - {datetime.now().strftime('%Y-%m-%d')}")
        report.append("=" * 50)

        # Tasks summary
        tasks_summary = self.get_tasks_summary()
        report.append("\n📋 ЗАДАЧИ:")
        report.append(f"Всего: {tasks_summary.get('total', 0)}")

        if tasks_summary.get('by_status'):
            report.append("\nПо статусу:")
            for status, count in tasks_summary['by_status'].items():
                report.append(f"  {status}: {count}")

        if tasks_summary.get('by_assignee'):
            report.append("\nПо исполнителям:")
            for assignee, count in tasks_summary['by_assignee'].items():
                report.append(f"  {assignee}: {count}")

        # Inbox summary
        inbox_summary = self.view_inbox_summary()
        report.append("\n📬 СООБЩЕНИЯ:")
        for agent, stats in inbox_summary.items():
            if stats['unread'] > 0:
                report.append(f"  {agent}: {stats['unread']} непрочитанных")

        report_text = "\n".join(report)

        # Save report
        report_file = self.reports_dir / f"report_{datetime.now().strftime('%Y%m%d')}.txt"
        with open(report_file, 'w', encoding='utf-8') as f:
            f.write(report_text)

        return report_text

    def interactive_menu(self):
        """Интерактивное меню CEO"""
        while True:
            print("\n" + "=" * 60)
            print("🏢 CEO VIRTUAL OFFICE CONTROL PANEL")
            print("=" * 60)
            print("\n1. 📋 Создать задачу")
            print("2. 📊 Просмотр задач")
            print("3. 💬 Отправить сообщение агенту")
            print("4. 📢 Сообщение всей команде")
            print("5. 📈 Сводка по задачам")
            print("6. 📬 Проверить inbox агентов")
            print("7. 📑 Сгенерировать отчет")
            print("8. 🚀 Запустить команду")
            print("0. 🚪 Выход")

            choice = input("\nВыберите действие: ")

            if choice == "1":
                title = input("Название задачи: ")
                description = input("Описание: ")
                assignee = input("Исполнитель (teamlead/backend/frontend/qa/devops): ")
                priority = input("Приоритет (low/normal/high/critical) [normal]: ") or "normal"
                deadline = input("Дедлайн (YYYY-MM-DD) [завтра]: ")

                self.create_task(title, description, assignee, priority, deadline)

            elif choice == "2":
                # Run PowerShell task manager
                subprocess.run([
                    "powershell", "-ExecutionPolicy", "Bypass",
                    "-File", str(self.base_path / "scripts" / "task-manager.ps1"),
                    "-Action", "list"
                ])

            elif choice == "3":
                agent = input("Кому (teamlead/backend/frontend/qa/devops): ")
                message = input("Сообщение: ")
                priority = input("Приоритет (low/normal/high) [normal]: ") or "normal"

                self.send_message(agent, message, priority)

            elif choice == "4":
                message = input("Сообщение всей команде: ")
                self.broadcast_message(message)

            elif choice == "5":
                summary = self.get_tasks_summary()
                print("\n📊 СВОДКА ПО ЗАДАЧАМ:")
                print(f"Всего задач: {summary.get('total', 0)}")

                if summary.get('by_status'):
                    print("\nПо статусу:")
                    for status, count in summary['by_status'].items():
                        print(f"  {status}: {count}")

                if summary.get('by_assignee'):
                    print("\nПо исполнителям:")
                    for assignee, count in summary['by_assignee'].items():
                        print(f"  {assignee}: {count}")

            elif choice == "6":
                inbox_summary = self.view_inbox_summary()
                print("\n📬 INBOX АГЕНТОВ:")
                for agent, stats in inbox_summary.items():
                    status = "🔴" if stats['unread'] > 0 else "🟢"
                    print(f"{status} {agent}: {stats['total']} всего, {stats['unread']} непрочитанных")

            elif choice == "7":
                report = self.generate_daily_report()
                print(report)
                print("\n✅ Отчет сохранен в virtual-office/reports/")

            elif choice == "8":
                print("\nЗапуск AI команды...")
                subprocess.run([
                    str(self.base_path / "scripts" / "START-AI-TEAM-FINAL.bat")
                ], shell=True)

            elif choice == "0":
                print("👋 До свидания!")
                break

            else:
                print("❌ Неверный выбор")

            input("\nНажмите Enter для продолжения...")

def main():
    """Главная функция"""
    ceo = CEOInterface()

    # Проверка аргументов командной строки
    if len(sys.argv) > 1:
        command = sys.argv[1]

        if command == "task" and len(sys.argv) > 3:
            title = sys.argv[2]
            assignee = sys.argv[3] if len(sys.argv) > 3 else ""
            ceo.create_task(title, "Task from CLI", assignee)

        elif command == "message" and len(sys.argv) > 3:
            agent = sys.argv[2]
            message = " ".join(sys.argv[3:])
            ceo.send_message(agent, message)

        elif command == "broadcast" and len(sys.argv) > 2:
            message = " ".join(sys.argv[2:])
            ceo.broadcast_message(message)

        elif command == "report":
            print(ceo.generate_daily_report())

        else:
            print("Usage:")
            print("  python ceo_interface.py                    - Interactive mode")
            print("  python ceo_interface.py task <title> [assignee]")
            print("  python ceo_interface.py message <agent> <message>")
            print("  python ceo_interface.py broadcast <message>")
            print("  python ceo_interface.py report")
    else:
        # Интерактивный режим
        ceo.interactive_menu()

if __name__ == "__main__":
    main()
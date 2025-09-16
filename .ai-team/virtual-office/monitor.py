#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Virtual Office Monitor
Система мониторинга активности AI агентов
"""

import json
import os
import time
import subprocess
from datetime import datetime
from pathlib import Path
from typing import Dict, List

class VirtualOfficeMonitor:
    """Мониторинг виртуального офиса"""

    def __init__(self):
        self.base_path = Path(r"C:\www.spa.com\.ai-team")
        self.virtual_office = self.base_path / "virtual-office"
        self.system_path = self.base_path / "system"
        self.metrics_file = self.system_path / "metrics.json"
        self.status_file = self.system_path / "status.json"

        # Initialize metrics
        self.init_metrics()

    def init_metrics(self):
        """Инициализация метрик"""
        if not self.metrics_file.exists():
            metrics = {
                "agents": {
                    "teamlead": {"tasks_completed": 0, "messages_processed": 0, "uptime_hours": 0},
                    "backend": {"tasks_completed": 0, "messages_processed": 0, "uptime_hours": 0},
                    "frontend": {"tasks_completed": 0, "messages_processed": 0, "uptime_hours": 0},
                    "qa": {"tasks_completed": 0, "bugs_found": 0, "tests_run": 0, "uptime_hours": 0},
                    "devops": {"tasks_completed": 0, "deployments": 0, "uptime_hours": 0}
                },
                "totals": {
                    "tasks_created": 0,
                    "tasks_completed": 0,
                    "messages_sent": 0,
                    "reports_generated": 0
                },
                "last_updated": datetime.now().isoformat()
            }
            self.save_metrics(metrics)

    def save_metrics(self, metrics: Dict):
        """Сохранить метрики"""
        try:
            self.metrics_file.parent.mkdir(parents=True, exist_ok=True)
            with open(self.metrics_file, 'w', encoding='utf-8') as f:
                json.dump(metrics, f, indent=2, ensure_ascii=False)
        except Exception as e:
            print(f"❌ Ошибка сохранения метрик: {e}")

    def load_metrics(self) -> Dict:
        """Загрузить метрики"""
        try:
            if self.metrics_file.exists():
                with open(self.metrics_file, 'r', encoding='utf-8') as f:
                    return json.load(f)
        except Exception as e:
            print(f"⚠️ Ошибка загрузки метрик: {e}")
        return {}

    def update_agent_status(self, agent: str, status: str):
        """Обновить статус агента"""
        statuses = {}
        if self.status_file.exists():
            with open(self.status_file, 'r', encoding='utf-8') as f:
                statuses = json.load(f)

        if "agents" not in statuses:
            statuses["agents"] = {}

        statuses["agents"][agent] = {
            "status": status,
            "last_seen": datetime.now().isoformat()
        }

        with open(self.status_file, 'w', encoding='utf-8') as f:
            json.dump(statuses, f, indent=2, ensure_ascii=False)

    def get_agent_activity(self, agent: str) -> Dict:
        """Получить активность агента"""
        activity = {
            "inbox": 0,
            "outbox": 0,
            "tasks_assigned": 0,
            "last_message": None
        }

        # Check inbox
        inbox_path = self.virtual_office / "inbox" / agent
        if inbox_path.exists():
            messages = list(inbox_path.glob("*.json"))
            activity["inbox"] = len(messages)

            # Get last message
            if messages:
                latest = max(messages, key=lambda p: p.stat().st_mtime)
                with open(latest, 'r', encoding='utf-8') as f:
                    msg = json.load(f)
                    activity["last_message"] = msg.get("timestamp")

        # Check outbox
        outbox_path = self.virtual_office / "outbox" / agent
        if outbox_path.exists():
            activity["outbox"] = len(list(outbox_path.glob("*.json")))

        # Check assigned tasks
        tasks_path = self.virtual_office / "tasks"
        if tasks_path.exists():
            for task_file in tasks_path.glob("*.json"):
                with open(task_file, 'r', encoding='utf-8') as f:
                    task = json.load(f)
                    if task.get("assignee") == agent:
                        activity["tasks_assigned"] += 1

        return activity

    def get_system_health(self) -> Dict:
        """Получить состояние системы"""
        health = {
            "status": "healthy",
            "issues": [],
            "agents_online": 0,
            "total_agents": 5
        }

        # Check chat server
        try:
            response = subprocess.run(
                ["powershell", "-Command", "Test-NetConnection -ComputerName localhost -Port 8082"],
                capture_output=True,
                text=True,
                timeout=5
            )
            if "TcpTestSucceeded : True" not in response.stdout:
                health["issues"].append("Chat server not responding on port 8082")
                health["status"] = "degraded"
        except:
            health["issues"].append("Cannot check chat server status")

        # Check agent status
        if self.status_file.exists():
            with open(self.status_file, 'r', encoding='utf-8') as f:
                statuses = json.load(f)
                for agent, info in statuses.get("agents", {}).items():
                    last_seen = datetime.fromisoformat(info["last_seen"])
                    if (datetime.now() - last_seen).seconds < 300:  # 5 minutes
                        health["agents_online"] += 1

        if health["agents_online"] < 3:
            health["issues"].append(f"Only {health['agents_online']}/5 agents online")
            health["status"] = "degraded" if health["agents_online"] > 0 else "critical"

        return health

    def display_dashboard(self):
        """Отобразить дашборд"""
        os.system('cls' if os.name == 'nt' else 'clear')

        print("=" * 80)
        print(" " * 25 + "🏢 VIRTUAL OFFICE MONITOR")
        print("=" * 80)
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print()

        # System health
        health = self.get_system_health()
        health_icon = "🟢" if health["status"] == "healthy" else "🟡" if health["status"] == "degraded" else "🔴"
        print(f"{health_icon} System Status: {health['status'].upper()}")
        print(f"   Agents Online: {health['agents_online']}/{health['total_agents']}")
        if health["issues"]:
            print("   Issues:")
            for issue in health["issues"]:
                print(f"     ⚠️  {issue}")
        print()

        # Agents activity
        print("👥 AGENTS ACTIVITY:")
        print("-" * 80)
        print(f"{'Agent':<12} {'Status':<10} {'Inbox':<8} {'Tasks':<8} {'Last Activity':<20}")
        print("-" * 80)

        agents = ["teamlead", "backend", "frontend", "qa", "devops"]
        for agent in agents:
            activity = self.get_agent_activity(agent)

            # Get status
            status = "offline"
            status_icon = "⚫"
            if self.status_file.exists():
                with open(self.status_file, 'r', encoding='utf-8') as f:
                    statuses = json.load(f)
                    agent_info = statuses.get("agents", {}).get(agent, {})
                    if agent_info:
                        last_seen = datetime.fromisoformat(agent_info["last_seen"])
                        if (datetime.now() - last_seen).seconds < 300:
                            status = "online"
                            status_icon = "🟢"
                        elif (datetime.now() - last_seen).seconds < 900:
                            status = "idle"
                            status_icon = "🟡"

            last_activity = ""
            if activity["last_message"]:
                try:
                    last_time = datetime.fromisoformat(activity["last_message"])
                    last_activity = last_time.strftime("%H:%M:%S")
                except:
                    last_activity = "unknown"

            print(f"{agent:<12} {status_icon} {status:<8} {activity['inbox']:<8} {activity['tasks_assigned']:<8} {last_activity:<20}")

        print()

        # Metrics summary
        metrics = self.load_metrics()
        if metrics:
            print("📊 METRICS SUMMARY:")
            print("-" * 80)
            totals = metrics.get("totals", {})
            print(f"Tasks Created: {totals.get('tasks_created', 0)}")
            print(f"Tasks Completed: {totals.get('tasks_completed', 0)}")
            print(f"Messages Sent: {totals.get('messages_sent', 0)}")
            print(f"Reports Generated: {totals.get('reports_generated', 0)}")

        print()
        print("=" * 80)
        print("Press Ctrl+C to exit...")

    def run(self):
        """Запуск мониторинга"""
        print("Starting Virtual Office Monitor...")
        print("Press Ctrl+C to stop")

        try:
            while True:
                self.display_dashboard()
                time.sleep(5)  # Update every 5 seconds
        except KeyboardInterrupt:
            print("\n👋 Monitor stopped")

def main():
    """Главная функция"""
    monitor = VirtualOfficeMonitor()
    monitor.run()

if __name__ == "__main__":
    main()
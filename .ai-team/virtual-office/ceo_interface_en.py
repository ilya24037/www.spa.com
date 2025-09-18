#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import json
import os
import sys
from datetime import datetime, timedelta
from pathlib import Path
import time

class CEOInterface:
    def __init__(self):
        try:
            self.base_path = Path(r"C:\www.spa.com\.ai-team")
            self.tasks_dir = self.base_path / "virtual-office" / "tasks"
            self.inbox_dir = self.base_path / "virtual-office" / "inbox"
            self.metrics_dir = self.base_path / "virtual-office" / "metrics"
            self.reports_dir = self.base_path / "virtual-office" / "reports"
            self.chat_file = self.base_path / "chat.md"

            # Create directories if not exist
            self.tasks_dir.mkdir(parents=True, exist_ok=True)
            self.inbox_dir.mkdir(parents=True, exist_ok=True)
            self.metrics_dir.mkdir(parents=True, exist_ok=True)
            self.reports_dir.mkdir(parents=True, exist_ok=True)

        except Exception as e:
            print(f"[ERROR] Initialization failed: {e}")
            sys.exit(1)

    def create_task(self):
        """Create a new task for an agent"""
        print("\n=== CREATE NEW TASK ===")
        title = input("Task title: ")
        description = input("Description: ")
        priority = input("Priority (high/medium/low) [medium]: ").lower() or "medium"
        assignee = input("Assign to (teamlead/backend/frontend/qa/devops): ").lower()
        deadline_days = input("Deadline (days from now) [7]: ") or "7"

        try:
            deadline = (datetime.now() + timedelta(days=int(deadline_days))).isoformat()
        except:
            deadline = (datetime.now() + timedelta(days=7)).isoformat()

        task = {
            "id": f"task_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
            "title": title,
            "description": description,
            "priority": priority,
            "assignee": assignee,
            "status": "pending",
            "created_at": datetime.now().isoformat(),
            "deadline": deadline,
            "created_by": "CEO"
        }

        # Save task
        task_file = self.tasks_dir / f"{task['id']}.json"
        with open(task_file, 'w', encoding='utf-8') as f:
            json.dump(task, f, indent=2, ensure_ascii=False)

        # Put in assignee's inbox
        if assignee in ['teamlead', 'backend', 'frontend', 'qa', 'devops']:
            inbox_file = self.inbox_dir / assignee / f"{task['id']}.json"
            inbox_file.parent.mkdir(exist_ok=True)
            with open(inbox_file, 'w', encoding='utf-8') as f:
                json.dump(task, f, indent=2, ensure_ascii=False)

        print(f"\n[SUCCESS] Task created and sent to {assignee}!")
        print(f"Task ID: {task['id']}")

    def view_tasks(self):
        """View all tasks"""
        print("\n=== ALL TASKS ===")
        tasks = []

        if not self.tasks_dir.exists():
            print("No tasks found.")
            return

        for task_file in self.tasks_dir.glob("*.json"):
            try:
                with open(task_file, 'r', encoding='utf-8') as f:
                    task = json.load(f)
                    tasks.append(task)
            except:
                continue

        if not tasks:
            print("No tasks found.")
            return

        # Sort by creation date
        tasks.sort(key=lambda x: x.get('created_at', ''), reverse=True)

        for task in tasks:
            print(f"\n[{task.get('status', 'unknown').upper()}] {task.get('title', 'No title')}")
            print(f"  ID: {task.get('id', 'unknown')}")
            print(f"  Assignee: {task.get('assignee', 'unknown')}")
            print(f"  Priority: {task.get('priority', 'unknown')}")
            print(f"  Deadline: {task.get('deadline', 'unknown')[:10]}")

    def send_message(self):
        """Send a message to an agent"""
        print("\n=== SEND MESSAGE ===")
        from_who = input("From (e.g., ceo): ") or "ceo"
        to_who = input("To (teamlead/backend/frontend/qa/devops/all): ").lower()
        message = input("Message: ")

        timestamp = datetime.now().strftime("%H:%M")

        # Format message
        if to_who == "all":
            chat_message = f"[{timestamp}] [{from_who.upper()}]: @all {message}\n"
        else:
            chat_message = f"[{timestamp}] [{from_who.upper()}]: @{to_who} {message}\n"

        # Append to chat
        with open(self.chat_file, 'a', encoding='utf-8') as f:
            f.write(chat_message)

        print(f"\n[SUCCESS] Message sent to {to_who}!")

    def view_metrics(self):
        """View team metrics"""
        print("\n=== TEAM METRICS ===")

        agents = ['teamlead', 'backend', 'frontend', 'qa', 'devops']

        for agent in agents:
            metric_file = self.metrics_dir / f"{agent}.json"
            if metric_file.exists():
                try:
                    with open(metric_file, 'r', encoding='utf-8') as f:
                        metrics = json.load(f)

                    print(f"\n{agent.upper()}:")
                    print(f"  Tasks completed: {metrics.get('tasks_completed', 0)}")
                    print(f"  Messages processed: {metrics.get('messages_processed', 0)}")

                    if agent == 'qa':
                        print(f"  Bugs found: {metrics.get('bugs_found', 0)}")
                        print(f"  Tests run: {metrics.get('tests_run', 0)}")
                    elif agent == 'devops':
                        print(f"  Deployments: {metrics.get('deployments', 0)}")
                except:
                    print(f"\n{agent.upper()}: No metrics available")
            else:
                print(f"\n{agent.upper()}: No metrics available")

    def generate_report(self):
        """Generate a status report"""
        print("\n=== GENERATING REPORT ===")

        report = {
            "date": datetime.now().isoformat(),
            "generated_by": "CEO Interface",
            "summary": {},
            "tasks": {"total": 0, "completed": 0, "in_progress": 0, "pending": 0},
            "team_status": {}
        }

        # Count tasks
        for task_file in self.tasks_dir.glob("*.json"):
            try:
                with open(task_file, 'r', encoding='utf-8') as f:
                    task = json.load(f)
                    report["tasks"]["total"] += 1
                    status = task.get("status", "pending")
                    if status == "completed":
                        report["tasks"]["completed"] += 1
                    elif status == "in_progress":
                        report["tasks"]["in_progress"] += 1
                    else:
                        report["tasks"]["pending"] += 1
            except:
                continue

        # Save report
        report_file = self.reports_dir / f"report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        with open(report_file, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)

        print(f"\nReport Summary:")
        print(f"  Total tasks: {report['tasks']['total']}")
        print(f"  Completed: {report['tasks']['completed']}")
        print(f"  In progress: {report['tasks']['in_progress']}")
        print(f"  Pending: {report['tasks']['pending']}")
        print(f"\nFull report saved to: {report_file.name}")

    def view_agent_status(self):
        """View the status of all agents"""
        print("\n=== AGENT STATUS ===")

        agents = {
            'teamlead': 'Team Coordinator',
            'backend': 'Laravel Developer',
            'frontend': 'Vue.js Developer',
            'qa': 'Test Engineer',
            'devops': 'Infrastructure Engineer'
        }

        for agent_id, role in agents.items():
            # Check for tasks in inbox
            inbox_path = self.inbox_dir / agent_id
            task_count = len(list(inbox_path.glob("*.json"))) if inbox_path.exists() else 0

            print(f"\n{agent_id.upper()} ({role}):")
            print(f"  Status: Active")
            print(f"  Tasks in inbox: {task_count}")

    def run(self):
        """Main interface loop"""
        print("=" * 40)
        print("       CEO CONTROL PANEL")
        print("       Virtual Office 3.2")
        print("=" * 40)

        while True:
            print("\n" + "=" * 40)
            print("         MAIN MENU")
            print("=" * 40)
            print("\n1. Create task")
            print("2. View tasks")
            print("3. Send message")
            print("4. View metrics")
            print("5. Generate report")
            print("6. View agent status")
            print("0. Exit")

            choice = input("\nSelect action: ")

            if choice == "1":
                self.create_task()
            elif choice == "2":
                self.view_tasks()
            elif choice == "3":
                self.send_message()
            elif choice == "4":
                self.view_metrics()
            elif choice == "5":
                self.generate_report()
            elif choice == "6":
                self.view_agent_status()
            elif choice == "0":
                print("\nExiting CEO Interface...")
                break
            else:
                print("\n[ERROR] Invalid option. Please try again.")

            input("\nPress Enter to continue...")

if __name__ == "__main__":
    try:
        ceo = CEOInterface()
        ceo.run()
    except KeyboardInterrupt:
        print("\n\nCEO Interface terminated.")
    except Exception as e:
        print(f"\n[ERROR] An error occurred: {e}")
        input("Press Enter to exit...")
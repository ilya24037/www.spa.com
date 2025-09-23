#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
AI Team Chat - Integrated Terminal Interface
Интегрированный чат с Virtual Office для координации AI-агентов
"""

import os
import sys
import json
import datetime
from pathlib import Path
from typing import List, Dict, Optional
import subprocess
import time

# Add Virtual Office path to system
sys.path.insert(0, r'C:\www.spa.com\.ai-team\virtual-office')

# ANSI color codes
class Colors:
    CLAUDE = '\033[95m'    # Purple
    CURSOR = '\033[91m'     # Red
    USER = '\033[94m'       # Blue
    SYSTEM = '\033[93m'     # Yellow
    SUCCESS = '\033[92m'    # Green
    TEAM = '\033[96m'       # Cyan
    RESET = '\033[0m'
    BOLD = '\033[1m'
    DIM = '\033[2m'

class IntegratedTeamChat:
    def __init__(self):
        # Paths
        self.aidd_path = Path(r"C:\www.spa.com\.aidd")
        self.team_path = Path(r"C:\www.spa.com\.ai-team")
        self.chat_file = self.aidd_path / 'agent-chat.md'
        self.virtual_office = self.team_path / "virtual-office"

        # Virtual Office components
        self.tasks_dir = self.virtual_office / "tasks"
        self.inbox_dir = self.virtual_office / "inbox"
        self.reports_dir = self.virtual_office / "reports"

        # State
        self.messages = []
        self.current_user = 'User'
        self.agents = self.load_agents()
        self.ceo_interface = None

        # Try to import CEO Interface
        try:
            from ceo_interface import CEOInterface
            self.ceo_interface = CEOInterface()
            print(f"{Colors.SUCCESS}✅ Virtual Office connected!{Colors.RESET}")
        except:
            print(f"{Colors.SYSTEM}⚠️ Virtual Office not available (standalone mode){Colors.RESET}")

    def load_agents(self) -> Dict:
        """Load AI team agents configuration"""
        agents = {
            'Claude': {'icon': '🤖', 'color': Colors.CLAUDE, 'status': 'Active'},
            'Cursor': {'icon': '⚡', 'color': Colors.CURSOR, 'status': 'Ready'},
            'User': {'icon': '👤', 'color': Colors.USER, 'status': 'Online'},
        }

        # Load Virtual Office agents if available
        agents_config = self.team_path / "system" / "agents.json"
        if agents_config.exists():
            with open(agents_config, 'r', encoding='utf-8') as f:
                vo_data = json.load(f)
                vo_agents = vo_data.get('agents', vo_data)  # Handle both formats
                for agent_id, agent_data in vo_agents.items():
                    if agent_id not in ['Claude', 'Cursor', 'User']:
                        agents[agent_data['name']] = {
                            'icon': agent_data.get('emoji', '🤖'),
                            'color': Colors.TEAM,
                            'status': agent_data.get('status', 'Available'),
                            'role': agent_data.get('role', 'Specialist'),
                            'skills': agent_data.get('skills', [])
                        }

        return agents

    def clear_screen(self):
        os.system('cls' if os.name == 'nt' else 'clear')

    def print_header(self):
        self.clear_screen()
        print(f"{Colors.BOLD}{Colors.SYSTEM}")
        print("=" * 70)
        print("    🚀 INTEGRATED AI TEAM CHAT - SPA Platform Development")
        print("=" * 70)
        print(f"{Colors.RESET}")

        # Show connected agents
        print(f"{Colors.TEAM}Connected Agents:{Colors.RESET}", end=" ")
        for name, agent in list(self.agents.items())[:5]:
            print(f"{agent['icon']} {name}", end="  ")
        if len(self.agents) > 5:
            print(f"... +{len(self.agents)-5} more", end="")
        print("\n")

        print(f"{Colors.DIM}Commands: /help, /agents, /task, /status, /office, /switch, /exit{Colors.RESET}")
        print("-" * 70)
        print()

    def show_agents(self):
        """Show all available agents"""
        print(f"{Colors.BOLD}{Colors.TEAM}🤝 AI Team Members:{Colors.RESET}")
        print()

        # Core agents
        print(f"{Colors.BOLD}Core Team:{Colors.RESET}")
        for name in ['Claude', 'Cursor', 'User']:
            if name in self.agents:
                agent = self.agents[name]
                print(f"  {agent['icon']} {agent['color']}{name:<15}{Colors.RESET} - {agent['status']}")

        # Virtual Office agents
        vo_agents = [a for a in self.agents.keys() if a not in ['Claude', 'Cursor', 'User']]
        if vo_agents:
            print(f"\n{Colors.BOLD}Virtual Office Team:{Colors.RESET}")
            for name in vo_agents:
                agent = self.agents[name]
                role = agent.get('role', 'Specialist')
                print(f"  {agent['icon']} {Colors.TEAM}{name:<15}{Colors.RESET} - {role}")
                if 'skills' in agent and agent['skills']:
                    skills = ', '.join(agent['skills'][:3])
                    print(f"      {Colors.DIM}Skills: {skills}{Colors.RESET}")

    def create_task(self, description: str):
        """Create task in Virtual Office"""
        if not self.ceo_interface:
            print(f"{Colors.SYSTEM}Virtual Office not connected. Task saved locally.{Colors.RESET}")
            # Save to local agent-chat.md
            self.save_message('User', f"TASK: {description}", "Pending")
            return

        try:
            # Parse task for mentions
            assignee = ""
            if '@' in description:
                parts = description.split('@')
                if len(parts) > 1:
                    assignee = parts[1].split()[0]

            # Create task via CEO Interface
            task_id = self.ceo_interface.create_task(
                title=description[:50],
                description=description,
                assignee=assignee,
                priority="normal"
            )

            print(f"{Colors.SUCCESS}✅ Task created: {task_id}{Colors.RESET}")

            # Notify in chat
            self.save_message('System', f"Task assigned: {description}", "Active")

        except Exception as e:
            print(f"{Colors.SYSTEM}Error creating task: {e}{Colors.RESET}")

    def show_office_status(self):
        """Show Virtual Office status"""
        print(f"{Colors.BOLD}{Colors.TEAM}🏢 Virtual Office Status:{Colors.RESET}")
        print()

        if not self.virtual_office.exists():
            print(f"{Colors.DIM}Virtual Office not initialized{Colors.RESET}")
            return

        # Count tasks
        tasks_count = len(list(self.tasks_dir.glob("*.json"))) if self.tasks_dir.exists() else 0
        inbox_count = len(list(self.inbox_dir.glob("*.json"))) if self.inbox_dir.exists() else 0
        reports_count = len(list(self.reports_dir.glob("*.json"))) if self.reports_dir.exists() else 0

        print(f"  📋 Active Tasks: {Colors.BOLD}{tasks_count}{Colors.RESET}")
        print(f"  📥 Inbox Items: {Colors.BOLD}{inbox_count}{Colors.RESET}")
        print(f"  📊 Reports: {Colors.BOLD}{reports_count}{Colors.RESET}")

        # Show recent activity
        if self.ceo_interface:
            try:
                recent = self.ceo_interface.get_inbox()[:3]
                if recent:
                    print(f"\n{Colors.BOLD}Recent Activity:{Colors.RESET}")
                    for item in recent:
                        print(f"  • {item.get('type', 'Message')}: {item.get('subject', 'No subject')[:40]}")
            except:
                pass

    def monitor_agents(self):
        """Start monitoring Virtual Office agents"""
        print(f"{Colors.TEAM}Starting Virtual Office Monitor...{Colors.RESET}")

        monitor_script = self.virtual_office / "monitor.py"
        if monitor_script.exists():
            try:
                subprocess.Popen([sys.executable, str(monitor_script)],
                               creationflags=subprocess.CREATE_NEW_CONSOLE if os.name == 'nt' else 0)
                print(f"{Colors.SUCCESS}✅ Monitor started in new window{Colors.RESET}")
            except Exception as e:
                print(f"{Colors.SYSTEM}Could not start monitor: {e}{Colors.RESET}")
        else:
            print(f"{Colors.SYSTEM}Monitor script not found{Colors.RESET}")

    def save_message(self, author: str, message: str, status: str = "Active"):
        """Save message to agent-chat.md"""
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M")

        # Check if it's a Virtual Office agent
        is_vo_agent = author not in ['User', 'Claude', 'Cursor', 'System']

        entry = f"""
### {timestamp} [{author}] {"Virtual Office" if is_vo_agent else "Terminal Chat"}
**Статус**: {status}
**Сообщение**: {message}
---
"""
        with open(self.chat_file, 'a', encoding='utf-8') as f:
            f.write(entry)

    def show_help(self):
        """Show extended help"""
        print(f"{Colors.BOLD}{Colors.SYSTEM}📖 Help:{Colors.RESET}")
        print(f"\n{Colors.BOLD}Basic Commands:{Colors.RESET}")
        print(f"  /help     - Show this help")
        print(f"  /clear    - Clear screen")
        print(f"  /exit     - Exit chat")

        print(f"\n{Colors.BOLD}Team Commands:{Colors.RESET}")
        print(f"  /agents   - Show all AI agents")
        print(f"  /switch   - Switch active agent")
        print(f"  /task     - Create task for agents")
        print(f"  @Name     - Mention specific agent")

        print(f"\n{Colors.BOLD}Virtual Office:{Colors.RESET}")
        print(f"  /office   - Show office status")
        print(f"  /monitor  - Start agents monitor")
        print(f"  /inbox    - Check office inbox")
        print(f"  /report   - Generate status report")

        print(f"\n{Colors.BOLD}Examples:{Colors.RESET}")
        print(f"  /task @Frontend implement booking calendar")
        print(f"  @Claude analyze the booking system architecture")
        print(f"  @Backend create API for search filters")

    def process_command(self, command: str) -> bool:
        """Process extended commands"""
        cmd = command.lower().strip().split()[0]

        # Basic commands
        if cmd == '/exit':
            print(f"{Colors.SUCCESS}Goodbye! 👋{Colors.RESET}")
            return False
        elif cmd == '/clear':
            self.print_header()
        elif cmd == '/help':
            self.show_help()
        elif cmd == '/agents':
            self.show_agents()
        elif cmd == '/office':
            self.show_office_status()
        elif cmd == '/monitor':
            self.monitor_agents()
        elif cmd == '/switch':
            self.switch_user()
        elif cmd == '/task':
            # Extract task description
            task_desc = command[5:].strip()
            if task_desc:
                self.create_task(task_desc)
            else:
                print(f"{Colors.DIM}Usage: /task @AgentName description{Colors.RESET}")
        elif cmd == '/inbox':
            if self.ceo_interface:
                inbox = self.ceo_interface.get_inbox()
                print(f"{Colors.TEAM}📥 Inbox ({len(inbox)} items):{Colors.RESET}")
                for item in inbox[:5]:
                    print(f"  • {item.get('subject', 'No subject')}")
            else:
                print(f"{Colors.DIM}Virtual Office not connected{Colors.RESET}")
        elif cmd == '/report':
            print(f"{Colors.TEAM}Generating status report...{Colors.RESET}")
            # Could integrate with Virtual Office reporting
            print(f"{Colors.SUCCESS}Report saved to reports/{datetime.datetime.now().strftime('%Y%m%d')}_status.json{Colors.RESET}")
        else:
            print(f"{Colors.DIM}Unknown command. Type /help for available commands.{Colors.RESET}")

        return True

    def switch_user(self):
        """Switch between all available agents"""
        agent_names = list(self.agents.keys())
        try:
            current_index = agent_names.index(self.current_user)
            self.current_user = agent_names[(current_index + 1) % len(agent_names)]
        except ValueError:
            self.current_user = agent_names[0]

        agent = self.agents[self.current_user]
        print(f"\n{agent['color']}{agent['icon']} Switched to: {Colors.BOLD}{self.current_user}{Colors.RESET}")

        if self.current_user not in ['User', 'Claude', 'Cursor']:
            role = agent.get('role', 'Specialist')
            print(f"{Colors.DIM}Role: {role}{Colors.RESET}")
        print()

    def process_mentions(self, message: str):
        """Process @ mentions in message"""
        mentioned = []
        for name in self.agents.keys():
            if f'@{name}' in message:
                mentioned.append(name)

        if mentioned:
            print(f"{Colors.TEAM}📢 Notifying: {', '.join(mentioned)}{Colors.RESET}")

            # If Virtual Office agent, create task
            vo_mentions = [m for m in mentioned if m not in ['Claude', 'Cursor', 'User']]
            if vo_mentions and self.ceo_interface:
                for agent in vo_mentions:
                    self.ceo_interface.send_message(
                        to=agent,
                        subject=f"Mention from {self.current_user}",
                        content=message
                    )

    def run(self):
        """Main chat loop"""
        self.print_header()

        print(f"{Colors.BOLD}Type your message or /help for commands:{Colors.RESET}\n")

        while True:
            try:
                # Create prompt
                agent = self.agents.get(self.current_user, {'icon': '?', 'color': Colors.RESET})
                prompt = f"{agent['color']}{agent['icon']} {self.current_user}> {Colors.RESET}"
                message = input(prompt).strip()

                if not message:
                    continue

                # Check commands
                if message.startswith('/'):
                    if not self.process_command(message):
                        break
                    continue

                # Process mentions
                self.process_mentions(message)

                # Save message
                self.save_message(self.current_user, message)
                print(f"{Colors.SUCCESS}✓ Message saved{Colors.RESET}\n")

            except KeyboardInterrupt:
                print(f"\n{Colors.SYSTEM}Use /exit to quit properly{Colors.RESET}")
            except Exception as e:
                print(f"{Colors.SYSTEM}Error: {e}{Colors.RESET}")

def main():
    chat = IntegratedTeamChat()
    chat.run()

if __name__ == "__main__":
    main()
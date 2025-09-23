#!/usr/bin/env python3
"""
AI Team Chat - Terminal Interface
Интерактивный чат для координации работы Claude и Cursor AI
"""

import os
import sys
import json
import datetime
from typing import List, Dict, Optional
from pathlib import Path

# ANSI color codes for terminal
class Colors:
    CLAUDE = '\033[95m'    # Purple
    CURSOR = '\033[91m'     # Red
    USER = '\033[94m'       # Blue
    SYSTEM = '\033[93m'     # Yellow
    SUCCESS = '\033[92m'    # Green
    RESET = '\033[0m'
    BOLD = '\033[1m'
    DIM = '\033[2m'

class TeamChat:
    def __init__(self):
        self.chat_file = Path(__file__).parent / 'agent-chat.md'
        self.messages = []
        self.current_user = 'User'

    def clear_screen(self):
        """Clear terminal screen"""
        os.system('cls' if os.name == 'nt' else 'clear')

    def print_header(self):
        """Print chat header"""
        self.clear_screen()
        print(f"{Colors.BOLD}{Colors.SYSTEM}")
        print("=" * 60)
        print("       🚀 AI TEAM CHAT - SPA Platform Development")
        print("=" * 60)
        print(f"{Colors.RESET}")
        print(f"{Colors.DIM}Commands: /help, /clear, /status, /tasks, /switch, /exit{Colors.RESET}")
        print("-" * 60)
        print()

    def load_messages(self):
        """Load messages from agent-chat.md"""
        if not self.chat_file.exists():
            return

        with open(self.chat_file, 'r', encoding='utf-8') as f:
            content = f.read()
            # Parse messages from markdown
            # This is simplified - in production would parse more carefully
            if '## 📝 Лог коммуникации' in content:
                log_section = content.split('## 📝 Лог коммуникации')[1]
                # Extract recent messages
                entries = log_section.split('###')[1:4]  # Get last 3 entries
                for entry in entries:
                    lines = entry.strip().split('\n')
                    if lines:
                        self.messages.append({
                            'header': lines[0],
                            'content': '\n'.join(lines[1:])
                        })

    def save_message(self, author: str, message: str, status: str = "В процессе"):
        """Save message to agent-chat.md"""
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M")

        entry = f"""
### {timestamp} [{author}] Сообщение из терминала
**Статус**: {status}
**Сообщение**: {message}
---
"""

        # Append to file
        with open(self.chat_file, 'a', encoding='utf-8') as f:
            f.write(entry)

    def display_messages(self, last_n: int = 10):
        """Display recent messages"""
        if not self.messages:
            print(f"{Colors.DIM}No messages yet. Start typing!{Colors.RESET}")
            return

        for msg in self.messages[-last_n:]:
            # Determine color based on author
            if '[Claude]' in msg['header']:
                color = Colors.CLAUDE
                icon = '🤖'
            elif '[Cursor]' in msg['header']:
                color = Colors.CURSOR
                icon = '⚡'
            elif '[User]' in msg['header']:
                color = Colors.USER
                icon = '👤'
            else:
                color = Colors.SYSTEM
                icon = '📢'

            print(f"{color}{icon} {msg['header']}{Colors.RESET}")
            print(f"{Colors.DIM}{msg['content']}{Colors.RESET}")
            print()

    def show_status(self):
        """Show current project status"""
        print(f"{Colors.BOLD}{Colors.SUCCESS}📊 Project Status:{Colors.RESET}")
        print(f"  • MVP Progress: {Colors.BOLD}86%{Colors.RESET}")
        print(f"  • Booking System: {Colors.BOLD}60%{Colors.RESET}")
        print(f"  • Search System: {Colors.BOLD}0%{Colors.RESET}")
        print(f"  • Active Agent: {Colors.BOLD}{self.current_user}{Colors.RESET}")

    def show_tasks(self):
        """Show current tasks"""
        print(f"{Colors.BOLD}{Colors.SUCCESS}📋 Current Tasks:{Colors.RESET}")
        tasks = [
            ("✅", "Синхронизация документации", "Completed"),
            ("🔄", "Система бронирования", "In Progress - 60%"),
            ("⏳", "Поиск мастеров", "Pending - 0%"),
            ("⏳", "Система отзывов", "Pending"),
        ]

        for icon, task, status in tasks:
            if "Completed" in status:
                color = Colors.SUCCESS
            elif "In Progress" in status:
                color = Colors.SYSTEM
            else:
                color = Colors.DIM

            print(f"  {icon} {color}{task} - {status}{Colors.RESET}")

    def show_help(self):
        """Show help information"""
        print(f"{Colors.BOLD}{Colors.SYSTEM}📖 Help:{Colors.RESET}")
        print(f"  {Colors.BOLD}/help{Colors.RESET}    - Show this help")
        print(f"  {Colors.BOLD}/clear{Colors.RESET}   - Clear screen")
        print(f"  {Colors.BOLD}/status{Colors.RESET}  - Show project status")
        print(f"  {Colors.BOLD}/tasks{Colors.RESET}   - Show current tasks")
        print(f"  {Colors.BOLD}/switch{Colors.RESET}  - Switch between User/Claude/Cursor")
        print(f"  {Colors.BOLD}/reload{Colors.RESET}  - Reload messages from file")
        print(f"  {Colors.BOLD}/exit{Colors.RESET}    - Exit chat")
        print(f"  {Colors.BOLD}@Claude{Colors.RESET}  - Mention Claude in message")
        print(f"  {Colors.BOLD}@Cursor{Colors.RESET}  - Mention Cursor in message")

    def switch_user(self):
        """Switch between User, Claude, and Cursor modes"""
        users = ['User', 'Claude', 'Cursor']
        current_index = users.index(self.current_user)
        self.current_user = users[(current_index + 1) % 3]

        colors = {
            'User': Colors.USER,
            'Claude': Colors.CLAUDE,
            'Cursor': Colors.CURSOR
        }

        icons = {
            'User': '👤',
            'Claude': '🤖',
            'Cursor': '⚡'
        }

        print(f"\n{colors[self.current_user]}{icons[self.current_user]} Switched to: {Colors.BOLD}{self.current_user}{Colors.RESET}\n")

    def process_command(self, command: str) -> bool:
        """Process chat commands. Returns True if should continue, False to exit"""
        command = command.lower().strip()

        if command == '/exit':
            print(f"{Colors.SUCCESS}Goodbye! 👋{Colors.RESET}")
            return False
        elif command == '/clear':
            self.print_header()
        elif command == '/status':
            self.show_status()
        elif command == '/tasks':
            self.show_tasks()
        elif command == '/help':
            self.show_help()
        elif command == '/switch':
            self.switch_user()
        elif command == '/reload':
            self.load_messages()
            print(f"{Colors.SUCCESS}Messages reloaded!{Colors.RESET}")
        else:
            print(f"{Colors.DIM}Unknown command. Type /help for available commands.{Colors.RESET}")

        return True

    def run(self):
        """Main chat loop"""
        self.print_header()
        self.load_messages()
        self.display_messages(5)

        print(f"\n{Colors.BOLD}Type your message or /help for commands:{Colors.RESET}\n")

        while True:
            try:
                # Set prompt based on current user
                colors = {
                    'User': Colors.USER,
                    'Claude': Colors.CLAUDE,
                    'Cursor': Colors.CURSOR
                }

                icons = {
                    'User': '👤',
                    'Claude': '🤖',
                    'Cursor': '⚡'
                }

                prompt = f"{colors[self.current_user]}{icons[self.current_user]} {self.current_user}> {Colors.RESET}"
                message = input(prompt).strip()

                if not message:
                    continue

                # Check if it's a command
                if message.startswith('/'):
                    if not self.process_command(message):
                        break
                    continue

                # Process mentions
                if '@Claude' in message:
                    print(f"{Colors.CLAUDE}🤖 Claude: Analyzing request...{Colors.RESET}")
                if '@Cursor' in message:
                    print(f"{Colors.CURSOR}⚡ Cursor: Processing...{Colors.RESET}")

                # Save message
                self.save_message(self.current_user, message)

                # Show confirmation
                print(f"{Colors.SUCCESS}✓ Message saved to agent-chat.md{Colors.RESET}\n")

                # Add to local messages
                self.messages.append({
                    'header': f"[{self.current_user}] Just now",
                    'content': message
                })

            except KeyboardInterrupt:
                print(f"\n{Colors.SYSTEM}Use /exit to quit properly{Colors.RESET}")
            except Exception as e:
                print(f"{Colors.SYSTEM}Error: {e}{Colors.RESET}")

def main():
    """Entry point"""
    chat = TeamChat()
    chat.run()

if __name__ == "__main__":
    main()
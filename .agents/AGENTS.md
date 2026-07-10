# Workspace Rules

## Terminal Command Execution
- **Do NOT execute terminal commands directly:** The agent must never use the `run_command` tool (or any other command-execution mechanisms) to run shell/terminal commands on the user's system.
- **Instruct the user to run commands:** Whenever a terminal command needs to be executed (such as running migrations, installing packages, starting servers, etc.), the agent must display the exact command clearly in its response and instruct/ask the user to run it in their terminal instead.
- **Provide context:** Always explain what the command does, why it is needed, and any expected output or next steps after the user runs it.

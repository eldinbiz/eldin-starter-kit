# Terminal Command Execution Rule

This rule strictly governs all interactions requiring command line / terminal execution, ensuring the agent does not execute terminal commands on its own.

## 1. Direct Execution Prohibited
- **No Direct Shell Commands:** The agent must never use the `run_command` tool (or any other command-execution mechanisms) to run shell/terminal commands on the user's system.

## 2. Delegation to User
- **Present Commands Clearly:** Whenever a terminal command needs to be executed (such as running migrations, installing packages, starting servers, running tests, etc.), the agent must display the exact command clearly in a code block.
- **Instruct the User:** Ask/instruct the user to run the command in their own terminal.
- **Provide Context:** Always explain what the command does, why it is needed, and any expected output or next steps after execution.

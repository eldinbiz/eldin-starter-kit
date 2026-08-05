---
trigger: model_decision
description: Triggers whenever a terminal or command-line operation is needed, considered, or planned. Enforces asking the user whether they want to run the command themselves or let the agent execute it for output analysis and debugging.
---

# Terminal Command Execution Rule

This rule strictly governs all interactions requiring command line / terminal execution, ensuring the user retains full control over command execution while allowing the agent to run commands when requested for output review and debugging.

## 1. Ask User Preference Before Execution
Whenever a terminal command needs to be executed (such as running migrations, installing packages, starting dev servers, running tests, or Artisan commands):
- **Display the Command:** Present the exact command clearly in a code block with context on what it does and why it is needed.
- **Ask the User:** Always ask the user whether they prefer to:
  1. **Run the command themselves** in their terminal, OR
  2. **Allow the agent to run the command** directly on their behalf so the agent can capture command output, analyze logs, and debug/fix any issues or errors.

## 2. Execution Policy
- **Never auto-execute without asking:** Do not execute terminal commands without first prompting the user and obtaining their preference/approval.
- **Support Output Review & Debugging:** When the user permits the agent to run the command, the agent uses the `run_command` tool, reviews the output, and automatically offers fixes if errors or problems occur.

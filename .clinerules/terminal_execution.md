# Terminal Command Execution Rule

This rule strictly governs all interactions requiring command line / terminal execution, ensuring destructive operations are never executed without user oversight.

## 0. Shell Detection & Command Adaptation

Every `execute_command` call **MUST** first check the `Default Shell` value provided in the `SYSTEM INFORMATION` block of the current prompt. This tells you both the operating system AND the specific shell interpreter in use.

### 0.1 Shell Identification

Use the `Default Shell` path to identify the shell type:

| Default Shell Value | Shell Type | OS |
|---|---|---|
| `C:\Windows\System32\cmd.exe` | **CMD** (Command Prompt) | Windows |
| `C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe` | **PowerShell** (Windows PowerShell) | Windows |
| `.*\pwsh.exe` | **PowerShell** (PWSh / PowerShell Core) | Windows |
| `.*\bash.exe`, `.*\git\bin\bash.exe` | **Git Bash** (MinGW) | Windows |
| `/bin/bash`, `/bin/zsh`, `/usr/bin/fish` | **Unix Shell** (bash/zsh/fish) | Linux/macOS |
| `.*\wsl.exe` | **WSL** (Windows Subsystem for Linux) | Windows → Linux |

### 0.2 Per-Shell Syntax Cheat Sheet

Always formulate commands using the syntax matching the detected shell.

| Operation | CMD | PowerShell | Git Bash / Unix Shell |
|---|---|---|---|
| Command chaining | `&&` | `;` (or `&&`) | `&&` |
| Continue on next line | `^` | `` ` `` | `\` |
| List directory | `dir` | `Get-ChildItem` (or `dir`) | `ls` |
| Read file contents | `type` | `Get-Content` (or `type`) | `cat` |
| Redirect stderr to stdout | `2>&1` | `2>&1` | `2>&1` |
| Discard output (redirect to null) | `>nul` | `>$null` (or `\| Out-Null`) | `>/dev/null` |
| Discard output with stderr | `>nul 2>&1` | `*>$null` | `>/dev/null 2>&1` |
| Sleep / wait | `timeout /t N /nobreak >nul` | `Start-Sleep -Seconds N` | `sleep N` |
| Environment variable reference | `%VAR%` | `$env:VAR` | `$VAR` |
| Path separator in file paths | `\` | `\` | `/` |
| Path separator in `PATH`-like env vars | `;` | `;` | `:` |
| Quote character for arguments | `"` | `"` or `'` | `"` or `'` |
| Shell redirection operator | `>` `>>` `<` | `>` `>>` `<` | `>` `>>` `<` |
| Pipe | `\|` | `\|` | `\|` |
| Logical AND (conditional) | `&&` | `-and` (in expressions only; chaining uses `;`) | `&&` |
| Logical OR (conditional) | `\|\|` | `-or` (expressions only) | `\|\|` |

### 0.3 Windows Path Separators (CMD + PowerShell)

On all Windows shells, backslash paths in PHP/Composer commands are subject to escape-sequence interpretation (e.g., `\b` becomes a backspace character, `\n` a newline). Always use **forward slashes** for vendor binary paths regardless of whether you are in CMD, PowerShell, or Git Bash:

```
# Correct (forward slashes — PHP on Windows handles these natively)
php vendor/bin/pint --dirty --format agent
php vendor/bin/phpunit
php vendor/bin/pest

# Incorrect (backslashes — \b, \t, \n etc. get mangled by cmd.exe)
php vendor\bin\pint --dirty --format agent
```

### 0.4 Git Bash on Windows — Special Considerations

Git Bash runs a Unix-like shell emulation layer. While it uses Unix-style command names (`ls`, `cat`, `sleep`), it runs on the Windows filesystem. Key guidelines:

- Use **Unix shell syntax** for command chaining, redirection, and variable references.
- Use **forward slashes** in path arguments (as with all PHP/Composer commands).
- The `php`, `composer`, and `node` executables are available from the Windows `PATH`.
- For `timeout`/`sleep`, use `sleep N` (not the Windows `timeout` command).
- For discarding output, use `>/dev/null 2>&1` (not `>nul`).

### 0.5 Execution Checklist (Before Every Command)

1. **Check** the `Default Shell` in the SYSTEM INFORMATION block.
2. **Identify** the shell type using the table in §0.1.
3. **Formulate** the command using the correct syntax from §0.2.
4. **Apply** forward-slashes for PHP/Composer vendor paths (§0.3) if on Windows.
5. **Verify** you are not accidentally using Unix commands in CMD or CMD commands in Unix.

## 1. Always Ask User Preference Before Execution

Whenever a terminal command needs to be executed (whether read-only or state-modifying):

- **Display the Command:** Present the exact command clearly in a code block with context on what it does and why it is needed.
- **Ask the User:** Always ask the user whether they prefer to:
  1. **Run the command themselves** in their terminal, OR
  2. **Let the agent execute the command** on their behalf so the agent can capture output, analyze logs, and automatically debug/fix any issues or errors.

## 2. Command Categories Reference

When asking the user, provide context on what the command does:

| Category | Examples |
|---|---|
| **Package Management** | `composer require lab404/laravel-impersonate`, `npm install ...` |
| **Schema & Migrations** | `php artisan migrate`, `php artisan migrate:fresh` |
| **Data Seeding & Reset** | `php artisan db:seed`, `php artisan migrate:fresh --seed` |
| **Tests & Verification** | `composer test`, `vendor/bin/pest` |
| **Environment & Maintenance** | `php artisan key:generate`, `php artisan storage:link`, `php artisan config:clear` |
| **Git Operations** | `git status`, `git diff`, `git push`, `git reset` |

## 3. Output Review & Automated Debugging

When the user chooses to let the agent run the command:
- Execute the command via tools.
- Carefully inspect stdout/stderr.
- If errors occur, analyze the root cause and propose or apply fixes immediately.

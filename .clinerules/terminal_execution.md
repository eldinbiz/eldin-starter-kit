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

## 1. Non-Destructive Commands (Agent May Execute)

The agent is permitted to run **safe, read-only, or environment-scoped** commands directly, including:

- `php artisan route:list` — listing routes
- `php artisan list` — listing available commands
- `php artisan about` — environment info
- `php -v`, `node -v`, `composer --version` — version checks
- `git status`, `git log`, `git diff` — read-only git operations
- `ls`, `dir`, `cat`, `type` — filesystem reads
- `php artisan tinker --execute="..."` — read-only tinker queries
- `composer install` (when adding/updating non-destructive dependencies)
- `npm install` (non-destructive package additions)

## 2. Destructive Commands (MUST Delegate to User)

Commands that **mutate the database, delete data, alter schema, or change system state** must NEVER be executed by the agent (unless explicitly ordered by the users, but you must confirm this with the user twice). These include:

| Category | Examples |
|---|---|
| **Schema changes** | `php artisan migrate`, `php artisan migrate:fresh`, `php artisan migrate:rollback` |
| **Data seeding** | `php artisan db:seed`, `php artisan migrate:fresh --seed` |
| **Data deletion** | `php artisan db:wipe`, truncate/delete SQL via tinker |
| **Environment mutation** | `php artisan key:generate`, `php artisan storage:link` |
| **Tests (side effects)** | `php artisan test` (may use test DB, treat as potentially destructive) |
| **Cache clearing (production)** | `php artisan config:clear`, `php artisan cache:clear` (safe locally, delegate in production context) |
| **Destructive Git** | `git push`, `git reset --hard`, `git clean -fd` |
| **Package removal** | `composer remove ...`, `npm uninstall ...` |

When the agent determines a command falls into this category, it must:
- Display the exact command clearly in a code block.
- Explain what the command does, why it is needed, and any expected output.
- Instruct the user to run the command in their own terminal.
- Wait for user confirmation before proceeding with any dependent next steps.

## 3. Ambiguous Commands

If a command's destructiveness is unclear, the agent must **default to delegation** — present it to the user and explain the ambiguity rather than risk unintended data loss.

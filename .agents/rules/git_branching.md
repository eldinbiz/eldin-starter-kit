---
trigger: model_decision
description: Triggers before modifying repository files or running any git/GitHub workflow operation (branch checks, commits, merges, pushes, PRs). Enforces always working on development, merging to staging only on explicit user command, and strictly forbidding any agent operations targeting main.
---

# Git Branching & Merge Policy Rule

This rule strictly governs Git branching, commits, merges, pushes, and Pull Requests for this repository and downstream projects built from this starter kit.

## 1. Branch Roles & Authority Matrix

| Branch | Purpose | Agent Allowed Actions | Forbidden Actions |
|---|---|---|---|
| `development` | Primary active working branch | Edit files, stage, commit, pull/push (with command permission), merge approved feature branches into `development` | Committing work intended for `development` onto any other branch |
| `staging` | Pre-production integration branch | Merge `development -> staging` and push **ONLY** upon explicit user command; immediately switch back to `development` afterward | Autonomous checkouts, commits, merges, pushes, or PRs without an explicit user command |
| `main` | Production branch (GitHub UI only) | Read-only status/diff inspection when needed | **ALL write/PR actions:** checkout to work, commit, merge into `main`, push to `main`, or `gh pr create`/`gh pr merge` targeting `main` |

## 2. Active Development Workflow (`development` Default)

1. **Direct Work on `development`:** Always work and commit directly on the `development` branch by default.
2. **Feature Branch Exception:** Do **not** create separate feature or topic branches unless the user explicitly asks for one. When explicitly asked, branch off `development` and merge back into `development` (never into `staging` or `main`).
3. **Mandatory Pre-Flight Check:** Before modifying files or committing in the repository:
   - Verify the active branch (`git -C <repo> branch --show-current`, respecting the Terminal Command Execution rule).
   - If the active branch is `main`, `staging`, or detached `HEAD`, **do not** edit tracked files or commit on that branch.
   - Check whether `development` exists locally or on `origin` (`git -C <repo> ls-remote --heads origin development`).
   - If `development` exists on `origin` but not locally (e.g., single-branch clone), fetch and checkout `development` tracking `origin/development`.
   - If `development` does not exist yet locally or remotely, ask the user before creating `development` from the base branch.

## 3. Merging to `staging` (Explicit User Command Only)

The agent may merge into `staging` **only** when the user explicitly commands it (e.g., *"merge to staging"*, *"promote development to staging"*).

When commanded to merge to `staging`:
1. Confirm all intended changes are committed on `development` and the working tree is clean.
2. Present the exact git commands to the user per the **Terminal Command Execution** rule before executing:
   ```bash
   git checkout staging && git pull origin staging && git merge development && git push origin staging && git checkout development
   ```
   *(Fetch `staging` first if using a single-branch clone; omit `git push` if the user requested a local-only merge.)*
3. **Always Return to `development`:** Never leave the repository checked out on `staging` after the merge completes.

## 4. Strict `main` Branch Prohibition

All merges into `main` are performed exclusively by the user in the **GitHub UI**.

The assistant is **strictly forbidden** from:
- Committing directly to `main`.
- Merging `staging`, `development`, or any other branch into `main` locally or remotely.
- Pushing any ref to `origin main`.
- Creating, updating, or merging Pull Requests targeting `main` via `gh` CLI or GitHub APIs.

If asked to merge to `main` or open a PR targeting `main`, decline the automated action and remind the user of this policy so they can perform the merge via the GitHub UI.

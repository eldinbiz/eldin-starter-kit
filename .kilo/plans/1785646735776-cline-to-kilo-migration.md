# Migrate Cline Rules & Skills to Kilo

## Goal

Convert `.clinerules/`, `.cline/skills/`, and `.agents/` (Cline) content into Kilo-native format under `.kilo/`. Original source directories remain untouched.

## Design Decisions

| # | Decision | Rationale |
|---|----------|-----------|
| 1 | **Always-on guardrails → instruction files loaded via `kilo.json` `instructions` glob** | Closest equivalent to Cline's auto-injection behavior |
| 2 | **Skills → `.kilo/skills/<name>/SKILL.md`** | Kilo's native skill system supports on-demand loading via the `skill` tool |
| 3 | **Merge all 3 sources** (`.clinerules/`, `.agents/rules/`, `.cline/skills/`) | `.agents/` has newer versions of some rules; `.cline/` has unique content missing from `.agents/` |
| 4 | **For overlapping rules, use `.agents/` version as base, layer in extra detail from `.clinerules/`** | `.agents/` versions have YAML frontmatter and cleaner language |
| 5 | **Preserve CDN-only Tailwind v4 rule verbatim** | As requested, despite the project having `vite.config.js` and `node_modules/` |
| 6 | **Adapt Cline-specific tool references to Kilo equivalents** | `execute_command` → `bash`; `playwright_navigate` → `kilo-playwright_browser_navigate`; etc. |

## Target Structure

```
.kilo/
├── kilo.json                          ← NEW (root-level project config)
├── instructions/                      ← NEW directory
│   ├── alpinejs.md                    ← merged from .agents/rules/alpine.md + .clinerules/alpine.md
│   ├── cdn-libraries.md               ← from .agents/rules/cdn-libraries.md (newer version)
│   ├── tailwind-v4.md                 ← from .agents/rules/tailwind4.md (exemption ref updated)
│   ├── php84.md                       ← from .agents/rules/php84.md
│   ├── testing.md                     ← from .clinerules/testing.md (adapted)
│   ├── ui-planning.md                 ← from .clinerules/ui-planning.md (adapted)
│   ├── sqlite-mcp.md                  ← from .clinerules/sqlite-mcp.md (adapted)
│   ├── terminal-execution.md          ← from .clinerules/terminal_execution.md (merged + adapted)
│   └── web-research.md                ← from .clinerules/web-research.md (adapted)
└── skills/                            ← NEW directory
    ├── laravel-best-practices/
    │   ├── SKILL.md                   ← from .cline/skills/laravel-best-practices/SKILL.md
    │   └── rules/                     ← all 20 rule files copied verbatim
    │       ├── advanced-queries.md
    │       ├── architecture.md
    │       ├── blade-views.md
    │       ├── caching.md
    │       ├── collections.md
    │       ├── config.md
    │       ├── db-performance.md
    │       ├── eloquent.md
    │       ├── error-handling.md
    │       ├── events-notifications.md
    │       ├── http-client.md
    │       ├── mail.md
    │       ├── migrations.md
    │       ├── queue-jobs.md
    │       ├── routing.md
    │       ├── scheduling.md
    │       ├── security.md
    │       ├── style.md
    │       ├── testing.md
    │       └── validation.md
    ├── pest-testing/
    │   └── SKILL.md                   ← from .cline/skills/pest-testing/SKILL.md
    ├── tailadmin/
    │   ├── SKILL.md                   ← from .cline/skills/tailadmin/SKILL.md
    │   └── src/                       ← all HTML/JS/CSS/images copied verbatim
    ├── tailwindcss-development/
    │   └── SKILL.md                   ← from .cline/skills/tailwindcss-development/SKILL.md
    ├── alpine-modal/
    │   └── SKILL.md                   ← from .cline/skills/alpine-modal/SKILL.md
    ├── approval-integration/
    │   └── SKILL.md                   ← from .cline/skills/approval-integration/SKILL.md
    └── datatable/
        └── SKILL.md                   ← from .cline/skills/datatable/SKILL.md
```

## Content Adaptation Rules

### General adaptations (apply to all instruction files):

1. **Strip Cline YAML frontmatter** — Kilo instruction files don't need `trigger: model_decision` / `description:` frontmatter. Keep only the Markdown title and body.
2. **Remove all `<!-- -->` HTML comments** and markdown-style `> [!NOTE]` → convert to plain markdown.
3. **Adapt font-awesome/arrow symbols** — `→` used in Cline files is fine in UTF-8, keep as-is.
4. **Do not add new content** — only strip, adapt tool refs, and merge conflicts.

### Tool-name adaptation table:

| Cline tool/reference | Kilo equivalent |
|---|---|
| `execute_command` | `bash` |
| `run_command` tool | `bash` tool |
| `SYSTEM INFORMATION` block / `Default Shell` value | The `<env>` block in the system prompt — no separate tool needed |
| `playwright_navigate` | `kilo-playwright_browser_navigate` |
| `playwright_get_visible_text` | `kilo-playwright_browser_snapshot` |
| `playwright_get_visible_html` | `kilo-playwright_browser_snapshot` |
| `read_file` | `read` |
| `search-docs` (MCP tool) | `webfetch` (equivalent) |
| SQLite MCP `query` with `"readonly": true` | Keep as-is; Kilo MCP works the same way |

### Per-file merge/adaptation notes:

#### `alpinejs.md`
- Base: `.agents/rules/alpine.md` (newer, has frontmatter)
- Merge in: `.clinerules/alpine.md` section 5 ("Confirmation Modals & Type-to-Confirm" — 1 paragraph at line 38-39) which references `alpine-modal` skill
- Strip frontmatter
- Convert the inline markdown link `[link](URL)` to text: "read `.kilo/skills/alpine-modal/SKILL.md`"

#### `cdn-libraries.md`
- Source: `.agents/rules/cdn-libraries.md` (32 lines, cleaner than `.clinerules/` 27 lines)
- Strip frontmatter
- Convert LaTeX-style arrows `$\rightarrow$` to plain `→`

#### `tailwind-v4.md`
- Source: `.agents/rules/tailwind4.md` (44 lines)
- Strip frontmatter
- Update exemption note path: `.agents/skills/tailadmin-scaffolder/` → `.kilo/skills/tailadmin/`
- Keep `> [!NOTE]` → convert to `**Note:**`

#### `php84.md`
- Source: `.agents/rules/php84.md` (30 lines)
- Strip frontmatter
- No other changes — purely PHP conventions, no Cline tool references

#### `testing.md`
- Source: `.clinerules/testing.md` (47 lines)
- Adapt: `php artisan test --compact tests/Feature/NewTestFile.php` → `php artisan test` (AGENTS.md already says `--compact`)
- Adapt: `php artisan test` to match AGENTS.md convention

#### `ui-planning.md`
- Source: `.clinerules/ui-planning.md` (53 lines)
- No tool references to adapt — pure ASCII wireframing convention
- Keep as-is

#### `sqlite-mcp.md`
- Source: `.clinerules/sqlite-mcp.md` (39 lines)
- Adapt: The MCP tool names (`query`, `schema`, `table_info`, `list_databases`) are MCP-provider-dependent. Keep generically — these are correct under any SQLite MCP server.
- Keep read-only policy as-is

#### `terminal-execution.md`
- Source: `.clinerules/terminal_execution.md` (114 lines — the detailed version)
- The `.agents/rules/terminal_execution.md` (16 lines, "never use run_command") is contradictory to Kilo's bash tool. Do NOT use it.
- Adapt: `execute_command` → `bash` throughout
- Adapt: "`Default Shell` value provided in the `SYSTEM INFORMATION` block" → "the `<env>` block in the system prompt" (Kilo's equivalent)
- Adapt: Shell detection table — Kilo's `<env>` block shows `Platform:` and `Default shell:` fields
- Adapt forward-slash path section: `php vendor/bin/pint` → same (already Kilo convention)
- Keep the full destructiveness classification table — Kilo's bash tool benefits from the same guardrails
- The `.agents/` version is ignored entirely

#### `web-research.md`
- Source: `.clinerules/web-research.md` (41 lines)
- Adapt: `playwright_navigate` → `kilo-playwright_browser_navigate`
- Adapt: `playwright_get_visible_text` → `kilo-playwright_browser_snapshot`
- Adapt: `playwright_get_visible_html` → `kilo-playwright_browser_snapshot`
- The "MCP-first" workflow using Playwright should reference Kilo's own browser tools

### Skipped content:

| File | Reason for skipping |
|---|---|
| `.clinerules/laravel-skills.md` | Was a routing index for Cline's skill system. The `laravel-best-practices` SKILL.md already contains the full index in its "Rule Index" table. Redundant. |
| `.agents/rules/nativephp.md` | Explicitly disabled (3-line stub: "NativePHP was uninstalled from this project") |
| `.agents/AGENTS.md` | Contradicts Kilo's bash tool (says "never use run_command"). Kilo has its own permission-based bash tool. |
| `.agents/skills/tailadmin-scaffolder/` | Redundant — `.cline/skills/tailadmin/` is the canonical source with full HTML/JS/CSS assets |

## AGENTS.md Extension

A single supplementary block appended to root `AGENTS.md`:

```markdown

=== supplementary coding guardrails ===

# Supplementary Guardrails

Additional coding guardrails are loaded from `.kilo/instructions/*.md`. These cover:
Alpine.js reactivity, CDN-only third-party libraries, Tailwind CSS v4 CDN usage,
PHP 8.4 strict standards, TDD requirements, ASCII UI wireframing, SQLite read-only policy,
terminal command destructiveness classification, and MCP-first web research.
```

## kilo.json (new file at project root)

```json
{
  "$schema": "https://app.kilo.ai/config.json",
  "instructions": [
    ".kilo/instructions/*.md"
  ],
  "skills": {
    "paths": [
      ".kilo/skills"
    ]
  }
}
```

## Task List

### Phase 1: Create directory structure
1. Create `.kilo/instructions/` directory
2. Create `.kilo/skills/` directory

### Phase 2: Create instruction files (9 files)
3. Write `.kilo/instructions/alpinejs.md` — merged from `.agents/rules/alpine.md` + `.clinerules/alpine.md` §5, stripped frontmatter
4. Write `.kilo/instructions/cdn-libraries.md` — from `.agents/rules/cdn-libraries.md`, stripped frontmatter, `$\rightarrow$` → `→`
5. Write `.kilo/instructions/tailwind-v4.md` — from `.agents/rules/tailwind4.md`, stripped frontmatter, TailAdmin ref updated to `.kilo/skills/tailadmin/`
6. Write `.kilo/instructions/php84.md` — from `.agents/rules/php84.md`, stripped frontmatter
7. Write `.kilo/instructions/testing.md` — from `.clinerules/testing.md`
8. Write `.kilo/instructions/ui-planning.md` — from `.clinerules/ui-planning.md`, verbatim
9. Write `.kilo/instructions/sqlite-mcp.md` — from `.clinerules/sqlite-mcp.md`
10. Write `.kilo/instructions/terminal-execution.md` — from `.clinerules/terminal_execution.md`, adapted tool references
11. Write `.kilo/instructions/web-research.md` — from `.clinerules/web-research.md`, adapted tool references

### Phase 3: Create skill files (7 skills)
12. Copy `.cline/skills/laravel-best-practices/` → `.kilo/skills/laravel-best-practices/` (SKILL.md + all 20 rules)
13. Copy `.cline/skills/pest-testing/SKILL.md` → `.kilo/skills/pest-testing/SKILL.md`
14. Copy `.cline/skills/tailadmin/` → `.kilo/skills/tailadmin/` (SKILL.md + all src/ files)
15. Copy `.cline/skills/tailwindcss-development/SKILL.md` → `.kilo/skills/tailwindcss-development/SKILL.md`
16. Copy `.cline/skills/alpine-modal/SKILL.md` → `.kilo/skills/alpine-modal/SKILL.md`
17. Copy `.cline/skills/approval-integration/SKILL.md` → `.kilo/skills/approval-integration/SKILL.md`
18. Copy `.cline/skills/datatable/SKILL.md` → `.kilo/skills/datatable/SKILL.md`

### Phase 4: Configuration files
19. Write `kilo.json` at project root (content above)
20. Append the supplementary guardrail block to `AGENTS.md`

### Phase 5: Validation
21. Verify `kilo.json` is valid JSON by parsing it
22. Verify all `.kilo/instructions/*.md` files exist and are non-empty
23. Verify all `.kilo/skills/*/SKILL.md` files exist
24. Verify `AGENTS.md` still starts with the original `<laravel-boost-guidelines>` block
25. Confirm original `.clinerules/`, `.cline/`, `.agents/` directories are untouched

## Risks & Notes

- **Tailwind CDN-vs-Vite conflict**: The `tailwind-v4.md` instruction mandates CDN-only zero-build Tailwind. The project already has `vite.config.js` and npm dependencies. These instructions will be loaded into the system prompt alongside AGENTS.md which references `npm run dev`. This creates contradictory guidance. The user chose to preserve CDN-only verbatim — Kilo may generate CDN-based HTML while the project's build pipeline expects Vite.
- **Cline skill frontmatter**: The copied skill SKILL.md files already have valid Kilo-compatible YAML frontmatter (`name`, `description`, `license`, `metadata`). No changes needed — Kilo reads the same fields.
- **TailAdmin source files**: The `src/` asset files (HTML partials, CSS, JS, SVG images) are ~150+ files totaling significant disk space. They are copied verbatim — the TailAdmin SKILL.md references them by relative path.
- **Backslashes in paths**: Ensure all file paths use forward slashes when writing.

---
name: web-research
description: "MCP-first web research priority for external package implementation. Use when implementing, configuring, or debugging external Composer or NPM packages. Prioritizes web research via Playwright MCP over internal knowledge. Covers searching docs, visiting official sources, and only falling back to training data when research is inconclusive."
license: MIT
metadata:
  author: laravel
---

# Web Research Priority Rule (MCP-First for Package Implementation)

This rule strictly governs how the agent researches and implements external packages (Laravel, PHP, JavaScript, Composer, NPM) to ensure accuracy and up-to-date information.

## 1. MCP-First Research Requirement

When tasked with **implementing, configuring, or debugging** an external package or library, the agent **MUST** prioritize external web research via the `github.com/executeautomation/mcp-playwright` MCP server before falling back to internal knowledge.

### Research Workflow

1. **Search:** Use `playwright_navigate` with a search engine URL (e.g., `https://www.google.com/search?q=...`) or directly navigate to package registries (Packagist, npm) to discover relevant documentation, blog posts, or GitHub issues for the specific package/version.
2. **Visit:** Use `playwright_navigate` to open official documentation pages, README files, or API reference pages. Use `playwright_get_visible_text` or `playwright_get_visible_html` to extract detailed installation instructions, API signatures, configuration options, and usage examples from authoritative sources.
3. **Synthesize:** Base the implementation on the research findings — not on internal training data alone.

### When to Fall Back to Internal Knowledge

Only fall back to internal (training) knowledge when ALL of the following are true:

- Web research via Playwright tools returned no relevant results for the specific package/version (e.g., documentation pages are unavailable, return 404, or are behind authentication).
- OR the results returned are outdated, contradictory, or insufficient to implement the feature correctly.
- In such cases, the agent **MUST explicitly note** in its response: *"External research was inconclusive; proceeding based on internal knowledge."*

## 2. Scope of This Rule

This rule applies to the following scenarios:

| Category | Examples |
|---|---|
| **Composer packages** | `composer require spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `laravel/sanctum`, etc. |
| **NPM packages** | `npm install lodash`, `alpinejs`, `flatpickr`, `chart.js`, etc. |
| **Package API usage** | Writing service providers, facades, middleware, config files for third-party packages |
| **Debugging package errors** | Resolving version conflicts, configuration mismatches, or runtime errors from packages |
| **Version-specific features** | "How do I use this feature in v3 vs v4 of package X?" |

## 3. Exemptions (No Pre-Research Needed)

The following do **not** require MCP-first research unless the user explicitly asks for a version-specific or edge-case feature:

- **Standard Laravel core features:** Routing, Eloquent ORM basics, Blade templating, Artisan commands, Migrations, Seeders, Factories, Middleware, Form Requests, Mail, Notifications, Queues.
- **Well-known PHP/JS built-ins:** Native PHP functions, standard array/string manipulation, basic JavaScript DOM APIs (though these typically fall under Alpine.js or CDN rules).
- **Previously researched packages:** If the same package was already researched earlier in the same session, the agent may reuse that knowledge without re-researching.

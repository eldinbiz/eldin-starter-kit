---
trigger: model_decision
description: Triggers when planning any UI change or creating a new interface layout. Enforces rendering ASCII box-drawing wireframes before writing code.
---

# UI Planning & ASCII Wireframing Rule

When planning any UI change or creating a new interface, render the layout as an ASCII box-drawing diagram before writing Blade/Alpine/Tailwind code.

## 1. ASCII Conventions

- Use box-drawing characters (`┌─┐│└┘├┤┴┬┼`) for layout boundaries and component edges.
- Annotate Tailwind classes in parentheses alongside elements: `(bg: gray-900, text-white)`
- Denote interactive elements in square brackets: `[Save]`, `[Cancel]`, `[▼]` for dropdowns
- Mark conditional/`x-show` areas with dashed containers: `┌ ╌ ╌ ╌ ╌ ╌ ┐`
- Annotate Alpine.js bindings inline: `(x-data="{ open: false }")`, `@click="open = true"`
- Always include the structural hierarchy: topbar → sidebar | main content area → footer
- Call out key responsive breakpoints: `(md:grid-cols-2, lg:grid-cols-3)`

## 2. When to Render ASCII

Render an ASCII diagram when:
- Creating a new Blade view or component from scratch
- Significantly restructuring an existing page layout
- Adding a multi-section dashboard or panel
- Discussing UI options with the user before implementation

Skip for:
- Single-element changes (adding a button, badge, or icon)
- Purely backend/logic changes with no UI impact

## 3. Example

```
┌─────────────────────────────────────────────────────────────┐
│  TOP NAVBAR (bg: gray-900, text-white)                      │
│  [Logo]              [Bell] [Avatar ▼]                      │
├────────────────────┬────────────────────────────────────────┤
│  SIDEBAR (w-64)    │  MAIN CONTENT AREA                     │
│                    │                                        │
│  ─ Dashboard       │  ┌──────────────────────────────────┐  │
│  ─ Users           │  │  Card 1          Card 2          │  │
│  ─ Settings        │  │  ─────────        ─────────       │  │
│  ─ Reports         │  │  Value: 1,234     Value: 567      │  │
│                    │  └──────────────────────────────────┘  │
│                    │                                        │
│                    │  ┌──────────────────────────────────┐  │
│                    │  │  Data Table                      │  │
│                    │  │  ┌──────┬────────┬──────────┐    │  │
│                    │  │  │ ID   │ Name   │ Action   │    │  │
│                    │  │  ├──────┼────────┼──────────┤    │  │
│                    │  │  │ 1    │ Alice  │ [Edit]   │    │  │
│                    │  │  │ 2    │ Bob    │ [Edit]   │    │  │
│                    │  │  └──────┴────────┴──────────┘    │  │
│                    │  └──────────────────────────────────┘  │
├────────────────────┴────────────────────────────────────────┤
│  FOOTER (text-sm, text-gray-500)                            │
└─────────────────────────────────────────────────────────────┘
```

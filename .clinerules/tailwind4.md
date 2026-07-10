# Tailwind CSS v4 (Non-Compilation Runtime) Rule

This rule strictly governs all frontend styling tasks, ensuring the AI agent uses the on-the-fly, zero-build compilation paradigm of Tailwind CSS v4 while strictly adhering to a structured design system.

> [!NOTE]
> **TailAdmin Scaffolder Exemption:**
> All rules, styling paradigms, layout patterns, and utility scale restrictions defined in this document **do not apply** to layouts, components, or styling specifications defined in the `tailadmin-scaffolder` skills (located in `.cline/skills/tailadmin/`). For components or pages based on TailAdmin patterns, implement the styles and markup exactly as specified in those skills.

## 1. Zero-Build Architecture
- **No Build Tools:** Do not generate scripts or dependencies for Vite, PostCSS, Webpack, or Tailwind CLI.
- **The Core Script:** Ensure all layout layouts or HTML base layouts load the Tailwind v4 Browser script inside the `<head>`:
  ```html
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  ```

## 2. CSS-First Theme Customization (No JavaScript Config)
Tailwind v4 does not use `tailwind.config.js`. Any custom theme configurations, custom spacing, or extending utility tokens must be written exclusively using CSS inside a `<style type="text/tailwindcss">` block:
```html
<style type="text/tailwindcss">
  @theme {
    --color-primary: #4f46e5;
    --color-brand-accent: #f59e0b;
    --font-display: "Inter", sans-serif;
    /* Add any custom explicit spacing or column extensions here if absolutely required */
  }
</style>
```

## 3. Strict Design System & Core Syntax Paradigms
When writing class strings in HTML, Blade components, or Livewire views, utilize native modern v4 features safely:
- **NO Arbitrary Values:** Absolutely refrain from using un-configured sizes or arbitrary bracket notation for dimensions, positioning, or grids (e.g., Do NOT use `w-[32.5rem]`, `top-[13px]`, or `grid-cols-15`).
- **Strict Utility Scale:** Rely exclusively on Tailwind's native core utility scales (e.g., `w-4`, `h-64`, `p-6`) or design tokens explicitly configured in the `@theme` block.
- **Standard Grid Columns:** Limit grid layouts to standard core scales (1 through 12). If a layout requires specialized column behavior, use standard flexbox or configure it explicitly in the theme block first.
- **Container Queries:** Style wrappers contextually without external plugins using `@md:` or `@lg:` tags (e.g., `@container class="md:grid-cols-2"`).
- **3D Transforms:** Use direct HTML utilities like `rotate-x-45` and `perspective-1000`.

## 4. Crucial Guidelines for AI Generation
- **Avoid `@apply` statements:** Do not generate normal global stylesheets looking for a pre-processor context. Use raw, structured utility classes directly on HTML attributes.
- **Asset Integrity:** When generating HTML elements, strictly embed all styling classes inside the element `class="..."` properties. Do not suggest running `npm run dev` or compiling assets.

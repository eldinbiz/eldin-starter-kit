# TailAdmin Layout & Page Shell Skill

## When to use
Use this skill when scaffolding a new TailAdmin page or modifying the base layout structure.

## Page body skeleton (every dashboard page)

```html
<body
  x-data="{ page: 'pagename', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
  x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
  :class="{'dark bg-gray-900': darkMode === true}"
>
  <include src="./partials/preloader.html" />
  <div class="flex h-screen overflow-hidden">
    <include src="./partials/sidebar.html" />
    <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
      <include src="./partials/overlay.html" />
      <include src="./partials/header.html" />
      <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div x-data="{ pageName: 'Section Name' }">
            <include src="./partials/breadcrumb.html" />
          </div>
          <!-- PAGE CONTENT -->
        </div>
      </main>
    </div>
  </div>
</body>
```

## Key conventions

- **Page tracking**: `x-data="{ page: 'pagename' }"` on `<body>` — must match sidebar.html's `page === 'xxx'` checks
- **Dark mode**: localStorage via `x-init`, `:class` toggles `.dark` on `<body>`
- **All pages use `<include src="...">`** for partials (Vite/parcel)
- **Content wrapper**: `mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6`
- **Breadcrumb**: wrap in `<div x-data="{ pageName: 'Section Name' }">`

## Source files reference

| Component | File |
|-----------|------|
| Full page skeleton | `src/index.html` |
| Preloader | `src/partials/preloader.html` |
| Overlay | `src/partials/overlay.html` |
| Breadcrumb | `src/partials/breadcrumb.html` |
| Full sidebar nav | `src/partials/sidebar.html` |
| Full header | `src/partials/header.html` |
| Decorative shapes | `src/partials/common-grid-shape.html` |
| Social links | `src/partials/common-social-links.html` |
| Blank starter page | `src/blank.html` |

## Component card pattern

```html
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  <div class="px-6 py-5">
    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">Section Title</h3>
  </div>
  <div class="border-t border-gray-100 px-6 py-6.5 dark:border-gray-800">
    <!-- content -->
  </div>
</div>
```

## Grid helper classes

- `grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6` — 2-col
- `grid grid-cols-12 gap-4 md:gap-6` — 12-col dashboard
- `col-span-12 xl:col-span-7` / `col-span-12 xl:col-span-5` — split
- `grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3` — 3-col

## Sidebar nav structure (key patterns)

Uses Alpine `$persist` for menu state:
- `x-data="{selected: $persist('Dashboard')}"` on `<nav>`
- Top items: `class="menu-item group"` + `:class="(selected === 'X') ? 'menu-item-active' : 'menu-item-inactive'"`
- Dropdown items: `menu-dropdown-item` + `menu-dropdown-item-active` / `menu-dropdown-item-inactive`
- Icon fill: `menu-item-icon-active` / `menu-item-icon-inactive`
- Arrow: `menu-item-arrow-active` (rotates 180deg) / `menu-item-arrow-inactive`
- Collapsed: `:class="sidebarToggle ? 'lg:hidden' : ''"` on text spans
- See full sidebar in `src/partials/sidebar.html`

## 3-dot dropdown menu

Used across many widgets (top-card-group, watchlist, upcoming-schedule, chart headers):
- `x-data="{openDropDown: false}"` on wrapper div
- `@click.outside="openDropDown = false"` closes on outside click
- See pattern in `src/partials/top-card-group.html`

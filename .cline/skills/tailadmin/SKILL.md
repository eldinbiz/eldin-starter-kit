---
name: tailadmin-scaffolder
description: Use this skill when scaffolding or modifying TailAdmin pages — covering authentication pages (sign in, sign up, 404, profile), data display elements (tables, charts, metric cards, maps, calendar, datepicker), form inputs and layouts, page shells and sidebar navigation, and UI components (alerts, buttons, badges, avatars, images, videos, media cards).
---

# TailAdmin Scaffolder Skill

This skill provides comprehensive reference patterns for building TailAdmin-based pages.

## Source Files

Reference source files (HTML, JS, CSS, images) are located in the `src/` directory alongside this file.

---

## Layout & Page Shell

Use this when scaffolding a new TailAdmin page or modifying the base layout structure.

### Page body skeleton (every dashboard page)

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

### Key conventions

- **Page tracking**: `x-data="{ page: 'pagename' }"` on `<body>` — must match sidebar.html's `page === 'xxx'` checks
- **Dark mode**: localStorage via `x-init`, `:class` toggles `.dark` on `<body>`
- **All pages use `<include src="...">`** for partials (Vite/parcel)
- **Content wrapper**: `mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6`
- **Breadcrumb**: wrap in `<div x-data="{ pageName: 'Section Name' }">`

### Source files reference

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

### Component card pattern

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

### Grid helper classes

- `grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6` — 2-col
- `grid grid-cols-12 gap-4 md:gap-6` — 12-col dashboard
- `col-span-12 xl:col-span-7` / `col-span-12 xl:col-span-5` — split
- `grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3` — 3-col

### Sidebar nav structure (key patterns)

Uses Alpine `$persist` for menu state:
- `x-data="{selected: $persist('Dashboard')}"` on `<nav>`
- Top items: `class="menu-item group"` + `:class="(selected === 'X') ? 'menu-item-active' : 'menu-item-inactive'"`
- Dropdown items: `menu-dropdown-item` + `menu-dropdown-item-active` / `menu-dropdown-item-inactive`
- Icon fill: `menu-item-icon-active` / `menu-item-icon-inactive`
- Arrow: `menu-item-arrow-active` (rotates 180deg) / `menu-item-arrow-inactive`
- Collapsed: `:class="sidebarToggle ? 'lg:hidden' : ''"` on text spans
- See full sidebar in `src/partials/sidebar.html`

### 3-dot dropdown menu

Used across many widgets (top-card-group, watchlist, upcoming-schedule, chart headers):
- `x-data="{openDropDown: false}"` on wrapper div
- `@click.outside="openDropDown = false"` closes on outside click
- See pattern in `src/partials/top-card-group.html`

---

## Authentication Pages

Use this when building authentication pages (sign in, sign up, 404 error, profile) using TailAdmin.

### Key differences from dashboard pages

| Aspect | Dashboard pages | Auth pages |
|--------|----------------|------------|
| Layout | Sidebar + header + main | Two-panel split (form + branding) or shell-based |
| `x-data` on body | Has `page`, `sidebarToggle`, `loaded` | Just `page` + `darkMode` |
| Dark mode toggle | In header | Fixed bottom-right floating button |
| Decorative shapes | Some pages | `common-grid-shape.html` |
| Auth page `page` value | — | `'comingSoon'` (not `'signin'`/`'signup'`) |

### Source files reference

#### Sign In
| Pattern | File |
|---------|------|
| Full sign in page (two-panel) | `src/signin.html` |

**Left panel**: form with back link, logo, heading, social buttons, "Or" divider, email/password fields, "Keep me logged in" checkbox, "Forgot password?" link, submit button
**Right panel** (`hidden lg:flex lg:w-1/2`): dark `bg-gray-900` section with `auth-logo.svg` and branding text

#### Sign Up
| Pattern | File |
|---------|------|
| Full sign up page (two-panel) | `src/signup.html` |

Same two-panel layout with sign up fields (name, email, password, confirm password, T&C checkbox).

#### 404 Error
| Pattern | File |
|---------|------|
| Full 404 page (with sidebar shell) | `src/404.html` |

Uses the **full dashboard shell** (preloader, sidebar, header, overlay) — not standalone. Shows:
- `text-title-2xl font-bold` "ERROR" heading
- Error image (`../images/error/404-dark.svg` / `404-light.svg`)
- "Oops! Page Not Found" message
- "Back to Home Page" button

#### Profile
| Pattern | File |
|---------|------|
| Full profile page | `src/profile.html` |
| Profile info edit modal | `src/partials/profile/profile-info-modal.html` |
| Profile address edit modal | `src/partials/profile/profile-address-modal.html` |

**Profile structure:**
- Cover image + avatar overlay
- User info (name, role, location) with social icon links
- Dual Alpine modals: `isProfileInfoModal` and `isProfileAddressModal`

### Auth-specific patterns

#### "Back to dashboard" link
```html
<a href="index.html" class="mb-10 inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
  <svg width="20" height="20"><path d="M15.8332 10H4.1665M4.1665 10L9.1665 15M4.1665 10L9.1665 5" stroke="currentColor" stroke-width="1.5"/></svg>
  Back to dashboard
</a>
```

#### Social login button
```html
<button class="inline-flex items-center justify-center gap-3 py-3 text-sm font-normal text-gray-700 bg-gray-100 rounded-lg px-7 hover:bg-gray-200 dark:bg-white/5 dark:text-white/90 dark:hover:bg-white/10 w-full">
  <!-- icon --> Sign in with Google
</button>
```

#### "Or" divider
```html
<div class="relative py-3 sm:py-5">
  <div class="absolute inset-0 flex items-center">
    <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
  </div>
  <div class="relative flex justify-center text-sm">
    <span class="p-2 text-gray-400 bg-white dark:bg-gray-900 sm:px-5 sm:py-2">Or</span>
  </div>
</div>
```

#### Fixed dark mode toggle button
```html
<button @click="darkMode = !darkMode" class="fixed bottom-6 right-6 z-50 flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
  <!-- sun/moon SVG -->
</button>
```

---

## Forms & Inputs

Use this when building or modifying form pages in TailAdmin.

### Design tokens

**Font:** Outfit (Google Font) — `font-outfit` on body
**Body:** `bg-gray-50`/`dark:bg-gray-900`

### Input base class string

Every `<input>`, `<select>`, `<textarea>` uses:

```
dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30
```

### Source files reference

| Pattern | File |
|---------|------|
| All form elements (inputs, selects, checkboxes, toggles, radios, file upload, datepickers, multi-select, textareas, validation) | `src/form-elements.html` |
| Flatpickr datepicker widget | `src/partials/datepicker.html` |
| Calendar event modal (date inputs) | `src/partials/calendar-event-modal.html` |
| Search input (header) | `src/partials/header.html` |
| File upload (Dropzone init) | `src/js/index.js` |

### Form conventions

- **Labels**: `mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400`
- **Form grid**: `grid grid-cols-1 gap-6 sm:grid-cols-2`
- **Required asterisk**: `<span class="text-error-500">*</span>`
- **Disabled state**: `disabled:border-gray-100 disabled:bg-gray-50 disabled:placeholder:text-gray-300 dark:disabled:border-gray-800 dark:disabled:bg-white/[0.03]`

### Common input patterns (quick reference)

#### Text / email / password
```html
<div>
  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Label</label>
  <input type="text" placeholder="Placeholder" class="[inputBase]" />
</div>
```

#### Password with show/hide
Alpine: `x-data="{ showPassword: false }"` on wrapper. `:type="showPassword ? 'text' : 'password'"` on input. Add `pr-11` for icon padding.

#### Select
Same `[inputBase]` class on `<select>`.

#### Checkbox
`<input type="checkbox" class="sr-only peer" />` + styled `<span>` sibling using `peer-checked:border-brand-500 peer-checked:bg-brand-500`

#### Toggle
`x-data="{ toggle: false }"` on wrapper. Button toggles between `bg-brand-500` and `bg-gray-300 dark:bg-gray-700`. Inner span slides with `translate-x-[1.375rem]` / `translate-x-0.5`.

#### Radio
`<input type="radio" class="sr-only peer" />` + styled span using `peer-checked:border-[6px] peer-checked:border-brand-500`

#### Textarea
Same `[inputBase]` with `resize-none` added.

### JS-backed patterns

| Pattern | JS file | Notes |
|---------|---------|-------|
| Flatpickr datepicker | `src/js/index.js` | Init on `.datepicker`, range mode, static |
| Dropzone file upload | `src/js/index.js` | Init on `#demo-upload` |
| Clipboard copy | `src/js/index.js` | `#copy-input` / `#copy-button` / `#website-input` |
| Search Cmd+K | `src/js/index.js` | Focus `#search-input` on meta+k or `/` |
| Alpine.js init | `src/js/index.js` | `Alpine.plugin(persist)`, `Alpine.start()` |

---

## Data Display

Use this when adding data display elements: tables, charts, metric/KPI cards, maps, watchlist, schedule.

### Source files reference

#### Tables
| Pattern | File |
|---------|------|
| Product table (filter + product rows) | `src/partials/table/table-01.html` |
| User table (avatars + team stack) | `src/partials/table/table-06.html` |
| Basic tables page (all table variants) | `src/basic-tables.html` |

**Table wrapper:** `overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]`

**Header row:** `tr class="border-y border-gray-100 dark:border-gray-800"` + `th class="py-3"` with `<p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">`

**Table toolbar button:** `inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700`

#### Metric / KPI Cards
| Pattern | File |
|---------|------|
| Metric group (Customers, Orders) | `src/partials/metric-group/metric-group-01.html` |

**Card:** `rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6`
**Icon box:** `flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800`
**Value:** `text-title-sm font-bold text-gray-800 dark:text-white/90`
**Trend (up):** `rounded-full bg-success-50 ... text-success-600` + up arrow SVG
**Trend (down):** `rounded-full bg-error-50 ... text-error-600` + down arrow SVG

#### Widgets
| Pattern | File |
|---------|------|
| Top Card Group (Channels/Pages) | `src/partials/top-card-group.html` |
| Watchlist (stock ticker) | `src/partials/watchlist.html` |
| Upcoming Schedule (checkable items) | `src/partials/upcoming-schedule.html` |

#### Charts
| Pattern | File |
|---------|------|
| Bar chart (Sales) | `src/js/components/charts/chart-01.js` & `src/partials/chart/chart-01.html` |
| RadialBar chart (Monthly Target) | `src/js/components/charts/chart-02.js` & `src/partials/chart/chart-02.html` |
| Area chart (gradient) | `src/js/components/charts/chart-03.js` & `src/partials/chart/chart-03.html` |
| Full charts pages | `src/bar-chart.html`, `src/line-chart.html` |

**Import pattern in `src/js/index.js`:**
```javascript
import chart01 from "./components/charts/chart-01";
document.addEventListener("DOMContentLoaded", () => { chart01(); });
```

**Chart types:** `"bar"`, `"line"`, `"area"`, `"donut"`, `"radialBar"`

**Chart wrapper (responsive):**
```html
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  <div class="custom-scrollbar max-w-full overflow-x-auto">
    <div class="min-w-[700px]">
      <div id="chartOne"></div>
    </div>
  </div>
</div>
```

**Chart with segmented toggle:**
```html
<div x-data="{ selected: 'overview' }">
  <div class="flex items-center justify-between px-6 pt-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Statistics</h3>
    <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-800">
      <button @click="selected = 'overview'" :class="selected === 'overview' ? 'bg-white dark:bg-gray-900 shadow-theme-xs' : ''" class="rounded-md px-4 py-2 text-sm font-medium text-gray-500">Overview</button>
      <button @click="selected = 'sales'" :class="selected === 'sales' ? 'bg-white dark:bg-gray-900 shadow-theme-xs' : ''" class="rounded-md px-4 py-2 text-sm font-medium text-gray-500">Sales</button>
    </div>
  </div>
  <div class="p-6"><div id="chartThree"></div></div>
</div>
```

#### Map
| Pattern | File |
|---------|------|
| jsVectorMap with markers | `src/js/components/map-01.js` & `src/partials/map-01.html` |

**Key config:** `selector: "#mapOne"`, `map: "world"`, `zoomOnScroll: false`, `regionStyle: { initial: { fill: "#D0D5DD" }, hover: { fill: "#465FFF" } }`
**Markers:** `{ coords: [lat, lng], name: "City" }`
**Country stats bars:** see `src/partials/map-01.html`

#### Calendar
| Pattern | File |
|---------|------|
| FullCalendar init + CRUD | `src/js/components/calendar-init.js` |
| Calendar page | `src/calendar.html` |
| Event modal | `src/partials/calendar-event-modal.html` |

**Plugins:** `dayGridPlugin`, `timeGridPlugin`, `listPlugin`, `interactionPlugin`
**Custom add button:** `<button class="fc-addEventButton-button ...">Add Event</button>`
**Event colors:** class mapping `.fc-bg-success`, `.fc-bg-danger`, `.fc-bg-primary`, `.fc-bg-warning`

#### Datepicker (Flatpickr)
| Pattern | File |
|---------|------|
| Datepicker widget | `src/partials/datepicker.html` |

**Init in `src/js/index.js`:** `flatpickr(".datepicker", { mode: "range", static: true, ... })`

---

## UI Components

Use this when adding UI components to a TailAdmin page — alerts, buttons, badges, avatars, images, videos, media cards.

### Design token reference

All components use these TailAdmin custom colors:
- `brand-{50..950}` — primary blue (500: `#465FFF`)
- `success-{50..950}` — green
- `error-{50..950}` — red
- `warning-{50..950}` — yellow/orange
- `blue-light-{50..950}` — light blue
- `gray-{25..950}` — neutral grays
- `theme-pink-500` — `#EE46BC`
- `theme-purple-500` — `#7A5AF8`

Dark mode uses `dark:` prefix on every color class. Standard pattern: `dark:border-gray-800 dark:bg-white/[0.03]`.

### Source files reference

#### Alerts
| Variant | File |
|---------|------|
| Success | `src/partials/alert/alert-success.html` |
| Warning | `src/partials/alert/alert-warning.html` |
| Error | `src/partials/alert/alert-error.html` |
| Info | `src/partials/alert/alert-info.html` |

**Outer wrapper pattern:** `rounded-xl border border-{TYPE}-500 bg-{TYPE}-50 p-4 dark:border-{TYPE}-500/30 dark:bg-{TYPE}-500/15`

#### Buttons
| Variant | File |
|---------|------|
| Primary | `src/partials/buttons/button-01.html` |
| Primary + left icon | `src/partials/buttons/button-02.html` |
| Primary + right icon | `src/partials/buttons/button-03.html` |
| Secondary (ring-based) | `src/partials/buttons/button-04.html` |
| Secondary + left icon | `src/partials/buttons/button-05.html` |
| Secondary + right icon | `src/partials/buttons/button-06.html` |

**Primary class:** `inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600`

**Secondary class (uses ring, not border):** `inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]`

**Size:** default `px-4 py-3`, large `px-5 py-3.5`

#### Avatars
| Pattern | File |
|---------|------|
| 7 plain sizes | `src/partials/avatar/avatar-01.html` |
| Online (green dot) | `src/partials/avatar/avatar-02.html` |
| Offline (red dot) | `src/partials/avatar/avatar-03.html` |
| Busy (yellow dot) | `src/partials/avatar/avatar-04.html` |

**Status dot colors:** `bg-success-500` (online), `bg-error-500` (offline), `bg-warning-500` (busy)

**Sizes:** `h-6` through `h-16`, dot scales proportionally (see `src/partials/avatar/avatar-01.html`)

**Avatar group (overlapping):** `flex -space-x-2` with `border-2 border-white dark:border-gray-900` on each circle

#### Badges
| Pattern | File |
|---------|------|
| Light bg (7 colors) | `src/partials/badge/badge-01.html` |
| Solid bg (7 colors) | `src/partials/badge/badge-02.html` |
| Light + left icon | `src/partials/badge/badge-03.html` |
| Solid + left icon | `src/partials/badge/badge-04.html` |
| Light + right icon | `src/partials/badge/badge-05.html` |
| Solid + right icon | `src/partials/badge/badge-06.html` |

**Color mapping (light):** `bg-{COLOR}-50 text-{COLOR}-{600|500} dark:bg-{COLOR}-500/15 dark:text-{COLOR}-{400|500}`
**Color mapping (solid):** `bg-{COLOR}-500 text-white`

**Table status badges:** `rounded-full bg-{success|warning|error}-50 px-2 py-0.5 text-theme-xs font-medium text-{color}-600 dark:...`

#### Images
| Pattern | File |
|---------|------|
| Single image | `src/partials/grid-image/image-01.html` |
| 2-column grid | `src/partials/grid-image/image-02.html` |
| 3-column grid | `src/partials/grid-image/image-03.html` |

#### Videos
| Pattern | File |
|---------|------|
| Thumbnail overlay | `src/index.html` (not a partial — see the video cards) |
| YouTube 16:9 | `src/partials/video/video-01.html` |
| YouTube 4:3 | `src/partials/video/video-02.html` |
| YouTube 21:9 | `src/partials/video/video-03.html` |
| YouTube 1:1 | `src/partials/video/video-04.html` |

**Aspect ratios:** `aspect-video` (16:9), `aspect-4/3`, `aspect-21/9`, `aspect-square` (1:1)

#### Media cards
| Pattern | File |
|---------|------|
| Full media grid with search + upload | `src/partials/media-card.html` |

**Color map (icon bg):** Image=success, Video=theme-pink, Audio=blue-light, Apps=orange, Docs=warning, Downloads=theme-purple

# TailAdmin Data Display Skill

## When to use
Use this skill when adding data display elements: tables, charts, metric/KPI cards, maps, watchlist, schedule.

## Source files reference

### Tables
| Pattern | File |
|---------|------|
| Product table (filter + product rows) | `src/partials/table/table-01.html` |
| User table (avatars + team stack) | `src/partials/table/table-06.html` |
| Basic tables page (all table variants) | `src/basic-tables.html` |

**Table wrapper:** `overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]`

**Header row:** `tr class="border-y border-gray-100 dark:border-gray-800"` + `th class="py-3"` with `<p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">`

**Table toolbar button:** `inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700`

### Metric / KPI Cards
| Pattern | File |
|---------|------|
| Metric group (Customers, Orders) | `src/partials/metric-group/metric-group-01.html` |

**Card:** `rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6`
**Icon box:** `flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800`
**Value:** `text-title-sm font-bold text-gray-800 dark:text-white/90`
**Trend (up):** `rounded-full bg-success-50 ... text-success-600` + up arrow SVG
**Trend (down):** `rounded-full bg-error-50 ... text-error-600` + down arrow SVG

### Widgets
| Pattern | File |
|---------|------|
| Top Card Group (Channels/Pages) | `src/partials/top-card-group.html` |
| Watchlist (stock ticker) | `src/partials/watchlist.html` |
| Upcoming Schedule (checkable items) | `src/partials/upcoming-schedule.html` |

### Charts
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

### Map
| Pattern | File |
|---------|------|
| jsVectorMap with markers | `src/js/components/map-01.js` & `src/partials/map-01.html` |

**Key config:** `selector: "#mapOne"`, `map: "world"`, `zoomOnScroll: false`, `regionStyle: { initial: { fill: "#D0D5DD" }, hover: { fill: "#465FFF" } }`
**Markers:** `{ coords: [lat, lng], name: "City" }`
**Country stats bars:** see `src/partials/map-01.html`

### Calendar
| Pattern | File |
|---------|------|
| FullCalendar init + CRUD | `src/js/components/calendar-init.js` |
| Calendar page | `src/calendar.html` |
| Event modal | `src/partials/calendar-event-modal.html` |

**Plugins:** `dayGridPlugin`, `timeGridPlugin`, `listPlugin`, `interactionPlugin`
**Custom add button:** `<button class="fc-addEventButton-button ...">Add Event</button>`
**Event colors:** class mapping `.fc-bg-success`, `.fc-bg-danger`, `.fc-bg-primary`, `.fc-bg-warning`

### Datepicker (Flatpickr)
| Pattern | File |
|---------|------|
| Datepicker widget | `src/partials/datepicker.html` |

**Init in `src/js/index.js`:** `flatpickr(".datepicker", { mode: "range", static: true, ... })`

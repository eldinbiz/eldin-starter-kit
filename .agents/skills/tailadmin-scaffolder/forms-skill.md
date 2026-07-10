# TailAdmin Forms & Inputs Skill

## When to use
Use this skill when building or modifying form pages in TailAdmin.

## Design tokens

**Font:** Outfit (Google Font) — `font-outfit` on body
**Body:** `bg-gray-50`/`dark:bg-gray-900`

## Input base class string

Every `<input>`, `<select>`, `<textarea>` uses:

```
dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30
```

## Source files reference

| Pattern | File |
|---------|------|
| All form elements (inputs, selects, checkboxes, toggles, radios, file upload, datepickers, multi-select, textareas, validation) | `src/form-elements.html` |
| Flatpickr datepicker widget | `src/partials/datepicker.html` |
| Calendar event modal (date inputs) | `src/partials/calendar-event-modal.html` |
| Search input (header) | `src/partials/header.html` |
| File upload (Dropzone init) | `src/js/index.js` |

## Form conventions

- **Labels**: `mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400`
- **Form grid**: `grid grid-cols-1 gap-6 sm:grid-cols-2`
- **Required asterisk**: `<span class="text-error-500">*</span>`
- **Disabled state**: `disabled:border-gray-100 disabled:bg-gray-50 disabled:placeholder:text-gray-300 dark:disabled:border-gray-800 dark:disabled:bg-white/[0.03]`

## Common input patterns (quick reference)

### Text / email / password
```html
<div>
  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Label</label>
  <input type="text" placeholder="Placeholder" class="[inputBase]" />
</div>
```

### Password with show/hide
Alpine: `x-data="{ showPassword: false }"` on wrapper. `:type="showPassword ? 'text' : 'password'"` on input. Add `pr-11` for icon padding.

### Select
Same `[inputBase]` class on `<select>`.

### Checkbox
`<input type="checkbox" class="sr-only peer" />` + styled `<span>` sibling using `peer-checked:border-brand-500 peer-checked:bg-brand-500`

### Toggle
`x-data="{ toggle: false }"` on wrapper. Button toggles between `bg-brand-500` and `bg-gray-300 dark:bg-gray-700`. Inner span slides with `translate-x-[1.375rem]` / `translate-x-0.5`.

### Radio
`<input type="radio" class="sr-only peer" />` + styled span using `peer-checked:border-[6px] peer-checked:border-brand-500`

### Textarea
Same `[inputBase]` with `resize-none` added.

## JS-backed patterns

| Pattern | JS file | Notes |
|---------|---------|-------|
| Flatpickr datepicker | `src/js/index.js` | Init on `.datepicker`, range mode, static |
| Dropzone file upload | `src/js/index.js` | Init on `#demo-upload` |
| Clipboard copy | `src/js/index.js` | `#copy-input` / `#copy-button` / `#website-input` |
| Search Cmd+K | `src/js/index.js` | Focus `#search-input` on meta+k or `/` |
| Alpine.js init | `src/js/index.js` | `Alpine.plugin(persist)`, `Alpine.start()` |

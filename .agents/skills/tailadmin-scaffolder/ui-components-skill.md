# TailAdmin UI Components Skill

## When to use
Use this skill when adding UI components to a TailAdmin page — alerts, buttons, badges, avatars, images, videos, media cards.

## Design token reference

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

## Source files reference

### Alerts
| Variant | File |
|---------|------|
| Success | `src/partials/alert/alert-success.html` |
| Warning | `src/partials/alert/alert-warning.html` |
| Error | `src/partials/alert/alert-error.html` |
| Info | `src/partials/alert/alert-info.html` |

**Outer wrapper pattern:** `rounded-xl border border-{TYPE}-500 bg-{TYPE}-50 p-4 dark:border-{TYPE}-500/30 dark:bg-{TYPE}-500/15`

### Buttons
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

### Avatars
| Pattern | File |
|---------|------|
| 7 plain sizes | `src/partials/avatar/avatar-01.html` |
| Online (green dot) | `src/partials/avatar/avatar-02.html` |
| Offline (red dot) | `src/partials/avatar/avatar-03.html` |
| Busy (yellow dot) | `src/partials/avatar/avatar-04.html` |

**Status dot colors:** `bg-success-500` (online), `bg-error-500` (offline), `bg-warning-500` (busy)

**Sizes:** `h-6` through `h-16`, dot scales proportionally (see `src/partials/avatar/avatar-01.html`)

**Avatar group (overlapping):** `flex -space-x-2` with `border-2 border-white dark:border-gray-900` on each circle

### Badges
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

### Images
| Pattern | File |
|---------|------|
| Single image | `src/partials/grid-image/image-01.html` |
| 2-column grid | `src/partials/grid-image/image-02.html` |
| 3-column grid | `src/partials/grid-image/image-03.html` |

### Videos
| Pattern | File |
|---------|------|
| Thumbnail overlay | `src/index.html` (not a partial — see the video cards) |
| YouTube 16:9 | `src/partials/video/video-01.html` |
| YouTube 4:3 | `src/partials/video/video-02.html` |
| YouTube 21:9 | `src/partials/video/video-03.html` |
| YouTube 1:1 | `src/partials/video/video-04.html` |

**Aspect ratios:** `aspect-video` (16:9), `aspect-4/3`, `aspect-21/9`, `aspect-square` (1:1)

### Media cards
| Pattern | File |
|---------|------|
| Full media grid with search + upload | `src/partials/media-card.html` |

**Color map (icon bg):** Image=success, Video=theme-pink, Audio=blue-light, Apps=orange, Docs=warning, Downloads=theme-purple

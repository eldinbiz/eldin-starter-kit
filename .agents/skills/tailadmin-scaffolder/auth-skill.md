# TailAdmin Authentication Pages Skill

## When to use
Use this skill when building authentication pages (sign in, sign up, 404 error, profile) using TailAdmin.

## Key differences from dashboard pages

| Aspect | Dashboard pages | Auth pages |
|--------|----------------|------------|
| Layout | Sidebar + header + main | Two-panel split (form + branding) or shell-based |
| `x-data` on body | Has `page`, `sidebarToggle`, `loaded` | Just `page` + `darkMode` |
| Dark mode toggle | In header | Fixed bottom-right floating button |
| Decorative shapes | Some pages | `common-grid-shape.html` |
| Auth page `page` value | — | `'comingSoon'` (not `'signin'`/`'signup'`) |

## Source files reference

### Sign In
| Pattern | File |
|---------|------|
| Full sign in page (two-panel) | `src/signin.html` |

**Left panel**: form with back link, logo, heading, social buttons, "Or" divider, email/password fields, "Keep me logged in" checkbox, "Forgot password?" link, submit button
**Right panel** (`hidden lg:flex lg:w-1/2`): dark `bg-gray-900` section with `auth-logo.svg` and branding text

### Sign Up
| Pattern | File |
|---------|------|
| Full sign up page (two-panel) | `src/signup.html` |

Same two-panel layout with sign up fields (name, email, password, confirm password, T&C checkbox).

### 404 Error
| Pattern | File |
|---------|------|
| Full 404 page (with sidebar shell) | `src/404.html` |

Uses the **full dashboard shell** (preloader, sidebar, header, overlay) — not standalone. Shows:
- `text-title-2xl font-bold` "ERROR" heading
- Error image (`../images/error/404-dark.svg` / `404-light.svg`)
- "Oops! Page Not Found" message
- "Back to Home Page" button

### Profile
| Pattern | File |
|---------|------|
| Full profile page | `src/profile.html` |
| Profile info edit modal | `src/partials/profile/profile-info-modal.html` |
| Profile address edit modal | `src/partials/profile/profile-address-modal.html` |

**Profile structure:**
- Cover image + avatar overlay
- User info (name, role, location) with social icon links
- Dual Alpine modals: `isProfileInfoModal` and `isProfileAddressModal`

## Auth-specific patterns

### "Back to dashboard" link
```html
<a href="index.html" class="mb-10 inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
  <svg width="20" height="20"><path d="M15.8332 10H4.1665M4.1665 10L9.1665 15M4.1665 10L9.1665 5" stroke="currentColor" stroke-width="1.5"/></svg>
  Back to dashboard
</a>
```

### Social login button
```html
<button class="inline-flex items-center justify-center gap-3 py-3 text-sm font-normal text-gray-700 bg-gray-100 rounded-lg px-7 hover:bg-gray-200 dark:bg-white/5 dark:text-white/90 dark:hover:bg-white/10 w-full">
  <!-- icon --> Sign in with Google
</button>
```

### "Or" divider
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

### Fixed dark mode toggle button
```html
<button @click="darkMode = !darkMode" class="fixed bottom-6 right-6 z-50 flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
  <!-- sun/moon SVG -->
</button>
```

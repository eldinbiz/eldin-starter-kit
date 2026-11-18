---
trigger: model_decision
description: Triggers when creating, editing, or refactoring any .blade.php view file. Enforces server-side Blade comment syntax ({{-- ... --}}) instead of HTML comments (<!-- ... -->).
---

# Blade Commenting Standard

When creating, editing, or refactoring any `.blade.php` view file:
1. ALWAYS use server-side Blade comment syntax (`{{-- comment --}}`) instead of HTML comment syntax (`<!-- comment -->`).
2. Server-side Blade comments prevent HTML DOM comment leakage to client browser DevTools and ensure enclosed Blade expressions are not executed.

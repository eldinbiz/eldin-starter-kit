---
name: php-84
description: "PHP 8.4 strict typing and PER coding style standards. Use when writing or refactoring any PHP code. Covers strict_types, property hooks, asymmetric visibility, constructor property promotion, array_find/array_any/array_all, and PER Coding Style 2.0 formatting conventions."
license: MIT
metadata:
  author: laravel
---

# PHP 8.4 & Strict PSR/PER Standard Rule

This document strictly governs all PHP code generation, updates, and refactoring tasks in this repository.

## 1. Strict Typing Enforcement
- EVERY single PHP file generated or modified MUST begin with strict types explicitly declared:
  ```php
  <?php

  declare(strict_types=1);
  ```
- All function/method arguments, return values, and class properties must have precise type-hinting. Use union types (`string|int`) or intersection types (`Iterator&Countable`) where appropriate. Avoid `mixed` unless entirely unavoidable.

## 2. PHP 8.4 Syntax Paradigm
Prefer modern PHP 8.4+ capabilities over verbose legacy patterns:
- **Property Hooks:** Use property hooks instead of custom getter/setter boilerplate code where applicable.
- **Asymmetric Visibility:** Use asymmetric visibility (e.g., `public private(set) string $name;`) for read-only properties with private mutations.
- **Constructor Property Promotion:** Always use constructor promotion for dependency injection and simple value objects.
- **New Array/String Functions:** Utilize native 8.4 functions like `array_find()`, `array_any()`, and `array_all()` instead of array filter loops.

## 3. Formatting & PSR/PER Standard
- Strictly follow the **PER Coding Style 2.0** (the modern successor to PSR-12).
- Use 4 spaces for indentation.
- Opening braces for classes and methods MUST go on a new line.
- Use camelCase for methods/variables and PascalCase for classes.

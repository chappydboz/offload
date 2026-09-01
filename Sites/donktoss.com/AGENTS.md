# Donk Toss — Agent Guidelines & Rules

## 1. CSS & Styling Rules
* **STRICT RULE: NEVER use `!important` unless strictly unavoidable.**
* Always solve layout, spacing, colors, and positioning by increasing CSS selector specificity (e.g. `body.single-product .element`, `#parent .child`, `.product .donktoss-wrap`) rather than adding `!important`.
* When writing media queries or component overrides, match or slightly increase selector specificity so overrides cascade naturally.

## 2. Public Repository & Child Theme Modifications
* Never modify core theme files directly (`astra`); all customizations belong in the active child theme (`donk-toss`) or configuration overrides.
* Always verify cache invalidation (`wp cache flush`) and local test before pushing to production.

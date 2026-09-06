# Donk Toss — Agent Guidelines & Rules

## 1. Coding & Engineering Rules
All agents and developers follow these collective standards. When establishing new rules, add them here:

### CSS & Styling
- **No `!important`**: NEVER use `!important` unless strictly unavoidable. Always solve layout, spacing, colors, and positioning by increasing CSS selector specificity (e.g. `body.single-product .element`, `#parent .child`, `.product .donktoss-wrap`) rather than adding `!important`.
- When writing media queries or component overrides, match or slightly increase selector specificity so overrides cascade naturally.

### WordPress / Child Themes
- Never modify core theme files directly (`astra`); all customizations belong in the active child theme (`donk-toss`) or configuration overrides.
- Always verify cache invalidation (`wp cache flush`) and local test before pushing to production.

### Public Repository Etiquette
- When working on servers and systems that rely on public Git repositories, always perform a check for the latest Git version, tags, and diffs before making changes.
- Favor configuration overrides or environment variables over modifying upstream code directly.

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

---

## 2. Herdr Multi-Agent Routing, Quota Adaptation & Self-Improvement Policy

### Agent Specialization & Task Affinity Matrix
| Agent | Core Strengths & Ideal Tasks | Token / Cost Profile |
|---|---|---|
| **Antigravity (`agy`)** | Full-stack architecture, complex planning, DB schema & migrations, MCP tool orchestration, Playwright E2E visual QA, SSH deployment, and multi-agent coordination. | Primary coordinator; high context retention capacity. |
| **Claude Code (`claude`)** | Surgical refactoring, focused single-file JS/TS/PHP components, terminal execution pipelines, rapid script generation, and localized fixes. | Fast cloud contributor; use bounded prompts to minimize context bloat. |
| **Local Qwen 2.5 (`qwen`)** | Static code review, CSS rule validation (zero `!important` compliance), PHP syntax linting, schema validation, test-delta analysis, and log sanitization. | **0 Cloud Tokens (Free)** — Runs locally on Apple Silicon M1 (`http://localhost:11434`). Always prioritize for review & linting. |

### Quota Conservation & Dynamic Auto-Failover Rules
1. **Zero-Token Offloading Priority**: Always offload repetitive validation, linter checks, and style adherence reviews to Local Qwen 2.5 before invoking cloud models.
2. **Quota & Rate-Limit Auto-Adaptation**:
   - If **Claude Code** is throttled, over quota, or rate-limited: Antigravity automatically absorbs contributor tasks or decomposes them into direct execution steps without blocking the user.
   - If **Antigravity** context/quota is constrained: delegate self-contained component implementations to Claude Code and verification to Local Qwen.
   - If both cloud agents are constrained: fall back to Local Qwen for draft generation and execute commands via sandboxed bash/CLI.
3. **Continuous Learning & Session Review**:
   - At the conclusion of coordinated tasks, evaluate routing effectiveness, token economy, and failure/retry frequency.
   - Record architectural learnings and adapt task delegation patterns across subsequent sessions.

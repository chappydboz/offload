---
name: beehive-show-data-standards
description: Enforces standards for Beehive show records, crew matching against the Personnel Spreadsheet, HTML-to-Markdown cleaning, and zero-fake-data policies.
---

# Beehive Show Data Standards & Metadata Guidelines

This document outlines the strict guidelines and rules for managing, enriching, and updating Purple Bee Show Records in the Beehive Knowledge Graph.

---

## 🚫 1. Absolute Zero-Fake-Data Policy

- **No Boilerplate / Template Crew Tables**: Never inject static template crew tables, default producer/booking lists, or assumed personnel into show records.
- **Unverified Shows = Zero Crew Listed**: If a show record does not have verified, show-specific crew documentation, leave the crew list section completely empty (`zero crew listed`). Inaccurate or masquerading placeholder data is strictly prohibited.

---

## 📅 2. Sourcing Crew from the Personnel Spreadsheet

- **Canonical Document**: The official Google Drive **Purple Bee Personnel Spreadsheet** ([1qHei6uXfQnEn4YfF8JbnY-N-58CcLmNsfWAtzqOr96o](https://docs.google.com/spreadsheets/d/1qHei6uXfQnEn4YfF8JbnY-N-58CcLmNsfWAtzqOr96o/edit)).
- **Schedule Tab (`SheetId: 1`)**: Contains individual per-show production schedule rows tracking exact roles starting **September 6, 2023** (`45175.0` Excel serial date).
- **Purple Bee Crew List Tab (`SheetId: 2`)**: Contains the verified 61-person staff roster for matching names, roles, and handles.

---

## 🎯 3. Strict Date AND Title Matching (Multi-Show Event Days)

- **Multi-Show Event Days**: On days with multiple concurrent or back-to-back shows (e.g. SXSW showcase days with main stage, satellite stage, and featured sessions), the Schedule spreadsheet contains multiple distinct rows for the same date.
- **Dual Matching Rule**: When linking a Schedule row to a Beehive show record, you MUST verify **BOTH**:
  1. **Exact Date Match** (`YYYY-MM-DD`).
  2. **High Title & Stage Similarity** (Minimum 50% title match score comparing artist/show/stage name).
- **Never Match by Date Alone**: Date-only matching causes wrong crew rosters to be attached to adjacent or multi-stage shows on the same day.

---

## 📝 4. Clean Markdown Prose Standard

- **Zero Raw HTML**: Show description text must be clean, human-readable Markdown (no `<p>`, `<div>`, `<a href>`, or Draft.js attributes like `data-block="true"`).
- **Decoded HTML Entities**: Titles and descriptions must have all HTML entities cleaned (`&#038;` $\rightarrow$ `&`, `&amp;` $\rightarrow$ `&`, `&nbsp;` $\rightarrow$ ` `, `&rsquo;` $\rightarrow$ `'`).
- **Standard Header Structure**:
  ```markdown
  ## Show: [Title]

  **Date**: YYYY-MM-DD
  **Venue**: [Verified Venue Name]
  **YouTube**: [Stream URL]

  ### Show Description
  [Clean Markdown Copy]

  ### Crew List (Only when verified)
  | Name | Role |
  |---|---|
  | [Name] | [Role] |

  ### Key Links
  * **Master Show Doc**: [URL]
  ```

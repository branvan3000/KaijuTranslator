# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.1.0] - 2025-12-25 (Visual Intelligence Update)

### Added

- **5-Column Intelligence Grid**: Re-architected the main dashboard to follow a logical flow: Source -> Targets -> Provider -> Volume -> Production.
- **Provider Dependency Locking**: The "Volume" and "Production" columns are now hidden until a valid Neural Provider API Key is configured.
- **Real-Time Volume Projection**: Added a "Calculate" action that performs a recursive server-side scan of the directory structure to estimate the total build size (`Source Files * Target Languages`).
- **Visual Widget Customizer**:
  - **Instant Preview**: Switching themes (Glass, Minimal, Kaiju, Bubble) now updates the preview immediately without page reload.
  - **Content Modes**: Added support for displaying "Flags Only", "Text Only", or "Both".
  - **Persistence**: Visual settings are now saved to `kaiju-config.php` and applied to the production `widget.php`.
- **SVG Flag Integration**: Replaced emoji flags with high-quality SVG icons via `flagcdn.com` for consistent cross-platform rendering.

### Changed

- **Target Distribution Grid**: Now displays the full recursive folder structure for each target language, collapsed by default for better readability.
- **Navigation Flow**: "Translation Scope" card now links to the Language Matrix configuration.
- **Matrix Synchronization**: Saving language settings now correctly recalculates constraints and redirects to the Overview dashboard.

### Fixed

- **Floating Bubble Widget**: Resolved rendering issues where the "Bubble" style was not displaying correctly or functioning as a dropdown trigger.
- **CSS Margins**: Increased grid gaps and spacing in the AI Orchestration dashboard for a more professional, "breathing" layout.

## [1.0.0] - 2025-12-24 (Initial Beta)

### Added

- Core Translation Engine (OpenAI Integration).
- Recursive File Analyzer & Sitemap Generator.
- Basic "Glass" Widget.
- Language Matrix with 130+ ISO Codes.

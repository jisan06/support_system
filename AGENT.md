# Agent Instructions & Project Rules

## Execution Guidelines
1. **Auto-Submit / Do Not Wait for User Input:**
   - Do not stop or wait for unnecessary user input or prompt confirmation before proceeding.
   - Proceed directly with implementation and auto-submit solutions.

2. **Terminal Command & Testing Restrictions:**
   - **Do NOT run any terminal commands or test commands** (e.g., `php artisan test`, automated test runners, build scripts, etc.) autonomously.
   - Only execute terminal commands when the user explicitly provides the command details and requests it to be run.

## Project Context & Configurations
- **Framework:** Laravel 12 running on Laragon (Windows)
- **Installation Bypass:**
  - `APP_INSTALLED=true` in `.env` bypasses the installer entirely without needing `storage/installed`.
  - `APP_INSTALLED=false` enables the web installation wizard.
- **Local Environment Defaults:**
  - `APP_ENV=local`
  - `APP_DEBUG=true`
  - `CACHE_STORE=file`
  - `SESSION_DRIVER=file`


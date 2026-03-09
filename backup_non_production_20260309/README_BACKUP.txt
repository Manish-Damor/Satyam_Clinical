Backup Bundle: non-production files moved on 2026-03-09

Purpose:
- Keep runtime project clean for deployment.
- Preserve old/debug/test files for rollback/reference.

Contents:
- Root folders moved: tests, diagnose, dnr, Checking Files, Debug and requirements, verification and test files, dbFile, scripts
- Root files moved: copy/old/backup and maintenance scripts
- php_action files moved: copy/old/test/debug/migration utility scripts

Restore process (if needed):
1. Copy files/folders back from this backup folder to project root.
2. Keep folder names exactly same while restoring.

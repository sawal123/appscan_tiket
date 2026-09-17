# Workflow Preferences

- Requires automated tests with Pest for new work, covering authorization (guests/scanner/admin), CRUD, validation, and edge cases. Confidence: 0.9
- Validation workflow order: run migration, then targeted/new tests, then regression tests, then the full `php artisan test` suite. Confidence: 0.9
- Runs Pint on changed PHP files as part of finishing. Confidence: 0.85
- Runs `npm run build` only when frontend assets actually changed. Confidence: 0.85
- Stays strictly within the stated task scope; do not audit or refactor unrelated areas, and don't touch already-PASSING auth/role architecture or finished Dashboard design. Confidence: 0.9
- Dislikes redundant file reads — do not read the same file more than twice; reuse existing context. Confidence: 0.7
- When tests/migrations fail, reads only the stack trace and relevant error lines rather than the full output. Confidence: 0.7

# Workflow Preferences

- Requires automated tests with Pest for new work, covering authorization (guests/scanner/admin), CRUD, validation, and edge cases. Confidence: 0.9
- Validation workflow order: run migration, then targeted/new tests, then regression tests, then the full `php artisan test` suite. Confidence: 0.9
- Runs Pint on changed PHP files as part of finishing. Confidence: 0.85
- Runs `npm run build` only when frontend assets actually changed. Confidence: 0.85
- Stays strictly within the stated task scope; do not audit or refactor unrelated areas, and don't touch already-PASSING auth/role architecture or finished Dashboard design. Confidence: 0.9
- Dislikes redundant file reads — do not read the same file more than twice; reuse existing context. Confidence: 0.7
- When tests/migrations fail, reads only the stack trace and relevant error lines rather than the full output. Confidence: 0.7
- Keeps Laravel Vite as the asset bundler; registers new CSS/JS entry points as Vite inputs and builds them with `npm run build`. Confidence: 0.75
- Keeps reference/prototype source folders (e.g. `ui_app/`) in place during a migration/slice and only considers deleting them after everything works. Confidence: 0.7
- Verifies a UI slice against the prototype before finishing — e.g. diffing the prototype's CSS classes against the Blade output, and checking the production bundle size. Confidence: 0.65
- Does feature work on a dedicated Git branch named with a `feat/` prefix plus a short lowercase description (e.g. `feat/slicing_app_ui`, `feat/checkin`), commits there rather than to `main`. Confidence: 0.6
- When starting feature work, expects the branch to come off an up-to-date `main`: switch to `main`, `git pull`, then create the feature branch from it, then push with upstream tracking. Confidence: 0.6

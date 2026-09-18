# Workflow Preferences

- Requires automated tests with Pest for new work, covering authorization (guests/scanner/admin), CRUD, validation, and edge cases. Confidence: 0.9
- Validation workflow order: run migration, then targeted/new tests, then regression tests, then the full `php artisan test` suite. Confidence: 0.9
- Runs Pint on changed PHP files as part of finishing. Confidence: 0.85
- Runs `npm run build` only when frontend assets actually changed. Confidence: 0.85
- Stays strictly within the stated task scope; do not audit or refactor unrelated areas, and don't touch already-PASSING auth/role architecture or finished Dashboard design. Confidence: 0.9
- Dislikes redundant file reads — do not read the same file more than twice; reuse existing context. Confidence: 0.7
- When tests/migrations fail, reads only the stack trace and relevant error lines rather than the full output. Confidence: 0.7
- Names feature branches with a `pr{N}/` prefix keyed to the upcoming PR number plus a short lowercase hyphenated description (e.g. `pr8/scanner-operation-ux`), an alternative to the `feat/` prefix. Confidence: 0.65
- Keeps Laravel Vite as the asset bundler; registers new CSS/JS entry points as Vite inputs and builds them with `npm run build`. Confidence: 0.75
- Keeps reference/prototype source folders (e.g. `ui_app/`) in place during a migration/slice and only considers deleting them after everything works. Confidence: 0.7
- Verifies a UI slice against the prototype before finishing — e.g. diffing the prototype's CSS classes against the Blade output, and checking the production bundle size. Confidence: 0.65
- Does feature work on a dedicated Git branch named with a `feat/` prefix plus a short lowercase description (e.g. `feat/slicing_app_ui`, `feat/checkin`, `feat/dashboard`), commits there rather than to `main`. Confidence: 0.75
- When starting feature work (or after finishing a slice), expects the branch to come off an up-to-date `main`: switch to `main`, `git pull`, then create the feature branch from it, then push with upstream tracking. Confidence: 0.8
- Prefers fast-forward-only pulls (e.g. `git pull --ff-only origin main`) to keep history linear and avoid unintended merge commits. Confidence: 0.7
- Runs destructive database commands (e.g. `migrate:fresh --seed`) against a temporary throwaway SQLite database instead of the dev database, so existing data isn't wiped. Confidence: 0.65
- Expects runtime/client-side behavior (e.g. a loading spinner that appears while a request is in flight) to be proven in a real browser against the running app with actual requests — observing the DOM/classes during the request and a screenshot — not just via rendered-HTML or attribute assertions in Pest. Confidence: 0.6
- Cleans up the throwaway scaffolding used for such verification before finishing: remove temporary debug tests/scripts, stop the local dev server, close the browser session, and delete the scratch database. Confidence: 0.55
- Verifies seeders end-to-end by inspecting the resulting data (row counts, cross-table consistency, no duplicates) and re-running the seed to confirm idempotency, not just trusting a clean exit. Confidence: 0.6

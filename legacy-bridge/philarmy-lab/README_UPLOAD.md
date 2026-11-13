# philarmy-lab.com Upload Instructions (Real-Time Bridge + Export API)

Upload **3 files** and add **1 line** in `config.php` inside `public_html/` on `philarmy-lab.com`:

1. Upload `api_sync_export.php` -> `public_html/api_sync_export.php`
2. Upload `sync_hook.php` -> `public_html/sync_hook.php`
3. Upload `sync_config.php` (created from `sync_config.php.example`) -> `public_html/sync_config.php` with the shared `secret` matching `PHILARMYLAB_LEGACY_SECRET` in `rpm-is.com`'s `.env`.
4. Edit `public_html/config.php` and add this single line right before `?>` (after `$conn->set_charset("utf8mb4");`):
   ```php
   include_once __DIR__ . '/sync_hook.php';
   ```

## Run Initial Backfill + Scheduled Catch-Up on `rpm-is.com`

1. In `rpm-is.com`'s `.env`, set:
   ```dotenv
   PHILARMYLAB_LEGACY_URL=https://philarmy-lab.com
   PHILARMYLAB_LEGACY_ENDPOINT=/api_sync_export.php
   PHILARMYLAB_LEGACY_SECRET=<same-secret-as-sync_config.php>
   ```
2. Run initial full API sync on `rpm-is.com`:
   ```bash
   php artisan philarmylab:sync --source=api
   ```
3. Ensure Laravel scheduler cron runs once per minute (`* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`) as an automatic catch-up safety net alongside the instant webhook push.

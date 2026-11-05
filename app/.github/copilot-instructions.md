PHP 8+, MySQL via PDO prepared statements, plain HTML/CSS/JS only. No frameworks, no
Composer, no CDNs. Query-string routing (index.php?r=module/action), no mod_rewrite.
DB runs on port 3307 via .env (working). Files under 150 lines, commented, simple enough
for students to explain in a viva. Views have no SQL and escape all output. CSRF on every
POST, POST-only for create/update/delete, post-redirect-get, client-side AND server-side
validation, generic user errors with details logged to storage/logs. Money is never a
float: use integer paise in PHP. Paid amount, pending balance and status are DERIVED from
the payments table, never stored. Reuse the existing layout, Company module and
Core classes as the pattern. Always run C:\xampp\php\php.exe -l on changed files and
test with curl.exe. Mark anything you could not run as "NOT VERIFIED". Finish every
task with: files changed, URLs to test, a manual test checklist, 5 viva questions with
short answers. Do not touch other modules.
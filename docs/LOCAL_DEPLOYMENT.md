
## Restoring an encrypted backup

Stop the web server and queue worker before restoring. List the backup record ID in the Security & Backup screen, then run:

```bash
php artisan school:backup:restore BACKUP_ID
```

The command verifies the SHA-256 checksum, decrypts the archive, validates the SQLite header, asks for confirmation, creates a timestamped pre-restore safety copy, and atomically replaces the database. Restart services and run a health check afterward. Use `--force` only in an already-confirmed automated recovery procedure.

# Performance and Capacity

The local deployment is designed for approximately 500–1,500 students and normal day-school staff concurrency. Operational indexes cover school/status scopes, attendance dates, enrollment registers, fee due queues, payment dates, WhatsApp dispatch, audit history, journals, complaints, leave and timetable lookups.

Use the database queue and keep the scheduler running. Connect the host by Gigabit Ethernet. Keep at least 20% SSD space free. Run encrypted backups outside teaching hours. Do not store the SQLite database in OneDrive/Dropbox or on a network share; sync only encrypted backup files.

For performance troubleshooting run `php artisan school:health`, inspect queue failures, check free disk space and verify that only one scheduler instance is active. A future move beyond the stated scale can replace SQLite with PostgreSQL without changing domain services.

# Security Baseline

- Least-privilege RBAC, resource policies and maker/checker approvals.
- Adaptive password hashing, login throttling and session rotation.
- CSRF protection, escaped output, strict validation and safe uploads.
- Encrypted secrets/session data; credentials never committed or logged.
- Append-only audit records for authentication, marks, attendance, fees, accounts, payroll and access changes.
- Encrypted backups with retention and tested restoration.
- Host firewall, private static address and locally trusted HTTPS where possible.
- Automated tests, static analysis and dependency scanning gate releases.

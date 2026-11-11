# Security Policy

## Reporting

Report suspected vulnerabilities privately to the repository owner. Do not open a public issue containing credentials, student data, database copies, connector sessions or exploit details.

## Secrets

Never commit `.env`, `APP_KEY`, GitHub tokens, WhatsApp connector tokens, QR session data, database files or decrypted backups. Any secret pasted into chat, logs or an issue must be revoked and replaced immediately.

## Supported deployment

The initial supported profile is one school host on a trusted LAN, with `APP_ENV=production`, `APP_DEBUG=false`, database-backed sessions and queues, restricted firewall rules, unique staff accounts and automated encrypted backups.

## WhatsApp warning

The QR connector uses unofficial WhatsApp Web automation. It is replaceable but carries disconnection, compatibility and account-restriction risks. Use a dedicated school number, obtain consent, respect opt-outs and avoid aggressive bulk sending.

## Data protection

Student, guardian, financial, attendance, payroll and complaint information is sensitive. Limit permissions by job role, review audit exports, suspend departed staff immediately, and retain backups according to school policy and applicable Pakistani law.

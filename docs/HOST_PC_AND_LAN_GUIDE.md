# Host PC and School LAN Guide

## Recommended host for 500–1,500 students

- Modern 6-core Intel Core i5 / AMD Ryzen 5 or better
- 16 GB RAM minimum; 32 GB preferred if the PC has other duties
- 512 GB NVMe SSD minimum, with a separate external backup drive
- Gigabit Ethernet to the main router/switch; do not rely on Wi-Fi for the server
- UPS sized for the PC, router and switch
- Windows 11 Pro or a supported Ubuntu LTS release
- Dedicated school WhatsApp number and Android/iPhone available for QR relinking

## LAN controls

Assign the host a reserved/static private IP. Allow TCP 8000 only on the trusted school LAN. Do not port-forward the ERP or WhatsApp connector to the internet. Keep connector port 3210 host-local whenever possible. Use strong unique staff passwords and separate Windows accounts.

## Required production settings

Use `APP_ENV=production`, `APP_DEBUG=false`, an absolute SQLite path, database queue/session drivers, and a random connector token of at least 32 characters. Run:

```bash
php artisan school:deployment-check
php artisan migrate --force
php artisan school:health
```

The Windows scripts under `scripts/windows` start the web server, queue worker, scheduler and WhatsApp connector. Review paths and test backup restoration before registering the startup task.

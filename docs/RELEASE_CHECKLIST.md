# Release Checklist

- [ ] All automated tests and formatting checks pass.
- [ ] Composer advisory audit has no unresolved production vulnerability.
- [ ] Secret-pattern scan passes and `.env`, databases and WhatsApp sessions are untracked.
- [ ] Demo passwords are replaced for production.
- [ ] `school:deployment-check` passes on the final host.
- [ ] Principal completes the user-acceptance plan.
- [ ] Dedicated WhatsApp number is linked and real delivery/relinking is tested.
- [ ] Receipt, result card, certificate and payslip print correctly on the school printer.
- [ ] Encrypted backup is copied off-host and a restore rehearsal succeeds.
- [ ] LAN firewall and static IP are verified; no internet port forwarding exists.
- [ ] Screenshots contain demo data only and expose no student, credential or QR-session data.
- [ ] Version tag, changelog and final license are reviewed before GitHub release.

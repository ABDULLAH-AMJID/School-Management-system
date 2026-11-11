# Local WhatsApp QR Connector

Unofficial, replaceable WhatsApp Web sidecar. It requires internet even when the ERP runs on the LAN. Personal-account automation can disconnect, break after WhatsApp changes, or lead to temporary/permanent number restriction. Use a dedicated school number and obtain guardian consent.

Copy `.env.example`, install Node.js 20+, run `npm install`, then `npm start`. Keep `session/` private and backed up securely. Never expose port 3210 outside the trusted host/LAN. Configure Laravel `WHATSAPP_CONNECTOR_URL` and the same strong token.

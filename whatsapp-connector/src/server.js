'use strict';

const crypto = require('crypto');
const express = require('express');
const QRCode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

const port = Number(process.env.PORT || 3210);
const token = process.env.CONNECTOR_TOKEN;
const maxMessagesPerMinute = Number(process.env.MAX_MESSAGES_PER_MINUTE || 30);
const maxIdempotencyEntries = Number(process.env.MAX_IDEMPOTENCY_ENTRIES || 5000);

if (!token || token.length < 32) {
  console.error('CONNECTOR_TOKEN of at least 32 characters is required');
  process.exit(1);
}

const app = express();
app.disable('x-powered-by');
app.use(express.json({ limit: '64kb' }));

let state = 'starting';
let qr = null;
let qrGeneratedAt = null;
let lastError = null;
let account = null;
const sent = new Map();
const sendTimestamps = [];

function authorized(req, res, next) {
  const value = (req.headers.authorization || '').replace(/^Bearer\s+/, '');
  const supplied = Buffer.from(value);
  const expected = Buffer.from(token);
  if (supplied.length !== expected.length || !crypto.timingSafeEqual(supplied, expected)) {
    return res.status(401).json({ error: 'unauthorized' });
  }
  next();
}

function sendingAllowed() {
  const cutoff = Date.now() - 60_000;
  while (sendTimestamps.length && sendTimestamps[0] < cutoff) sendTimestamps.shift();
  if (sendTimestamps.length >= maxMessagesPerMinute) return false;
  sendTimestamps.push(Date.now());
  return true;
}

function remember(key, messageId) {
  sent.set(key, messageId);
  while (sent.size > maxIdempotencyEntries) sent.delete(sent.keys().next().value);
}

const client = new Client({
  authStrategy: new LocalAuth({ dataPath: process.env.SESSION_PATH || './session' }),
  puppeteer: { headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] },
});

client.on('qr', async value => {
  state = 'qr_required';
  qr = await QRCode.toDataURL(value);
  qrGeneratedAt = Date.now();
  lastError = null;
});
client.on('authenticated', () => { state = 'authenticated'; qr = null; qrGeneratedAt = null; });
client.on('ready', () => { state = 'connected'; account = client.info?.wid?.user || null; qr = null; qrGeneratedAt = null; lastError = null; });
client.on('auth_failure', message => { state = 'auth_failure'; lastError = String(message); qr = null; });
client.on('disconnected', reason => { state = 'disconnected'; lastError = String(reason); account = null; qr = null; });

app.get('/api/health', authorized, (req, res) => res.json({
  connected: state === 'connected', state, account, last_error: lastError,
  risk: 'Unofficial WhatsApp Web automation may disconnect or cause number restriction.',
}));

app.get('/api/qr', authorized, (req, res) => {
  const age = qrGeneratedAt ? Math.floor((Date.now() - qrGeneratedAt) / 1000) : null;
  const valid = qr && age !== null && age < 60;
  res.json({ state, qr: valid ? qr : null, expires_in: valid ? 60 - age : null });
});

app.post('/api/messages', authorized, async (req, res) => {
  try {
    const { to, text, idempotency_key: key } = req.body || {};
    if (state !== 'connected') return res.status(503).json({ error: 'whatsapp_not_connected' });
    if (!/^\+[1-9]\d{7,14}$/.test(to || '') || typeof text !== 'string' || !text.trim() || text.length > 4000 || !key) {
      return res.status(422).json({ error: 'invalid_message' });
    }
    if (sent.has(key)) return res.json({ message_id: sent.get(key), duplicate: true });
    if (!sendingAllowed()) return res.status(429).json({ error: 'rate_limit_exceeded', retry_after: 60 });
    const chatId = `${to.slice(1)}@c.us`;
    if (!await client.isRegisteredUser(chatId)) return res.status(422).json({ error: 'recipient_not_registered' });
    const result = await client.sendMessage(chatId, text);
    remember(key, result.id._serialized);
    return res.status(201).json({ message_id: result.id._serialized });
  } catch (error) {
    lastError = error.message;
    return res.status(500).json({ error: 'send_failed' });
  }
});

app.post('/api/logout', authorized, async (req, res) => {
  try {
    await client.logout(); state = 'logged_out'; qr = null; account = null;
    return res.json({ ok: true });
  } catch (error) {
    lastError = error.message;
    return res.status(500).json({ error: 'logout_failed' });
  }
});

app.use((error, req, res, next) => {
  if (error?.type === 'entity.parse.failed') return res.status(400).json({ error: 'invalid_json' });
  return next(error);
});

app.listen(port, '0.0.0.0', () => {
  console.log(`WhatsApp connector listening on ${port}`);
  client.initialize().catch(error => { state = 'failed'; lastError = error.message; });
});

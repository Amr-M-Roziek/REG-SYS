const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');
const bodyParser = require('body-parser');
const puppeteer = require('puppeteer');

const app = express();
const port = process.env.PORT || 3000;

let puppeteerConfig = {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
};

if (process.platform === 'win32') {
    puppeteerConfig.executablePath = 'C:\\Users\\Amr\\.cache\\puppeteer\\chrome\\win64-127.0.6533.88\\chrome-win64\\chrome.exe';
}

app.use(cors());
app.use(bodyParser.json());

let qrCodeData = null;
let clientStatus = 'INITIALIZING';
let clientReady = false;

const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: puppeteerConfig
});

client.on('qr', (qr) => {
    console.log('QR RECEIVED');
    qrcode.toDataURL(qr, (err, url) => {
        qrCodeData = url;
        clientStatus = 'WAITING_FOR_SCAN';
    });
});

client.on('ready', () => {
    console.log('Client is ready!');
    clientStatus = 'READY';
    clientReady = true;
    qrCodeData = null;
});

client.on('authenticated', () => {
    console.log('AUTHENTICATED');
    clientStatus = 'AUTHENTICATED';
    qrCodeData = null;
});

client.on('auth_failure', msg => {
    console.error('AUTHENTICATION FAILURE', msg);
    clientStatus = 'AUTH_FAILURE';
});

client.on('disconnected', (reason) => {
    console.log('Client was logged out', reason);
    clientStatus = 'DISCONNECTED';
    clientReady = false;
    client.initialize();
});

client.initialize();

app.get('/status', (req, res) => {
    res.json({
        status: clientStatus,
        ready: clientReady,
        qr: qrCodeData
    });
});

app.post('/send', async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ status: 'error', message: 'Client not ready' });
    }

    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ status: 'error', message: 'Phone and message required' });
    }

    try {
        let cleanPhone = phone.replace(/[^0-9]/g, '');
        const chatId = `${cleanPhone}@c.us`;
        const response = await client.sendMessage(chatId, message);
        res.json({ status: 'success', id: response.id._serialized });
    } catch (error) {
        console.error('Send Error:', error);
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.post('/send-pdf', async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ status: 'error', message: 'Client not ready' });
    }

    const { phone, message, pdf_url } = req.body;

    if (!phone || !pdf_url) {
        return res.status(400).json({ status: 'error', message: 'Phone and pdf_url required' });
    }

    let browser = null;
    try {
        let cleanPhone = phone.replace(/[^0-9]/g, '');
        const chatId = `${cleanPhone}@c.us`;

        browser = await puppeteer.launch(puppeteerConfig);
        const page = await browser.newPage();

        await page.setViewport({ width: 1123, height: 794 });
        await page.goto(pdf_url, { waitUntil: 'networkidle0' });
        await page.addStyleTag({ content: '.toolbar { display: none !important; } body { background: white !important; }' });

        const pdfBuffer = await page.pdf({
            format: 'A4',
            landscape: true,
            printBackground: true
        });

        await browser.close();
        browser = null;

        const media = new MessageMedia('application/pdf', pdfBuffer.toString('base64'), 'Certificate.pdf');
        const response = await client.sendMessage(chatId, media, { caption: message });
        res.json({ status: 'success', id: response.id._serialized });
    } catch (error) {
        console.error('Send PDF Error:', error);
        if (browser) await browser.close();
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.post('/logout', async (req, res) => {
    try {
        await client.logout();
        res.json({ status: 'success', message: 'Logged out' });
    } catch (error) {
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.listen(port, () => {
    console.log(`WhatsApp Web Backend listening at http://localhost:${port}`);
});


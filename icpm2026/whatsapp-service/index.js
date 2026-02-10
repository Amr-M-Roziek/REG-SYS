const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');
const bodyParser = require('body-parser');
const puppeteer = require('puppeteer-core');

const app = express();
const port = process.env.PORT || 3000;

// Chrome path configuration
let puppeteerConfig = {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
};

// Only use hardcoded path on local Windows environment if needed
if (process.platform === 'win32') {
    puppeteerConfig.executablePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
}

app.use(cors());
app.use(bodyParser.json());

let qrCodeData = null;
let clientStatus = 'INITIALIZING'; // INITIALIZING, WAITING_FOR_SCAN, READY, AUTHENTICATED, DISCONNECTED
let clientReady = false;

// Initialize WhatsApp Client
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
    client.initialize(); // Auto restart
});

client.initialize();

// API Endpoints

// Get Status
app.get('/status', (req, res) => {
    res.json({
        status: clientStatus,
        ready: clientReady,
        qr: qrCodeData
    });
});

// Send Message
app.post('/send', async (req, res) => {
    if (!clientReady) {
        return res.status(503).json({ status: 'error', message: 'Client not ready' });
    }

    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ status: 'error', message: 'Phone and message required' });
    }

    try {
        // Clean phone number (remove non-digits)
        let cleanPhone = phone.replace(/[^0-9]/g, '');
        
        // Ensure it has a country code (simplistic check, assuming input is mostly correct)
        // Ideally PHP backend validates this
        
        const chatId = `${cleanPhone}@c.us`;
        
        const response = await client.sendMessage(chatId, message);
        res.json({ status: 'success', id: response.id._serialized });
    } catch (error) {
        console.error('Send Error:', error);
        res.status(500).json({ status: 'error', message: error.message });
    }
});

// Send PDF
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
        // Clean phone number (remove non-digits)
        let cleanPhone = phone.replace(/[^0-9]/g, '');
        const chatId = `${cleanPhone}@c.us`;

        // Generate PDF using Puppeteer
        browser = await puppeteer.launch(puppeteerConfig);
        const page = await browser.newPage();
        
        // Set viewport to approximate A4 landscape
        await page.setViewport({ width: 1123, height: 794 });
        
        await page.goto(pdf_url, { waitUntil: 'networkidle0' });

        // Hide toolbar and ensure proper background
        await page.addStyleTag({ content: '.toolbar { display: none !important; } body { background: white !important; }' });

        const pdfBuffer = await page.pdf({
            format: 'A4',
            landscape: true,
            printBackground: true
        });

        await browser.close();
        browser = null;

        // Create MessageMedia
        const media = new MessageMedia('application/pdf', pdfBuffer.toString('base64'), 'Certificate.pdf');

        // Send
        const response = await client.sendMessage(chatId, media, { caption: message });
        res.json({ status: 'success', id: response.id._serialized });
    } catch (error) {
        console.error('Send PDF Error:', error);
        if (browser) await browser.close();
        res.status(500).json({ status: 'error', message: error.message });
    }
});

// Logout
app.post('/logout', async (req, res) => {
    try {
        await client.logout();
        res.json({ status: 'success', message: 'Logged out' });
    } catch (error) {
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.listen(port, '0.0.0.0', () => {
    console.log(`WhatsApp Service listening at http://0.0.0.0:${port}`);
});

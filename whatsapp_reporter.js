const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const pino = require('pino');
const qrcode = require('qrcode-terminal');
const mysql = require('mysql2/promise');
const cron = require('node-cron');
const puppeteer = require('puppeteer');

// Database configuration
const dbConfig = {
    host: '127.0.0.1', // Or the Render MySQL host
    user: 'root',
    password: '',
    database: 'manufacturing_erp'
};

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('baileys_auth_info');

    const sock = makeWASocket({
        auth: state,
        logger: pino({ level: 'silent' }), // Suppress excessive logs
        printQRInTerminal: false // We will handle it manually using qrcode-terminal
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('Scan the QR code below to link WhatsApp:');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            const shouldReconnect = (lastDisconnect.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('Connection closed due to ', lastDisconnect.error, ', reconnecting ', shouldReconnect);
            if (shouldReconnect) {
                connectToWhatsApp();
            }
        } else if (connection === 'open') {
            console.log('WhatsApp connected successfully!');
            
            // Send connection success message to the connected user's own number
            const userJid = sock.user.id.split(':')[0] + '@s.whatsapp.net';
            await sock.sendMessage(userJid, { 
                text: '✅ *Manufacturing ERP*\nWhatsApp integration has been connected successfully! You will now receive automated reports here.' 
            });

            // Start the reporting cron job
            startReportingCron(sock, userJid);
        }
    });
}

function startReportingCron(sock, targetJid) {
    console.log('Cron scheduler started for reporting...');
    
    // Schedule for 9:00 PM every day
    cron.schedule('0 21 * * *', async () => {
        console.log('Generating daily report...');
        try {
            const connection = await mysql.createConnection(dbConfig);
            let reportText = `📊 *Daily Operations Report* - ${new Date().toLocaleDateString()}\n\n`;

            // 1. Daily Urgent Log
            const [urgentOrders] = await connection.execute(`
                SELECT p.id, c.client_name, pr.item_code, pi.boxes, pi.pieces 
                FROM purchase_orders p 
                JOIN clients c ON p.client_id = c.id
                JOIN po_items pi ON p.id = pi.po_id
                JOIN products pr ON pi.product_id = pr.id
                WHERE p.is_urgent = 1 AND p.status = 'Pending'
            `);

            reportText += `🚨 *Daily Urgent Log*\n`;
            if (urgentOrders.length > 0) {
                urgentOrders.forEach(o => {
                    reportText += `PO #${o.id} | ${o.client_name}\nItem: ${o.item_code}\nQty: ${o.boxes} Boxes, ${o.pieces} Pieces\n---\n`;
                });
            } else {
                reportText += `No urgent orders pending.\n---\n`;
            }

            // 2. Production Demands
            const [demands] = await connection.execute(`
                SELECT department_name, item_code, item_name, SUM(quantity_required) as total
                FROM department_queues 
                WHERE status = 'Pending' 
                GROUP BY department_name, item_code, item_name
            `);

            reportText += `\n⚙️ *Production Demands*\n`;
            if (demands.length > 0) {
                demands.forEach(d => {
                    reportText += `${d.department_name}\n${d.item_code} (${d.item_name}): ${d.total} needed\n\n`;
                });
            } else {
                reportText += `No raw materials pending.\n\n`;
            }

            // Send via WhatsApp
            await sock.sendMessage(targetJid, { text: reportText });
            console.log('Daily text report sent via WhatsApp!');

            await connection.end();

            // Demonstration of Puppeteer usage as requested
            await captureAndSendDashboard(sock, targetJid);

        } catch (error) {
            console.error('Error generating report:', error);
            await sock.sendMessage(targetJid, { text: `⚠️ Error generating daily report: ${error.message}` });
        }
    });
}

async function captureAndSendDashboard(sock, targetJid) {
    try {
        console.log('Launching Puppeteer to capture dashboard screenshot...');
        // In render, you must configure Puppeteer to run without a sandbox
        const browser = await puppeteer.launch({ args: ['--no-sandbox', '--disable-setuid-sandbox'] });
        const page = await browser.newPage();
        
        // Since we are running on Render, the PHP server would typically be on a local port or URL
        // Example: await page.goto('http://localhost:80'); 
        // We will just create a placeholder HTML to demonstrate since PHP might not be running on the same port in development
        await page.setContent('<h1>Manufacturing ERP Snapshot Placeholder</h1><p>Running on Render.</p>');
        
        const screenshot = await page.screenshot({ encoding: 'base64' });
        await browser.close();

        const buffer = Buffer.from(screenshot, 'base64');
        await sock.sendMessage(targetJid, {
            image: buffer,
            caption: '📸 *Dashboard Visual Snapshot*'
        });
        console.log('Visual snapshot sent via WhatsApp!');
    } catch (err) {
        console.error('Puppeteer error:', err);
    }
}

connectToWhatsApp();

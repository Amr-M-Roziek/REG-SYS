# WhatsApp Bulk Messaging Setup Guide

## Overview
This feature allows administrators to send bulk WhatsApp messages (e.g., certificate links) to participants using a linked WhatsApp account. It uses a local Node.js service to bridge the PHP application with the WhatsApp network.

## Prerequisites
1.  **Node.js**: Must be installed on the server (v18+ recommended).
    -   Verify with `node -v` in terminal.
2.  **Internet Connection**: The server must be online to communicate with WhatsApp.
3.  **WhatsApp Account**: A dedicated phone number/account is recommended (Business App or Standard App).

## Installation

1.  Navigate to the service directory:
    ```bash
    cd c:\xampp\htdocs\reg-sys.com\icpm2026\whatsapp-service
    ```
2.  Install dependencies:
    ```bash
    npm install
    ```
    *Note: This downloads a local version of Chromium, which may take a few minutes.*

## Starting the Service

1.  **Manual Start**:
    Run the following command in a terminal:
    ```bash
    node server.js
    ```
    You should see: `WhatsApp Service listening at http://localhost:3000`

2.  **Using the Batch Script**:
    Double-click `start_whatsapp.bat` (if created) or create one with the content:
    ```bat
    @echo off
    cd icpm2026\whatsapp-service
    node server.js
    pause
    ```

3.  **Process Management (Production)**:
    For production, use `pm2` or a service manager to keep it running:
    ```bash
    npm install -g pm2
    pm2 start server.js --name "icpm-whatsapp"
    ```

## Usage

1.  **Connect Device**:
    -   Go to the **WhatsApp Dashboard** in the Admin Panel (`whatsapp-dashboard.php`).
    -   If status is "Scan QR Code", open WhatsApp on your phone > Linked Devices > Link a Device.
    -   Scan the QR code displayed on the screen.
    -   Once connected, the status will change to "Connected".

2.  **Sending Messages**:
    -   **Select Recipients**: Choose "All Participants" or a specific group.
    -   **Template**: Customize the message. Use placeholders like `{name}`, `{certificate_link}`.
    -   **Add to Queue**: Click the button to prepare messages.

3.  **Processing the Queue**:
    -   Click "Start Processing Queue".
    -   The system sends messages sequentially (1 every 2-5 seconds) to avoid spam detection.
    -   Monitor the progress and logs on the screen.

## Troubleshooting

-   **QR Code Not Appearing**:
    -   Ensure the Node.js service is running (`http://localhost:3000/status` should return JSON).
    -   Refresh the dashboard.

-   **Service Offline**:
    -   Check the terminal window running `node server.js` for errors.
    -   Restart the service if needed.

-   **Messages Failed**:
    -   Check "Invalid Phone Number" errors. Ensure numbers include country codes or update the PHP logic to prepend defaults.
    -   If "Client not ready", ensure the phone is connected to the internet.

## Safety & Rate Limiting
-   **Limit**: The system is designed to send slowly. Do not modify the delay in `whatsapp_handler.php` to be faster than 2 seconds.
-   **Ban Risk**: Using this with a brand new number for thousands of messages can lead to a ban. Warm up the number first.

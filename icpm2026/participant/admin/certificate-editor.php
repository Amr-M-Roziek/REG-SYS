<?php
include 'dbconnection.php';
include '../../admin/includes/auth_helper.php';
// Ensure session is valid
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}
require_permission('user_edit');

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$user = null;

if ($uid > 0) {
    $query = mysqli_query($con, "SELECT * FROM users WHERE id='$uid'");
    $user = mysqli_fetch_array($query);
}

if (!$user) {
    echo "User not found.";
    exit;
}

// Prepare dynamic data
$fullName = $user['fname'] . ' ' . $user['lname'];
$refNo = $user['id'];
$category = isset($user['category']) ? $user['category'] : '';
$organization = isset($user['organization']) ? $user['organization'] : '';
$profession = isset($user['profession']) ? $user['profession'] : '';

$isParticipant = (stripos($category, 'Participant') !== false);

// Dynamic Certificate Content based on Category
$certTitle = "Certificate of Participation";
$orgText = "A Conference Organized by ICPM";
$awardedToText = "This Certificate is awarded to"; // Default
$contributionText = "For successful participation and attendance at \"ICPM 2026\""; // Default
$confTitle = "the 14th International Conference of Pharmacy and Medicine (ICPM)"; // Default
$entitledText = "Entitled";
$topicText = "Building a Culture of Innovation and Technology in Healthcare"; // Default topic
$dateText = "Held on 20th – 22nd January 2026";
$venueText = "Venue at Sharjah SRTIP United Arab of Emirates"; // Adjusted default
$accreditationText = "Accreditation Code EHS/CPD/26/082";

if (!$isParticipant && !empty($category)) {
    // Custom text for Non-Participants (Speakers, Exhibitors, etc.)
    $awardedToText = "This Certificate is awarded to";
    $contributionText = "In Gratitude for the outstanding Contribution as " . htmlspecialchars($category);
    $confTitle = "At the 14th International Conference of Pharmacy and Medicine";
    $venueText = "Venue at Sharjah SRTIP United Arab of Emirates";
} else {
    // Participant specific text adjustments if needed
    $awardedToText = "This Certificate has been awarded to";
    $venueText = "Venue: Sharjah Research Technology and Innovation Park UAE<br>This Program has been awarded with total of 11 CPD Credits";
}

// Verification Logic
$secret_salt = 'ICPM2026_Secure_Salt';
$hash = md5($user['id'] . $secret_salt);
// Force regsys.cloud domain for verification
$verificationLink = "https://regsys.cloud/icpm2026/verify.php?id=" . $user['id'] . "&hash=" . $hash;
?>
<script>
    // Global variables for dynamic data re-injection
    const currentUserData = {
        fullName: "<?php echo htmlspecialchars($fullName); ?>",
        refNo: "<?php echo htmlspecialchars($refNo); ?>",
        verificationLink: "<?php echo $verificationLink; ?>",
        email: "<?php echo htmlspecialchars($user['email']); ?>",
        category: "<?php echo htmlspecialchars($category); ?>",
        organization: "<?php echo htmlspecialchars($organization); ?>",
        profession: "<?php echo htmlspecialchars($profession); ?>",
        isParticipant: <?php echo $isParticipant ? 'true' : 'false'; ?>
    };
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Editor - <?php echo htmlspecialchars($fullName); ?></title>
    
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- PDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- Interact.js for Drag and Drop/Resizing -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

    <style>
        html, body {
            height: 100%;
            overflow: hidden; /* Prevent body scroll, handle in workspace */
        }
        body {
            background: #f0f2f5;
            font-family: 'Open Sans', sans-serif;
            display: flex;
            flex-direction: column;
        }
        
        #editor-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        #design-sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            width: 250px;
            background: #fff;
            border-right: 1px solid #ddd;
            padding: 20px;
            overflow-y: auto;
            z-index: 900;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }

        #design-sidebar h5 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .tool-group {
            margin-bottom: 20px;
        }

        .tool-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 13px;
        }

        .tool-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        #workspace {
            margin-top: 60px;
            margin-left: 250px;
            flex-grow: 1;
            display: flex;
            justify-content: center;
            padding: 20px;
            overflow: hidden; /* Changed from auto to hidden to rely on scaling */
            background: #f0f2f5;
            position: relative; /* Ensure positioning context */
        }

        /* A4 Landscape Dimensions: 297mm x 210mm */
        /* Screen conversion approx: 1123px x 794px at 96 DPI */
        #certificate-preview {
            width: 1123px;
            height: 794px;
            background: white;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            border: 1px solid #ddd;
            transform-origin: top center;
        }

        /* Certificate Styling */
        .cert-border {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 5px solid #b22222; /* Reddish border */
            pointer-events: none;
            z-index: 0;
        }
        
        .cert-element {
            position: absolute;
            cursor: move;
            text-align: center;
            min-width: 50px;
            padding: 5px;
            border: 1px dashed transparent;
            touch-action: none;
            user-select: none;
            box-sizing: border-box;
        }
        
        .cert-element:hover, .cert-element:focus {
            border: 1px dashed #3498db;
            background: rgba(52, 152, 219, 0.05);
            outline: none;
        }

        .cert-element img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }

        .cert-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        <?php if ($isParticipant): ?>
        /* --- PARTICIPANT TEMPLATE STYLES --- */
        #logo-left { top: 50px; left: 60px; width: 150px; }
        #logo-right { top: 50px; right: 60px; width: 150px; }
        
        #title-header {
            top: 200px;
            width: 100%;
            font-size: 28px;
            color: #7f8c8d;
            font-weight: 300;
            border-bottom: 2px solid #eee;
            width: 60%;
            left: 20%;
        }

        #awarded-to {
            top: 260px;
            width: 100%;
            font-size: 18px;
            color: #333;
        }

        #recipient-name {
            top: 300px;
            width: 100%;
            font-size: 42px;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            color: #000;
        }

        #participation-text {
            top: 380px;
            width: 100%;
            font-size: 16px;
            color: #333;
        }

        #conference-title {
            top: 420px;
            width: 100%;
            font-size: 24px;
            font-weight: bold;
            color: #003366;
        }

        #date-text {
            top: 480px;
            width: 100%;
            font-size: 16px;
            color: #333;
        }

        #venue-text {
            top: 520px;
            width: 100%;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        
        #accreditation-text {
            top: 580px;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #ff4500;
        }

        #sig-left { bottom: 80px; left: 80px; text-align: left; }
        #sig-center { bottom: 60px; left: 50%; transform: translateX(-50%); }
        
        /* New Grouped Signature for consistency if used */
        #sig-right-group {
             bottom: 40px;
             right: 50px;
             text-align: center;
        }

        #icpm-stamp-right { bottom: 90px; right: 260px; z-index: 1; opacity: 0.9; }
        #sig-right-img { bottom: 100px; right: 80px; text-align: center; z-index: 2; }
        #sig-right-text { bottom: 60px; right: 80px; text-align: center; z-index: 1; }

        #qr-code-container {
            bottom: 140px;
            left: 50px;
            width: 100px;
            height: 100px;
            z-index: 10;
        }

        #ref-no {
            bottom: 30px;
            width: 100%;
            font-size: 12px;
            font-weight: bold;
        }

        /* Hide elements not in participant template */
        #org-text, #entitled-text, #topic-text, #footer-logo-left, #sig-right-group { display: none; }

        <?php else: ?>
        /* --- EXHIBITOR/SPEAKER/OTHER TEMPLATE STYLES --- */
        #logo-left { top: 30px; left: 30px; width: 140px; }
        #logo-right { top: 30px; right: 30px; width: 140px; }
        
        #title-header {
            top: 50px;
            width: 100%;
            font-size: 36px;
            color: #000;
            font-weight: 400;
            text-align: center;
        }

        #org-text {
            top: 100px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        #awarded-to {
            top: 140px;
            width: 100%;
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }

        #recipient-name {
            top: 180px;
            width: 100%;
            font-size: 48px;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            color: #e91e63; /* Pink/Red */
        }

        #participation-text {
            top: 260px;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        #conference-title {
            top: 300px;
            width: 100%;
            font-size: 22px;
            font-weight: bold;
            color: #000;
        }

        #logo-center { 
            top: 350px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 120px; 
        }

        #entitled-text {
            top: 440px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        #topic-text {
            top: 470px;
            width: 100%;
            font-size: 20px;
            font-weight: bold;
            color: #00bcd4; /* Cyan/Blue */
        }

        #date-text {
            top: 530px;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        #venue-text {
            top: 570px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        
        #accreditation-text {
            display: none;
            top: 620px;
            width: 100%;
            font-size: 14px;
            color: #777;
        }

        /* Footer Elements */
        #footer-logo-left {
            bottom: 40px;
            left: 50px;
            width: 200px;
        }

        #sig-right-group {
            bottom: 40px;
            right: 50px;
            text-align: center;
        }
        
        #sig-right-img { position: relative; width: 150px; display: block; margin: 0 auto; }
        #sig-right-text { position: relative; margin-top: 5px; font-weight: bold; }
        #icpm-stamp-right { position: absolute; bottom: 20px; right: 0; width: 140px; opacity: 0.9; z-index: 1; }

        #qr-code-container {
            bottom: 50px;
            left: 300px;
            width: 80px;
            height: 80px;
            z-index: 10;
        }

        #ref-no {
            bottom: 10px;
            right: 20px;
            width: auto;
            font-size: 10px;
            color: #aaa;
            text-align: right;
        }

        /* Hide elements not in this template */
        #sig-left, #sig-center { display: none; }
        <?php endif; ?>

        #editor-toolbar h4 {
            margin: 0;
            margin-right: auto;
            color: white;
        }
        
        .toolbar-btn {
            margin-left: 10px;
            background: #3498db;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .toolbar-btn:hover {
            background: #2980b9;
        }
        
        .toolbar-btn.btn-print { background: #27ae60; }
        .toolbar-btn.btn-print:hover { background: #219150; }
        
        .toolbar-btn.btn-back { background: #7f8c8d; }
        .toolbar-btn.btn-back:hover { background: #95a5a6; }

        /* Print Styles */
        @media print {
            @page {
                size: landscape;
                margin: 0;
            }
            body {
                background: white;
                display: block; /* Override flex */
            }
            #editor-toolbar, #design-sidebar {
                display: none !important;
            }
            #workspace {
                margin: 0;
                padding: 0;
                overflow: hidden;
                width: 100%;
                height: 100%;
            }
            #certificate-preview {
                box-shadow: none;
                border: none;
                transform: none !important;
                width: 100%;
                height: 100%;
                page-break-after: always;
            }
            .cert-element {
                border: none !important;
            }
        }
        
        #template-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        #template-modal .modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
        .close-modal:hover { color: #000; }
        
        .template-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .template-item:last-child { border-bottom: none; }
        .template-info { flex-grow: 1; cursor: pointer; }
        .template-actions { margin-left: 10px; }
        .btn-delete-tpl {
            color: #e74c3c;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-delete-tpl:hover { color: #c0392b; }
    </style>
</head>
<body>

<div id="editor-toolbar">
    <h4><i class="fa fa-certificate"></i> Certificate Editor</h4>
    
    <div style="margin-left: 20px;">
        <button class="toolbar-btn" onclick="toggleSidebar()" title="Toggle Sidebar"><i class="fa fa-bars"></i></button>
        <button class="toolbar-btn btn-back" onclick="undo()" id="btn-undo" title="Undo (Ctrl+Z)" disabled><i class="fa fa-undo"></i></button>
        <button class="toolbar-btn btn-back" onclick="redo()" id="btn-redo" title="Redo (Ctrl+Y)" disabled><i class="fa fa-repeat"></i></button>
    </div>

    <div style="flex-grow: 1;"></div>
    <a href="manage-users.php" class="toolbar-btn btn-back"><i class="fa fa-arrow-left"></i> Back</a>
    <button class="toolbar-btn" onclick="saveTemplate()"><i class="fa fa-save"></i> Save Template</button>
    <button class="toolbar-btn" onclick="showTemplateModal()"><i class="fa fa-folder-open"></i> Load Template</button>
    <button class="toolbar-btn btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
    <button class="toolbar-btn" onclick="exportPDF(72)"><i class="fa fa-file-pdf-o"></i> PDF (Screen)</button>
    <button class="toolbar-btn" onclick="exportPDF(300)"><i class="fa fa-file-pdf-o"></i> PDF (High Res)</button>
    <button class="toolbar-btn" onclick="applyFinalCMEFix()" style="background: #e67e22;"><i class="fa fa-wrench"></i> Fix Layout</button>
</div>

<div id="design-sidebar">
    <h5><i class="fa fa-paint-brush"></i> Design Tools</h5>
    
    <div class="tool-group">
        <label>Add Elements</label>
        <button class="btn btn-primary btn-block" onclick="addTextElement()" style="margin-bottom: 5px;"><i class="fa fa-font"></i> Add Text</button>
        <button class="btn btn-secondary btn-block" onclick="document.getElementById('img-upload').click()" style="margin-bottom: 5px;"><i class="fa fa-image"></i> Add Image</button>
        <button class="btn btn-info btn-block" onclick="addQRCodeElement()" style="margin-bottom: 5px;"><i class="fa fa-qrcode"></i> Add QR Code</button>
        <button class="btn btn-warning btn-block" onclick="replaceSelectedImage()" id="btn-replace-img" style="display:none;"><i class="fa fa-refresh"></i> Replace Image</button>
        
        <input type="file" id="img-upload" style="display: none;" accept="image/*" onchange="addImageElement(this)">
        <input type="file" id="img-replace" style="display: none;" accept="image/*" onchange="performImageReplacement(this)">
    </div>
    
    <div class="tool-group">
        <label>Insert Data Variable</label>
        <div style="display:flex; gap:5px;">
            <select id="data-variable-select" class="tool-control">
                    <option value="">-- Select Variable --</option>
                    <option value="fullName">Full Name</option>
                    <option value="refNo">Ref No (ID)</option>
                    <option value="category">Category</option>
                    <option value="organization">Organization</option>
                    <option value="profession">Profession</option>
                    <option value="verificationLink">Verification Link</option>
                    <option value="email">Email</option>
                </select>
            <button class="btn btn-info" onclick="insertDataVariable()"><i class="fa fa-plus"></i></button>
        </div>
        <small style="color:#7f8c8d; font-size:11px;">Adds a new element with the selected variable.</small>
    </div>
    <hr>
    
    <div class="tool-group">
        <label>Selected Element</label>
        <div id="selected-element-name" style="color: #888; font-style: italic; margin-bottom: 10px;">None selected</div>
    </div>

    <div class="tool-group">
        <label>Font Family</label>
        <select id="font-family" class="tool-control" onchange="updateStyle('fontFamily', this.value)">
            <option value="inherit">Default</option>
            <option value="'Open Sans', sans-serif">Open Sans</option>
            <option value="'Times New Roman', serif">Times New Roman</option>
            <option value="Arial, sans-serif">Arial</option>
            <option value="'Courier New', monospace">Courier New</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="'Verdana', sans-serif">Verdana</option>
        </select>
    </div>

    <div class="tool-group">
        <label>Font Size (px)</label>
        <input type="number" id="font-size" class="tool-control" value="16" onchange="updateStyle('fontSize', this.value + 'px')">
    </div>

    <div class="tool-group">
        <label>Text Color</label>
        <input type="color" id="text-color" class="tool-control" style="height: 40px;" onchange="updateStyle('color', this.value)">
    </div>

    <div class="tool-group">
        <label>Font Weight & Style</label>
        <div style="display: flex; gap: 5px;">
            <select id="font-weight" class="tool-control" onchange="updateStyle('fontWeight', this.value)">
                <option value="normal">Normal</option>
                <option value="bold">Bold</option>
                <option value="300">Light</option>
            </select>
            <button class="btn btn-default" onclick="toggleStyle('fontStyle', 'italic')"><i class="fa fa-italic"></i></button>
            <button class="btn btn-default" onclick="toggleStyle('textDecoration', 'underline')"><i class="fa fa-underline"></i></button>
        </div>
    </div>

    <div class="tool-group">
        <label>Text Alignment</label>
        <div class="btn-group" style="width: 100%; display: flex;">
            <button class="btn btn-default" style="flex: 1;" onclick="updateStyle('textAlign', 'left')"><i class="fa fa-align-left"></i></button>
            <button class="btn btn-default" style="flex: 1;" onclick="updateStyle('textAlign', 'center')"><i class="fa fa-align-center"></i></button>
            <button class="btn btn-default" style="flex: 1;" onclick="updateStyle('textAlign', 'right')"><i class="fa fa-align-right"></i></button>
            <button class="btn btn-default" style="flex: 1;" onclick="updateStyle('textAlign', 'justify')"><i class="fa fa-align-justify"></i></button>
        </div>
    </div>

    <div class="tool-group">
        <label>Positioning</label>
        <button class="btn btn-default btn-block" onclick="centerSelectedElement()"><i class="fa fa-arrows-h"></i> Center Horizontally</button>
    </div>

    <div class="tool-group">
        <label>Letter Spacing (px)</label>
        <input type="number" id="letter-spacing" class="tool-control" value="0" onchange="updateStyle('letterSpacing', this.value + 'px')">
    </div>

    <div class="tool-group">
        <label>Line Height</label>
        <input type="number" id="line-height" class="tool-control" value="1.2" step="0.1" onchange="updateStyle('lineHeight', this.value)">
    </div>

    <div class="tool-group">
        <label>Opacity</label>
        <input type="range" id="opacity" class="tool-control" min="0" max="1" step="0.1" value="1" onchange="updateStyle('opacity', this.value)">
    </div>

    <div class="tool-group">
        <label>Rotation (deg)</label>
        <div style="display: flex; gap: 5px;">
            <input type="range" id="rotation-range" class="tool-control" min="-180" max="180" step="1" value="0" oninput="updateRotation(this.value, false)" onchange="updateRotation(this.value, true)">
            <input type="number" id="rotation-number" class="tool-control" min="-180" max="180" value="0" style="width: 60px;" onchange="updateRotation(this.value, true)">
        </div>
    </div>

    <div class="tool-group">
        <label>Layering</label>
        <div style="display: flex; gap: 5px;">
            <button class="btn btn-default" style="flex: 1;" onclick="changeZIndex(1)"><i class="fa fa-arrow-up"></i> Front</button>
            <button class="btn btn-default" style="flex: 1;" onclick="changeZIndex(-1)"><i class="fa fa-arrow-down"></i> Back</button>
        </div>
    </div>

    <div class="tool-group">
        <button class="btn btn-danger btn-block" onclick="deleteSelectedElement()"><i class="fa fa-trash"></i> Delete Element</button>
    </div>
    
    <hr>

    <div class="tool-group">
        <button class="btn btn-warning btn-block" onclick="resetTemplate()"><i class="fa fa-eraser"></i> Reset Settings</button>
    </div>
    
    <div class="tool-group">
        <button class="btn btn-success btn-block" onclick="openSendModal()"><i class="fa fa-paper-plane"></i> Send Certificate as PDF</button>
    </div>
    
    <div class="tool-group">
        <button class="btn btn-info btn-block" onclick="refreshData()"><i class="fa fa-refresh"></i> Refresh Data</button>
    </div>
</div>

<div id="workspace">
    <div id="certificate-preview">
        <div class="cert-border"></div>

        <!-- Logos -->
        <div id="logo-left" class="cert-element draggable">
            <?php if ($isParticipant): ?>
            <img src="../../admin/assets/img/icpm-gold-seal.png" class="cert-logo" alt="Logo 1">
            <?php else: ?>
            <img src="../../admin/assets/img/icpm-stamp.png" class="cert-logo" alt="Stamp 1">
            <?php endif; ?>
        </div>
        
        <div id="logo-right" class="cert-element draggable">
             <?php if ($isParticipant): ?>
             <img src="../../admin/assets/img/icpm-gold-seal.png" class="cert-logo" alt="Logo 2">
             <?php else: ?>
             <img src="../../admin/assets/img/icpm-stamp.png" class="cert-logo" alt="Stamp 2">
             <?php endif; ?>
        </div>

        <!-- Text Content -->
        <div id="title-header" class="cert-element draggable" contenteditable="true">
            <?php echo $certTitle; ?>
        </div>

        <div id="org-text" class="cert-element draggable" contenteditable="true">
            <?php echo $orgText; ?>
        </div>

        <div id="awarded-to" class="cert-element draggable" contenteditable="true">
            <?php echo $awardedToText; ?>
        </div>

        <div id="recipient-name" class="cert-element draggable" contenteditable="true">
            <?php if ($isParticipant): ?>
            « <?php echo htmlspecialchars($fullName); ?> »
            <?php else: ?>
            <?php echo htmlspecialchars($fullName); ?>
            <?php endif; ?>
        </div>

        <div id="participation-text" class="cert-element draggable" contenteditable="true">
            <?php echo $contributionText; ?>
        </div>

        <div id="conference-title" class="cert-element draggable" contenteditable="true">
            <?php echo $confTitle; ?>
        </div>
        
        <div id="logo-center" class="cert-element draggable">
            <img src="../../images/icpm-logo.png" class="cert-logo" style="width: 100%;" alt="ICPM Logo">
        </div>

        <div id="entitled-text" class="cert-element draggable" contenteditable="true">
            <?php echo $entitledText; ?>
        </div>
        
        <div id="topic-text" class="cert-element draggable" contenteditable="true">
            <?php echo $topicText; ?>
        </div>

        <div id="date-text" class="cert-element draggable" contenteditable="true">
            <?php echo $dateText; ?>
        </div>

        <div id="venue-text" class="cert-element draggable" contenteditable="true">
            <?php echo $venueText; ?>
        </div>
        
        <div id="accreditation-text" class="cert-element draggable" contenteditable="true">
             <?php echo $accreditationText; ?>
        </div>

        <!-- Signatures (Participant) -->
        <div id="sig-left" class="cert-element draggable" contenteditable="true">
            <strong>Prof. Omer Eladil Abdalla Hamid</strong><br>
            RAKMHSU
        </div>

        <div id="sig-center" class="cert-element draggable">
             <img src="../../images/icpm-logo.png" style="width: 80px; opacity: 0.8;" alt="Stamp">
        </div>

        <!-- Footer (Non-Participant) -->
        <div id="footer-logo-left" class="cert-element draggable">
             <img src="../../images/icpm-logo.png" style="width: 100%;" alt="ICPM Logo Footer">
        </div>

        <div id="sig-right-group" class="cert-element draggable">
            <div id="icpm-stamp-right">
                <img src="../../admin/assets/img/icpm-stamp-blue.png" style="width: 100%; height: auto;" alt="Stamp">
            </div>
            <div id="sig-right-img">
                <img src="../../admin/assets/img/dr-muneer-signature.png" alt="Signature" style="width: 100%; height: auto;">
            </div>
            <div id="sig-right-text" contenteditable="true">
                Dr. Muneer Rayan<br>
                <span style="font-size: 0.8em; font-weight: normal;">Al Madinah Al Dwalia Exhibitions and<br>Conference Management</span><br>
                ICPM
            </div>
        </div>

        <!-- Old Signature Elements for Participant (Hidden in New, Shown in Old) -->
        <?php if ($isParticipant): ?>
        <!-- These are handled by #sig-right-group in new, but for old we had separate elements.
             Actually, for compatibility, I'll let the user use #sig-right-group or separate elements.
             But the old CSS expects #sig-right-img, #sig-right-text, #icpm-stamp-right to be DIRECT children of certificate-preview.
             The structure above nested them. This breaks the old layout.
        -->
        <?php endif; ?>
        
        <!-- Correcting the structure for compatibility -->
        <!-- I will place the separate elements for Participant outside the group, and hide them if not participant -->
        
        <?php if ($isParticipant): ?>
             <div id="icpm-stamp-right" class="cert-element draggable">
                 <img src="../../admin/assets/img/icpm-stamp-blue.png" alt="ICPM Stamp" style="width: 170px; height: auto;">
             </div>

             <div id="sig-right-img" class="cert-element draggable">
                 <img src="../../admin/assets/img/dr-muneer-signature.png" alt="Signature" style="width: 150px; height: auto; pointer-events: none;">
             </div>

             <div id="sig-right-text" class="cert-element draggable" contenteditable="true">
                 <div style="font-family: 'Times New Roman', serif; font-size: 12pt; color: #000;">
                     <strong>Dr. Muneer Rayan</strong><br>
                     ICPM
                 </div>
             </div>
        <?php endif; ?>

        <div id="qr-code-container" class="cert-element draggable"></div>

        <div id="ref-no" class="cert-element draggable" contenteditable="true">
            Ref No. <?php echo htmlspecialchars($refNo); ?>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div id="template-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; padding:20px; border-radius:8px; width:400px; max-height:80vh; overflow-y:auto;">
        <h4>Select a Template</h4>
        <div id="template-list" style="margin-top:10px;">
            Loading...
        </div>
        <button class="btn btn-default btn-block" style="margin-top:20px;" onclick="document.getElementById('template-modal').style.display='none'">Close</button>
    </div>
</div>

<!-- Send Certificate Modal -->
<div id="send-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; padding:20px; border-radius:8px; width:450px; max-height:80vh; overflow-y:auto;">
        <h4><i class="fa fa-envelope"></i> Send Certificate</h4>
        
        <div class="form-group">
            <label>Recipient Email</label>
            <input type="text" id="send-email" class="form-control" readonly value="">
        </div>

        <div class="form-group">
             <label>Select Design to Send</label>
             <select id="send-template-select" class="form-control">
                 <option value="current">Current Editor Content (Recommended)</option>
             </select>
             <small class="text-muted">Selecting a saved template will load it into the editor before generating the PDF.</small>
        </div>
        
        <div id="send-status" style="margin: 10px 0; display: none;"></div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-primary btn-block" onclick="sendCertificate()" id="btn-confirm-send">Send PDF</button>
            <button class="btn btn-default btn-block" onclick="document.getElementById('send-modal').style.display='none'">Cancel</button>
        </div>
    </div>
</div>


<!-- Scripts -->
<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/jquery-ui-1.9.2.custom.min.js"></script>
<script src="assets/js/certificate-editor-core.js?v=<?php echo time(); ?>"></script>


<script>
    // Event listeners for workspace resizing
    window.addEventListener('resize', resizeWorkspace);
    window.addEventListener('load', function() {
        // If in iframe and small width, collapse sidebar automatically for better preview
        if (window.self !== window.top && window.innerWidth < 1000) {
             const sidebar = document.getElementById('design-sidebar');
             const workspace = document.getElementById('workspace');
             if (sidebar && workspace) {
                 sidebar.style.display = 'none';
                 workspace.style.marginLeft = '0';
             }
        }
        resizeWorkspace();
    });
    setTimeout(resizeWorkspace, 100);


    // Unified Initialization and Automation Script
    window.addEventListener('load', async function() {
        const urlParams = new URLSearchParams(window.location.search);
        const autogen = urlParams.get('autogen');
        const templateId = urlParams.get('template_id');

        // Setup UI for autogen
        let overlay = null;
        if (autogen === 'true') {
            console.log("Autogen started for UID: " + currentUserData.refNo);
            overlay = document.createElement('div');
            Object.assign(overlay.style, {
                position: 'fixed', top: '0', left: '0', width: '100%', height: '100%',
                backgroundColor: 'rgba(255,255,255,0.9)', zIndex: '9999',
                display: 'flex', flexDirection: 'column', justifyContent: 'center', alignItems: 'center'
            });
            overlay.innerHTML = '<h2 style="color:#333"><i class="fa fa-cog fa-spin"></i> Generating Certificate...</h2><p>Please wait, do not close this window.</p>';
            document.body.appendChild(overlay);
        }

        try {
            // 1. Template Loading Strategy
            let loaded = false;

            if (templateId) {
                // Load specific template
                const formData = new FormData();
                formData.append('action', 'load_template');
                formData.append('id', templateId);
                
                const res = await fetch('ajax_handler.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    console.log("Loaded template ID: " + templateId);
                    const elements = JSON.parse(data.data.data);
                    if (typeof applyState === 'function') {
                        applyState(elements);
                        loaded = true;
                    }
                } else {
                    console.error('Template load failed: ' + data.message);
                    if (autogen) throw new Error('Template load failed: ' + data.message);
                }
            } else {
                // Load Default Template ("Final")
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_default_template');
                    
                    const res = await fetch('ajax_handler.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        console.log("Loading default template: " + data.data.name);
                        const elements = JSON.parse(data.data.data);
                        if (typeof applyState === 'function') {
                            applyState(elements);
                            loaded = true;
                        }
                    } else {
                        console.log("No default template found, using hardcoded layout.");
                    }
                } catch (e) {
                    console.error("Failed to load default template", e);
                }
            }

            // 2. Apply User Data & Resize
            if (typeof refreshData === 'function') {
                refreshData(); 
            }
            resizeWorkspace();

            // 3. Autogen Execution
            if (autogen === 'true') {
                // Wait for rendering stabilization (Fonts, Images)
                await document.fonts.ready;
                // Wait for all images to load
                const images = document.querySelectorAll('img');
                await Promise.all(Array.from(images).map(img => {
                    if (img.complete) return Promise.resolve();
                    return new Promise(resolve => { img.onload = resolve; img.onerror = resolve; });
                }));
                // Extra delay for layout and any async rendering
                await new Promise(r => setTimeout(r, 1500));

                // Generate PDF (Silent)
                const element = document.getElementById('certificate-preview');
                // Ensure no selection outline
                if (typeof selectedElement !== 'undefined' && selectedElement) {
                    selectedElement.style.outline = 'none';
                }
                
                const canvas = await html2canvas(element, {
                    scale: 2,
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                    onclone: (clonedDoc) => {
                        const clonedEl = clonedDoc.getElementById('certificate-preview');
                        clonedEl.style.transform = 'none'; 
                        clonedEl.style.margin = '0';
                        
                        const workspace = clonedDoc.getElementById('workspace');
                        if (workspace) {
                            workspace.style.overflow = 'visible';
                            workspace.style.width = 'auto';
                            workspace.style.height = 'auto';
                            workspace.style.position = 'static';
                            workspace.style.margin = '0';
                            workspace.style.padding = '0';
                        }
                        
                        clonedDoc.body.style.overflow = 'visible';
                        clonedDoc.documentElement.style.overflow = 'visible';
                        clonedDoc.body.style.width = '1200px';
                        clonedDoc.body.style.height = 'auto';
                    }
                });

                const imgData = canvas.toDataURL('image/jpeg', 0.85);
                const pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
                pdf.addImage(imgData, 'JPEG', 0, 0, 297, 210);
                const pdfBase64 = pdf.output('datauristring').split(',')[1];

                // Send Email
                const sendData = new FormData();
                sendData.append('action', 'send_certificate');
                sendData.append('uid', currentUserData.refNo);
                sendData.append('pdf_data', pdfBase64);

                const sendRes = await fetch('ajax_handler.php', { method: 'POST', body: sendData });
                const sendJson = await sendRes.json();

                if (sendJson.status === 'success') {
                    overlay.innerHTML = '<h2 style="color:green"><i class="fa fa-check"></i> Sent!</h2>';
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({ 
                            type: 'CERT_PROCESSED',
                            status: 'success', 
                            uid: currentUserData.refNo 
                        }, '*');
                    }
                } else {
                    throw new Error(sendJson.message);
                }
            }

        } catch (e) {
            console.error(e);
            if (overlay) overlay.innerHTML = '<h2 style="color:red"><i class="fa fa-times"></i> Error</h2><p>' + e.message + '</p>';
            if (window.parent && window.parent !== window && autogen === 'true') {
                window.parent.postMessage({ 
                    type: 'CERT_PROCESSED',
                    status: 'error', 
                    uid: currentUserData.refNo, 
                    message: e.message 
                }, '*');
            }
        }
    });

</script>
</body>
</html>
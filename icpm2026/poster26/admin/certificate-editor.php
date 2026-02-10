<?php
require_once 'session_setup.php';
include 'dbconnection.php';
require_once 'permission_helper.php';

// Check session
if (empty($_SESSION['id'])) {
    header('location:index.php');
    exit();
}

// Check permission
if (!has_permission($con, 'edit_users')) {
    die("Access Denied: You do not have permission to edit users.");
}

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
$fullName = $user['fname'] . ' ' . (isset($user['lname']) ? $user['lname'] : '');
$category = isset($user['category']) ? $user['category'] : '';
$organization = isset($user['organization']) ? $user['organization'] : '';
$profession = isset($user['profession']) ? $user['profession'] : '';
$refNo = $user['id'];
$isParticipant = false; // Poster admin

// Verification Logic
$secret_salt = 'ICPM2026_Secure_Salt';
$hash = md5($user['id'] . $secret_salt);
// Determine protocol (http or https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
// Update verification link to point to regsys.cloud verify
$verificationLink = "https://regsys.cloud/icpm2026/poster26/verify.php?id=" . $user['id'] . "&hash=" . $hash;

// Check for override email in URL (for bulk processing)
$overrideEmailParam = isset($_GET['override_email']) ? trim($_GET['override_email']) : '';
?>
<script>
    const currentUid = "<?php echo $uid; ?>";
    const overrideEmailParam = "<?php echo htmlspecialchars($overrideEmailParam); ?>";
    // Global variables for dynamic data re-injection
    const currentUserData = {
        fullName: "<?php echo htmlspecialchars($fullName); ?>",
        refNo: "<?php echo htmlspecialchars($refNo); ?>",
        verificationLink: "<?php echo $verificationLink; ?>",
        email: "<?php echo htmlspecialchars($user['email']); ?>",
        category: "<?php echo htmlspecialchars($category); ?>",
        organization: "<?php echo htmlspecialchars($organization); ?>",
        profession: "<?php echo htmlspecialchars($profession); ?>"
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
        body {
            background: #f0f2f5;
            font-family: 'Open Sans', sans-serif;
            display: flex;
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
            align-items: center; /* Center vertically */
            padding: 20px;
            overflow: hidden; /* Hide scrollbars for cleaner view */
            background: #f0f2f5;
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
            /* Scale down for smaller screens */
            transform-origin: top center;
            flex-shrink: 0; /* Prevent shrinking */
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
            box-sizing: border-box; /* Important for resizing */
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
            max-width: none !important;
            max-height: none !important;
        }

        .cert-logo {
            max-width: 150px;
            height: auto;
        }

        /* Default Positions */
        #logo-left { top: 50px; left: 60px; }
        #logo-center { top: 40px; left: 50%; transform: translateX(-50%); width: 300px; }
        #logo-right { top: 50px; right: 60px; }
        
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
        #sig-right-img { bottom: 100px; right: 80px; text-align: center; z-index: 2; }
        #sig-right-text { bottom: 60px; right: 80px; text-align: center; z-index: 1; }
        #icpm-stamp-right { bottom: 90px; right: 260px; z-index: 1; opacity: 0.9; }

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
    </style>
</head>
<body>

<div id="editor-toolbar">
    <h4><i class="fa fa-certificate"></i> Certificate Editor</h4>
    
    <button class="toolbar-btn" onclick="toggleSidebar()" title="Toggle Sidebar" style="margin-right: 15px;"><i class="fa fa-bars"></i></button>

    <div style="margin-left: 20px;">
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
        <button class="btn btn-default btn-block" onclick="addQRCodeElement()" style="margin-bottom: 5px;"><i class="fa fa-qrcode"></i> Add QR Code</button>
        <button class="btn btn-warning btn-block" onclick="replaceSelectedImage()" id="btn-replace-img" style="display:none;"><i class="fa fa-refresh"></i> Replace Image</button>
        
        <input type="file" id="img-upload" style="display: none;" accept="image/*" onchange="addImageElement(this)">
        <input type="file" id="img-replace" style="display: none;" accept="image/*" onchange="performImageReplacement(this)">
    </div>
    <hr>
    
    <div class="tool-group">
        <label>Insert Data Variable</label>
        <div style="display: flex; gap: 5px;">
             <select id="data-variable-select" class="tool-control">
                <option value="">-- Select Variable --</option>
                <option value="fullName">Full Name</option>
                <option value="category">Category</option>
                <option value="organization">Organization</option>
                <option value="profession">Profession</option>
                <option value="refNo">Ref No</option>
                <option value="verificationLink">Verification Link</option>
                <option value="email">Email</option>
             </select>
             <button class="btn btn-info" onclick="insertDataVariable()"><i class="fa fa-plus"></i></button>
        </div>
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

        <!-- Logos (using relative paths to main admin/images) -->
        <div id="logo-left" class="cert-element draggable">
            <img src="../../admin/assets/img/icpm-gold-seal.png" class="cert-logo" alt="Logo 1"> 
        </div>
        
        <div id="logo-right" class="cert-element draggable">
             <img src="../../admin/assets/img/icpm-gold-seal.png" class="cert-logo" alt="Logo 2">
        </div>

        <!-- Text Content -->
        <div id="title-header" class="cert-element draggable" contenteditable="true">
            Certificate of Participation
        </div>

        <div id="awarded-to" class="cert-element draggable" contenteditable="true">
            This Certificate has been awarded to
        </div>

        <div id="recipient-name" class="cert-element draggable" contenteditable="true">
            « <?php echo htmlspecialchars($fullName); ?> »
        </div>

        <div id="user-category" class="cert-element draggable" contenteditable="true" style="top: 340px; width: 100%; font-size: 20px; color: #555;">
            Category: « <?php echo htmlspecialchars($category); ?> »
        </div>

        <div id="participation-text" class="cert-element draggable" contenteditable="true">
            For successful participation and attendance at "ICPM 2026"
        </div>

        <div id="conference-title" class="cert-element draggable" contenteditable="true">
            the 14th International Conference of Pharmacy and Medicine (ICPM)
        </div>

        <div id="date-text" class="cert-element draggable" contenteditable="true">
            Held on 20th – 22nd January 2026
        </div>

        <div id="venue-text" class="cert-element draggable" contenteditable="true">
            Venue: Sharjah Research Technology and Innovation Park UAE<br>
            This Program has been awarded with total of 11 CPD Credits
        </div>

        <div id="accreditation-text" class="cert-element draggable" contenteditable="true">
            Accreditation Code EHS/CPD/26/082
        </div>

        <!-- Signatures -->
        <div id="sig-left" class="cert-element draggable" contenteditable="true">
            <strong>Prof. Omer Eladil Abdalla Hamid</strong><br>
            RAKMHSU
        </div>

        <div id="sig-center" class="cert-element draggable">
            <!-- Badge/Logo placeholder -->
             <img src="../../images/icpm-logo.png" style="width: 80px; opacity: 0.8;" alt="Stamp">
        </div>

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

        <div id="qr-code-container" class="cert-element draggable"></div>

        <div id="ref-no" class="cert-element draggable" contenteditable="true">
            Certificate Ref No. «<?php echo htmlspecialchars($refNo); ?>»
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
            <input type="text" id="send-email" class="form-control" readonly value="<?php echo htmlspecialchars($user['email']); ?>">
            
            <div class="checkbox" style="margin-top: 10px;">
                <label>
                    <input type="checkbox" id="use-override-email" onchange="toggleOverrideEmail()"> Send to specific email
                </label>
            </div>
            <input type="email" class="form-control" id="override-email" placeholder="Enter email address" style="display:none; margin-top: 5px;" value="<?php echo htmlspecialchars($overrideEmailParam); ?>">
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

<script src="../../admin/assets/js/certificate-editor-core.js?v=<?php echo time(); ?>"></script>
<script>
    // Path variables for Core JS
    const assetPath = "../../admin/assets/";
    const rootImagePath = "../../images/";

    function toggleOverrideEmail() {
        if(document.getElementById('use-override-email').checked) {
            document.getElementById('override-email').style.display = 'block';
            document.getElementById('override-email').focus();
        } else {
            document.getElementById('override-email').style.display = 'none';
        }
    }

    // Auto-generation logic for bulk processing
    window.addEventListener('load', function() {
        // Check if override email was passed via URL and pre-fill/check
        if (typeof overrideEmailParam !== 'undefined' && overrideEmailParam) {
            const overrideCheckbox = document.getElementById('use-override-email');
            const overrideInput = document.getElementById('override-email');
            if (overrideCheckbox && overrideInput) {
                overrideCheckbox.checked = true;
                overrideInput.value = overrideEmailParam;
                overrideInput.style.display = 'block';
            }
        }

        // Initial resize
        if(typeof resizeWorkspace === 'function') resizeWorkspace();

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('autogen')) {
            console.log("Auto-generating certificate for UID:", currentUid);
            // Wait for fonts and images
            document.fonts.ready.then(function() {
                setTimeout(function() {
                    sendCertificate(currentUid, true); // true = isBulk
                }, 1500); // Increased delay to 1500ms for stability
            });
        }
    });

    // Resize workspace on window resize
    window.addEventListener('resize', function() {
        if(typeof resizeWorkspace === 'function') resizeWorkspace();
    });
</script>
</body>
</html>
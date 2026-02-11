
// Certificate Editor Core Logic

// Global Variables
let selectedElement = null;
const historyStack = [];
let historyStep = -1;
const MAX_HISTORY = 50;

// Initialize Interact.js
interact('.draggable')
  .draggable({
    listeners: { move: dragMoveListener },
    modifiers: [
      interact.modifiers.restrictRect({
        restriction: 'parent',
        endOnly: true
      })
    ]
  })
  .resizable({
    edges: { left: true, right: true, bottom: true, top: true },
    listeners: { move: resizeMoveListener },
    modifiers: [
      interact.modifiers.restrictEdges({
        outer: 'parent'
      }),
      interact.modifiers.restrictSize({
        min: { width: 50, height: 50 }
      })
    ]
  })
  .on('tap', function (event) {
    // Only select if not clicking on contentEditable text (to allow editing)
    selectElement(event.currentTarget);
  });

function dragMoveListener (event) {
  var target = event.target
  var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx
  var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy

  target.style.transform = 'translate(' + x + 'px, ' + y + 'px)'
  target.setAttribute('data-x', x)
  target.setAttribute('data-y', y)
  
  // Apply rotation if exists
  const rotation = target.getAttribute('data-rotation') || 0;
  if(rotation != 0) {
      target.style.transform += ' rotate(' + rotation + 'deg)';
  }
}

function resizeMoveListener (event) {
  var target = event.target
  var x = (parseFloat(target.getAttribute('data-x')) || 0)
  var y = (parseFloat(target.getAttribute('data-y')) || 0)

  // update the element's style
  target.style.width = event.rect.width + 'px'
  target.style.height = event.rect.height + 'px'

  // translate when resizing from top or left edges
  x += event.deltaRect.left
  y += event.deltaRect.top

  target.style.transform = 'translate(' + x + 'px, ' + y + 'px)'

  target.setAttribute('data-x', x)
  target.setAttribute('data-y', y)
  
  const rotation = target.getAttribute('data-rotation') || 0;
  if(rotation != 0) {
      target.style.transform += ' rotate(' + rotation + 'deg)';
  }
}

function selectElement(el) {
    if (selectedElement) {
        selectedElement.style.outline = 'none';
    }
    selectedElement = el;
    selectedElement.style.outline = '2px dashed #3498db';
    
    // Update Sidebar Tools
    const nameDisplay = document.getElementById('selected-element-name');
    if(nameDisplay) nameDisplay.textContent = el.id;
    
    // Populate tools with current values
    const computed = window.getComputedStyle(el);
    
    setVal('font-family', computed.fontFamily.replace(/"/g, "'")); // Normalize quotes
    setVal('font-size', parseInt(computed.fontSize));
    setVal('text-color', rgbToHex(computed.color));
    setVal('font-weight', computed.fontWeight);
    setVal('letter-spacing', parseInt(computed.letterSpacing) || 0);
    setVal('line-height', computed.lineHeight === 'normal' ? 1.2 : parseFloat(computed.lineHeight));
    setVal('opacity', computed.opacity);
    
    const rot = el.getAttribute('data-rotation') || 0;
    setVal('rotation-range', rot);
    setVal('rotation-number', rot);
    
    // Enable replace image button if it's an image
    const btnReplace = document.getElementById('btn-replace-img');
    if(btnReplace) {
        btnReplace.style.display = (el.querySelector('img')) ? 'block' : 'none';
    }
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if(el) el.value = val;
}

function rgbToHex(rgb) {
    if (!rgb || rgb === 'transparent') return '#000000';
    if (rgb.startsWith('#')) return rgb;
    
    const sep = rgb.indexOf(",") > -1 ? "," : " ";
    const rgbVals = rgb.substr(4).split(")")[0].split(sep);
    
    let r = (+rgbVals[0]).toString(16),
        g = (+rgbVals[1]).toString(16),
        b = (+rgbVals[2]).toString(16);
  
    if (r.length == 1) r = "0" + r;
    if (g.length == 1) g = "0" + g;
    if (b.length == 1) b = "0" + b;
  
    return "#" + r + g + b;
}

// --- Sidebar Actions ---

function addTextElement() {
    const id = 'text-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'cert-element draggable';
    div.style.top = '100px';
    div.style.left = '100px';
    div.style.fontSize = '24px';
    div.style.color = '#333';
    div.innerHTML = 'Double click to edit';
    div.contentEditable = "false"; // Enable on double click
    
    div.ondblclick = function() {
        this.contentEditable = "true";
        this.focus();
    };
    div.onblur = function() {
        this.contentEditable = "false";
        saveState();
    };
    
    document.getElementById('certificate-preview').appendChild(div);
    selectElement(div);
    saveState();
}

function addImageElement(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const id = 'img-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = 'cert-element draggable';
            div.style.top = '100px';
            div.style.left = '100px';
            div.style.width = '200px';
            div.style.height = 'auto';
            div.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; pointer-events:none;">`;
            
            document.getElementById('certificate-preview').appendChild(div);
            selectElement(div);
            saveState();
        };
        reader.readAsDataURL(input.files[0]);
    }
    input.value = ''; // Reset
}

function replaceSelectedImage() {
    if(selectedElement && selectedElement.querySelector('img')) {
        document.getElementById('img-replace').click();
    }
}

function performImageReplacement(input) {
    if (selectedElement && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = selectedElement.querySelector('img');
            if(img) img.src = e.target.result;
            saveState();
        };
        reader.readAsDataURL(input.files[0]);
    }
    input.value = '';
}

function addQRCodeElement() {
    const existing = document.getElementById('qr-code-container');
    if(existing) {
        alert("QR Code element already exists.");
        return;
    }
    
    const div = document.createElement('div');
    div.id = 'qr-code-container';
    div.className = 'cert-element draggable';
    div.style.top = '500px';
    div.style.left = '100px';
    div.style.width = '100px';
    div.style.height = '100px';
    div.setAttribute('data-variable', 'qrcode');
    
    document.getElementById('certificate-preview').appendChild(div);
    
    // Generate QR
    new QRCode(div, {
        text: currentUserData.verificationLink,
        width: 100,
        height: 100
    });
    
    selectElement(div);
    saveState();
}

function deleteSelectedElement() {
    if(selectedElement) {
        selectedElement.remove();
        selectedElement = null;
        saveState();
    }
}

function updateStyle(prop, value) {
    if(selectedElement) {
        selectedElement.style[prop] = value;
        saveState();
    }
}

function updateRotation(deg, save) {
    if(selectedElement) {
        selectedElement.setAttribute('data-rotation', deg);
        // Re-apply transform
        const x = selectedElement.getAttribute('data-x') || 0;
        const y = selectedElement.getAttribute('data-y') || 0;
        selectedElement.style.transform = `translate(${x}px, ${y}px) rotate(${deg}deg)`;
        
        if(save) saveState();
    }
}

function changeZIndex(dir) {
    if(selectedElement) {
        const current = parseInt(window.getComputedStyle(selectedElement).zIndex) || 0;
        selectedElement.style.zIndex = current + dir;
        saveState();
    }
}

function centerSelectedElement() {
    if(selectedElement) {
        const parentWidth = 1123; // A4 Width
        const elWidth = selectedElement.offsetWidth;
        const left = (parentWidth - elWidth) / 2;
        selectedElement.style.left = left + 'px';
        selectedElement.setAttribute('data-x', 0); // Reset translation
        selectedElement.style.transform = `translate(0px, ${selectedElement.getAttribute('data-y') || 0}px)`;
        saveState();
    }
}

function insertDataVariable() {
    const select = document.getElementById('data-variable-select');
    const val = select.value;
    if(!val) return;
    
    const id = 'var-' + val + '-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'cert-element draggable';
    div.style.top = '200px';
    div.style.left = '200px';
    div.style.fontSize = '24px';
    div.style.color = '#000';
    div.innerHTML = `{${val}}`; // Placeholder
    div.setAttribute('data-variable', val);
    div.contentEditable = "false";
    
    document.getElementById('certificate-preview').appendChild(div);
    
    // Immediately replace with current data for preview
    if(currentUserData[val]) {
        div.setAttribute('data-template', `{${val}}`);
        div.innerHTML = currentUserData[val];
    }
    
    selectElement(div);
    saveState();
    select.value = '';
}

// --- History & State ---

function saveState() {
    // Debounce or just save
    const elements = [];
    document.querySelectorAll('.cert-element').forEach(el => {
        elements.push({
            id: el.id,
            style: el.getAttribute('style'),
            content: el.innerHTML,
            dataX: el.getAttribute('data-x'),
            dataY: el.getAttribute('data-y'),
            dataRotation: el.getAttribute('data-rotation'),
            dataVariable: el.getAttribute('data-variable')
        });
    });
    
    historyStack.push(JSON.stringify(elements));
    historyStep++;
    
    // Limit history
    if (historyStack.length > MAX_HISTORY) {
        historyStack.shift();
        historyStep--;
    }
    
    updateUndoRedoButtons();
}

function undo() {
    if (historyStep > 0) {
        historyStep--;
        applyState(JSON.parse(historyStack[historyStep]));
        updateUndoRedoButtons();
    }
}

function redo() {
    if (historyStep < historyStack.length - 1) {
        historyStep++;
        applyState(JSON.parse(historyStack[historyStep]));
        updateUndoRedoButtons();
    }
}

function applyState(elements) {
    const preview = document.getElementById('certificate-preview');
    // Keep the border
    const border = preview.querySelector('.cert-border');
    preview.innerHTML = '';
    if(border) preview.appendChild(border);
    else {
        const b = document.createElement('div');
        b.className = 'cert-border';
        preview.appendChild(b);
    }
    
    elements.forEach(data => {
        const div = document.createElement('div');
        div.id = data.id;
        div.className = 'cert-element draggable';
        div.setAttribute('style', data.style);
        if(data.dataX) div.setAttribute('data-x', data.dataX);
        if(data.dataY) div.setAttribute('data-y', data.dataY);
        if(data.dataRotation) div.setAttribute('data-rotation', data.dataRotation);
        if(data.dataVariable) div.setAttribute('data-variable', data.dataVariable);
        
        // Restore content
        div.innerHTML = data.content;

        // If content contains a variable placeholder, store it as a template
        if (data.dataVariable && data.content.includes('{')) {
             div.setAttribute('data-template', data.content);
        }
        
        // Restore contentEditable if it was text (heuristic: no img)
        if (!div.querySelector('img') && div.id !== 'qr-code-container') {
             div.contentEditable = "false";
             
             // Event listeners
             div.ondblclick = function() {
                this.contentEditable = "true";
                this.focus();
             };
             div.onblur = function() {
                this.contentEditable = "false";
                saveState();
             };
        }
        
        preview.appendChild(div);
    });
    
    // Re-initialize QR code
    const qrContainer = document.getElementById("qr-code-container");
    if(qrContainer && qrContainer.innerHTML === '') {
         new QRCode(qrContainer, {
            text: currentUserData.verificationLink,
            width: 100,
            height: 100,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
    
    refreshData();
}

function updateUndoRedoButtons() {
    const btnUndo = document.getElementById('btn-undo');
    const btnRedo = document.getElementById('btn-redo');
    if(btnUndo) btnUndo.disabled = (historyStep <= 0);
    if(btnRedo) btnRedo.disabled = (historyStep >= historyStack.length - 1);
}


// --- Saving / Loading ---

function saveTemplate() {
    const name = prompt("Enter a name for this template:");
    if (!name) return;
    
    // Prepare data
    const elements = [];
    document.querySelectorAll('.cert-element').forEach(el => {
        elements.push({
            id: el.id,
            style: el.getAttribute('style'),
            content: el.innerHTML,
            dataX: el.getAttribute('data-x'),
            dataY: el.getAttribute('data-y'),
            dataRotation: el.getAttribute('data-rotation'),
            dataVariable: el.getAttribute('data-variable')
        });
    });
    
    const dataStr = JSON.stringify(elements);
    
    // Send to backend
    const formData = new FormData();
    formData.append('action', 'save_template');
    formData.append('name', name);
    formData.append('data', dataStr);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
        } else {
            alert('Error saving template: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
    });
}

function showTemplateModal() {
    const modal = document.getElementById('template-modal');
    const list = document.getElementById('template-list');
    if(modal) modal.style.display = 'flex';
    if(list) list.innerHTML = 'Loading...';
    
    const formData = new FormData();
    formData.append('action', 'get_templates');
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.data.length === 0) {
                if(list) list.innerHTML = '<p>No templates found.</p>';
                return;
            }
            
            let html = '<div class="list-group">';
            data.data.forEach(tpl => {
                html += `<div class="list-group-item list-group-item-action template-item">
                            <div class="template-info" onclick="loadTemplate(${tpl.id})">
                                ${tpl.name} <small class="text-muted">(${tpl.created_at})</small>
                            </div>
                            <div class="template-actions">
                                <button class="btn-delete-tpl" onclick="deleteTemplate(${tpl.id}, '${tpl.name.replace(/'/g, "\\'")}')" title="Delete Template">
                                    <i class="fa fa-trash-o"></i>
                                </button>
                            </div>
                         </div>`;
            });
            html += '</div>';
            if(list) list.innerHTML = html;
        } else {
            if(list) list.innerHTML = '<p class="text-danger">Error loading templates.</p>';
        }
    })
    .catch(error => {
        if(list) list.innerHTML = '<p class="text-danger">Connection error.</p>';
    });
}

function deleteTemplate(id, name) {
    if (!confirm(`Are you sure you want to delete template "${name}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_template');
    formData.append('id', id);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Refresh list
            showTemplateModal();
        } else {
            alert('Error deleting template: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
    });
}

function loadTemplate(id) {
    const formData = new FormData();
    formData.append('action', 'load_template');
    formData.append('id', id);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const elements = JSON.parse(data.data.data);
            applyState(elements);
            saveState(); // Add to history
            const modal = document.getElementById('template-modal');
            if(modal) modal.style.display = 'none';
        } else {
            alert('Error loading template: ' + data.message);
        }
    })
    .catch(error => {
        alert('Connection error.');
    });
}

// --- Added Missing Functions ---

function loadTemplateByName(name) {
    const formData = new FormData();
    formData.append('action', 'load_template_by_name');
    formData.append('name', name);
    
    fetch('ajax_handler.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const elements = JSON.parse(data.data.data);
            applyState(elements);
        } else {
            console.log("Template '" + name + "' not found, checking defaults...");
            // Fallback to default
            loadDefaultTemplate();
        }
    })
    .catch(e => {
        console.log("Error loading by name, checking defaults...");
        loadDefaultTemplate();
    });
}

function loadDefaultTemplate() {
    const formData = new FormData();
    formData.append('action', 'get_default_template');
    
    fetch('ajax_handler.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const elements = JSON.parse(data.data.data);
            applyState(elements);
        } else {
            console.log("No default template found.");
            alert("Could not load any certificate template. Please contact support.");
        }
    })
    .catch(e => {
        console.error("Error loading default template:", e);
        alert("Error loading default template.");
    });
}

function refreshData() {
    if (typeof currentUserData === 'undefined') return;

    document.querySelectorAll('.cert-element').forEach(el => {
        const variable = el.getAttribute('data-variable');
        
        // Handle Text replacements
        if (variable && currentUserData[variable]) {
             let template = el.getAttribute('data-template');
             
             // If no template stored, but content has {}, try to infer it or just replace if it matches {var}
             if (!template && el.innerHTML.includes('{')) {
                 template = el.innerHTML;
                 el.setAttribute('data-template', template);
             }
             
             if (template) {
                 el.innerHTML = template.replace('{' + variable + '}', currentUserData[variable]);
             } else if (variable) {
                 // Direct replacement for elements that are purely variables (like name)
                 // Only if the current content doesn't look like a template (no other text)
                 // But safer to assume if data-variable is set, we set the content.
                 // However, we must respect the "template" nature if user added "Dr. {fullName}".
                 
                 // If no template, we just set the text if it's currently a placeholder
                 if(el.innerHTML.trim() === '{' + variable + '}') {
                     el.innerHTML = currentUserData[variable];
                 }
             }
        }
        
        // Handle QRCode special case
        if (variable === 'qrcode' && el.id === 'qr-code-container') {
             el.innerHTML = ''; // Clear
             new QRCode(el, {
                text: currentUserData.verificationLink,
                width: 100,
                height: 100,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('design-sidebar');
    const workspace = document.getElementById('workspace');
    if (sidebar.style.display === 'none') {
        sidebar.style.display = 'block';
        workspace.style.marginLeft = '250px';
    } else {
        sidebar.style.display = 'none';
        workspace.style.marginLeft = '0';
    }
}

function resizeWorkspace() {
    // Optional: dynamic resizing logic
    // For now, CSS handles most of it
}


// --- Export & Send ---

async function exportPDF(dpi) {
    const element = document.getElementById('certificate-preview');
    
    // Temporarily remove borders or selection indicators if any
    if (selectedElement) {
        selectedElement.style.outline = 'none';
    }
    
    const scale = dpi === 300 ? 3.125 : 1; // 96 * 3.125 = 300
    
    try {
        const canvas = await html2canvas(element, {
            scale: scale,
            useCORS: true,
            allowTaint: true,
            logging: false
        });
        
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        
        // A4 Landscape: 297 x 210 mm
        const pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
        pdf.addImage(imgData, 'JPEG', 0, 0, 297, 210);
        pdf.save(`Certificate_${currentUserData.refNo}.pdf`);
        
    } catch (e) {
        console.error("PDF Export Error:", e);
        alert("Error generating PDF. Please check console.");
    }
    
    // Restore selection
    if (selectedElement) {
        selectedElement.style.outline = '2px dashed #3498db';
    }
}

function openSendModal() {
    document.getElementById('send-email').value = currentUserData.email;
    document.getElementById('send-modal').style.display = 'flex';
}

async function sendCertificate(uid, isBulk = false) {
    const btn = document.getElementById('btn-confirm-send');
    const statusDiv = document.getElementById('send-status');
    const modal = document.getElementById('send-modal');
    
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
    }
    if(statusDiv) {
        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
    }
    
    // Hide modal and backdrops for clean capture
    if(modal) modal.style.display = 'none';
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(bd => bd.style.display = 'none');
    
    // Add delay to ensure gray overlay is gone
    await new Promise(r => setTimeout(r, 500));
    
    // Wait for fonts to ensure text renders
    await document.fonts.ready;
    
    try {
        // Generate PDF blob
        const element = document.getElementById('certificate-preview');
        // Deselect any selected element
        document.querySelectorAll('.cert-element').forEach(el => el.style.outline = 'none');
        
        const canvas = await html2canvas(element, {
            scale: 2, 
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false
        });
        
        const imgData = canvas.toDataURL('image/jpeg', 0.85);
        const pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
        pdf.addImage(imgData, 'JPEG', 0, 0, 297, 210);
        
        // Get Base64 string (remove data URI prefix)
        const pdfDataUri = pdf.output('datauristring');
        const pdfBase64 = pdfDataUri.split(',')[1];
        
        // Create FormData
        const formData = new FormData();
        formData.append('action', 'send_certificate');
        
        // Use provided uid, or global currentUid, or fallback to currentUserData.refNo
        let targetUid = uid;
        if (!targetUid && typeof currentUid !== 'undefined') targetUid = currentUid;
        if (!targetUid && typeof currentUserData !== 'undefined') targetUid = currentUserData.refNo;
        
        formData.append('uid', targetUid);
        formData.append('pdf_data', pdfBase64);

        // Check for override email
        // 1. From URL parameters (for bulk autogen)
        const urlParams = new URLSearchParams(window.location.search);
        const overrideEmailParam = urlParams.get('override_email');
        if (overrideEmailParam) {
            formData.append('override_email', overrideEmailParam);
        } 
        // 2. From Modal Input (for manual send)
        else {
            const emailInput = document.getElementById('send-email');
            if (emailInput && emailInput.value && emailInput.value !== currentUserData.email) {
                formData.append('override_email', emailInput.value);
            }
        }

        const response = await fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const res = await response.json();
        
        if(res.status === 'success') {
             if(statusDiv) statusDiv.innerHTML = '<div class="alert alert-success">Certificate sent successfully!</div>';
             
             // Restore UI after short delay
             setTimeout(() => {
                 if(statusDiv) statusDiv.style.display = 'none';
                 if(btn) {
                     btn.disabled = false;
                     btn.innerHTML = 'Send PDF';
                 }
             }, 2000);

             // PostMessage for Bulk
             if (isBulk && window.parent) {
                window.parent.postMessage({ 
                    action: 'CERT_PROCESSED',
                    status: 'success', 
                    uid: targetUid 
                }, '*');
             }

        } else {
             throw new Error(res.message || 'Server error');
        }

    } catch (e) {
        console.error(e);
        if(statusDiv) statusDiv.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
        if(btn) btn.disabled = false;
        
        // Restore backdrops on error
        if(modal) modal.style.display = 'flex';
        backdrops.forEach(bd => bd.style.display = 'block');
        
        if (isBulk && window.parent) {
            window.parent.postMessage({ 
                action: 'CERT_PROCESSED',
                status: 'error', 
                message: e.message,
                uid: uid 
            }, '*');
        }
    }
}

// Initial Setup
window.addEventListener('load', function() {
    // Generate QR Code
    const qrContainer = document.getElementById("qr-code-container");
    if(qrContainer) {
        new QRCode(qrContainer, {
            text: currentUserData.verificationLink,
            width: 100,
            height: 100,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
    
    // Save initial state
    saveState();
});

function resetTemplate() {
    if(confirm("Are you sure you want to reset the template to default settings? All changes will be lost.")) {
        loadDefaultTemplate();
    }
}

function applyFinalCMEFix() {
    if(!confirm("This will reset the layout to the optimized 'Final-CME' standard. Any unsaved changes will be lost. Continue?")) return;

    // Use global path variables if defined, otherwise fallback to default relative paths
    const assets = (typeof assetPath !== 'undefined') ? assetPath : 'assets/';
    const rootImages = (typeof rootImagePath !== 'undefined') ? rootImagePath : '../images/';

    const elements = [
        // --- Logos ---
        {
            id: 'logo-left',
            style: 'top: 50px; left: 60px; width: 150px; height: auto;',
            content: `<img src="${assets}img/icpm-gold-seal.png" class="cert-logo" alt="Logo 1">`,
            dataX: '0', dataY: '0'
        },
        {
            id: 'logo-right',
            style: 'top: 50px; left: 913px; width: 150px; height: auto;',
            content: `<img src="${assets}img/icpm-gold-seal.png" class="cert-logo" alt="Logo 2">`,
            dataX: '0', dataY: '0'
        },
        
        // --- Header Section ---
        {
            id: 'title-header',
            style: 'top: 180px; left: 0px; width: 1123px; text-align: center; font-size: 32px; color: #7f8c8d; font-weight: 300; border: none;',
            content: 'Certificate of Participation',
            dataX: '0', dataY: '0'
        },
        {
            id: 'org-text',
            style: 'top: 150px; left: 0px; width: 1123px; text-align: center; font-size: 14px; color: #555;',
            content: 'A Conference Organized by ICPM',
            dataX: '0', dataY: '0'
        },
        
        // --- Recipient Section ---
        {
            id: 'awarded-to',
            style: 'top: 250px; left: 0px; width: 1123px; text-align: center; font-size: 18px; color: #333;',
            content: 'This Certificate is awarded to',
            dataX: '0', dataY: '0'
        },
        {
            id: 'recipient-name',
            style: 'top: 290px; left: 0px; width: 1123px; text-align: center; font-size: 42px; font-weight: bold; font-family: \'Times New Roman\', serif; color: #000;',
            content: 'Participant Name', // Placeholder
            dataVariable: 'fullName',
            dataX: '0', dataY: '0'
        },
        
        // --- Body Text ---
        {
            id: 'participation-text',
            style: 'top: 370px; left: 111px; width: 900px; text-align: center; font-size: 16px; color: #333;',
            content: 'For successful participation and attendance at "ICPM 2026"',
            dataX: '0', dataY: '0'
        },
        {
            id: 'conference-title',
            style: 'top: 410px; left: 0px; width: 1123px; text-align: center; font-size: 24px; font-weight: bold; color: #003366;',
            content: 'the 14th International Conference of Pharmacy and Medicine (ICPM)',
            dataX: '0', dataY: '0'
        },
        {
            id: 'date-text',
            style: 'top: 480px; left: 0px; width: 1123px; text-align: center; font-size: 16px; color: #333;',
            content: 'Held on 20th – 22nd January 2026',
            dataX: '0', dataY: '0'
        },
        {
            id: 'venue-text',
            style: 'top: 520px; left: 0px; width: 1123px; text-align: center; font-size: 14px; color: #333; line-height: 1.5;',
            content: 'Venue: Sharjah Research Technology and Innovation Park UAE<br>This Program has been awarded with total of 11 CPD Credits',
            dataX: '0', dataY: '0'
        },
        {
            id: 'accreditation-text',
            style: 'top: 580px; left: 0px; width: 1123px; text-align: center; font-size: 18px; font-weight: bold; color: #ff4500;',
            content: 'Accreditation Code EHS/CPD/26/082',
            dataX: '0', dataY: '0'
        },
        
        // --- Signatures ---
        {
            id: 'sig-left',
            style: 'top: 660px; left: 100px; width: 250px; text-align: center; font-size: 14px;',
            content: '<strong>Prof. Omer Eladil Abdalla Hamid</strong><br>RAKMHSU',
            dataX: '0', dataY: '0'
        },
        {
            id: 'sig-center',
            style: 'top: 650px; left: 521px; width: 80px; opacity: 0.8;',
            content: `<img src="${rootImages}icpm-logo.png" style="width: 80px; opacity: 0.8;" alt="Stamp">`,
            dataX: '0', dataY: '0'
        },
        
        // Right Group (Dr Muneer)
        {
            id: 'icpm-stamp-right',
            style: 'top: 620px; left: 800px; width: 170px; height: auto; z-index: 1;',
            content: `<img src="${assets}img/icpm-stamp-blue.png" alt="ICPM Stamp" style="width: 170px; height: auto;">`,
            dataX: '0', dataY: '0'
        },
        {
            id: 'sig-right-img',
            style: 'top: 650px; left: 810px; width: 150px; height: auto; z-index: 2;',
            content: `<img src="${assets}img/dr-muneer-signature.png" alt="Signature" style="width: 150px; height: auto; pointer-events: none;">`,
            dataX: '0', dataY: '0'
        },
        {
            id: 'sig-right-text',
            style: 'top: 730px; left: 800px; width: 200px; text-align: center; font-size: 12px; font-family: \'Times New Roman\', serif; z-index: 3;',
            content: '<strong>Dr. Muneer Rayan</strong><br>ICPM',
            dataX: '0', dataY: '0'
        },
        
        // --- Footer ---
        {
            id: 'qr-code-container',
            style: 'top: 680px; left: 50px; width: 80px; height: 80px;',
            content: '', // QRCode generated by refreshData
            dataVariable: 'qrcode',
            dataX: '0', dataY: '0'
        },
        {
            id: 'ref-no',
            style: 'top: 760px; left: 950px; width: 150px; text-align: right; font-size: 12px; color: #999;',
            content: 'Ref No. ID',
            dataVariable: 'refNo',
            dataX: '0', dataY: '0'
        }
    ];
    
    applyState(elements);
    alert("Layout fixed! Please click 'Save Template' and name it 'Final-CME' to apply this permanently.");
}

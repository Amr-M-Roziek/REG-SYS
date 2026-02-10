// Certificate Editor Core Logic
// Extracted from inline script for modularity and parity

// Global Variables
const historyStack = [];
let historyStep = -1;
const MAX_HISTORY = 50;
let activeElement = null; // Replaces selectedElement from other versions for consistency with this logic

// Undo/Redo Logic
function saveState() {
    if (historyStep < historyStack.length - 1) {
        historyStack.splice(historyStep + 1);
    }
    
    const elements = [];
    document.querySelectorAll('.cert-element').forEach(el => {
        elements.push({
            id: el.id,
            style: el.getAttribute('style'),
            content: el.innerHTML,
            dataX: el.getAttribute('data-x'),
            dataY: el.getAttribute('data-y'),
            dataRotation: el.getAttribute('data-rotation'),
            dataVariable: el.getAttribute('data-variable') // Save data variable mapping
        });
    });
    
    historyStack.push(JSON.stringify(elements));
    historyStep++;
    
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
    const border = preview.querySelector('.cert-border');
    preview.innerHTML = '';
    if(border) preview.appendChild(border);
    else preview.innerHTML = '<div class="cert-border"></div>';
    
    elements.forEach(data => {
        const div = document.createElement('div');
        div.id = data.id;
        div.className = 'cert-element draggable';
        div.setAttribute('style', data.style);
        if(data.dataX) div.setAttribute('data-x', data.dataX);
        if(data.dataY) div.setAttribute('data-y', data.dataY);
        if(data.dataRotation) div.setAttribute('data-rotation', data.dataRotation);
        if(data.dataVariable) div.setAttribute('data-variable', data.dataVariable);

        // Store template for variable replacement
        if (data.dataVariable && data.content && data.content.includes('{')) {
             div.setAttribute('data-template', data.content);
        }
        
        // Re-inject dynamic content based on ID or data-variable
        if (div.id === 'recipient-name') {
             div.innerHTML = data.content; 
             div.contentEditable = "true";
        } else if (div.id === 'qr-code-container') {
             div.innerHTML = ''; 
        } else if (data.dataVariable && typeof currentUserData !== 'undefined' && currentUserData[data.dataVariable]) {
             // If it has a variable mapping, we can optionally refresh it
             // But for undo/redo, we usually want the exact state saved.
             // However, let's ensure it's consistent.
             div.innerHTML = data.content;
             div.contentEditable = "true";
        } else {
            div.innerHTML = data.content;

            // If content contains a variable placeholder, store it as a template
            if (data.dataVariable && data.content.includes('{')) {
                div.setAttribute('data-template', data.content);
            }

            if (!div.querySelector('img')) {
                div.contentEditable = "true";
            }
        }
        
        preview.appendChild(div);
    });
    
    const qrContainer = document.getElementById("qr-code-container");
    if(qrContainer) {
        qrContainer.innerHTML = '';
        new QRCode(qrContainer, {
            text: currentUserData.verificationLink,
            width: 100,
            height: 100,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
    
    activeElement = null;
    if(document.getElementById('selected-element-name')) 
        document.getElementById('selected-element-name').innerText = "None selected";
    if(document.getElementById('btn-replace-img'))
        document.getElementById('btn-replace-img').style.display = 'none';
    document.querySelectorAll('.cert-element').forEach(i => i.style.outline = 'none');
}

function updateUndoRedoButtons() {
    const btnUndo = document.getElementById('btn-undo');
    const btnRedo = document.getElementById('btn-redo');
    if(btnUndo) {
        btnUndo.disabled = historyStep <= 0;
        btnUndo.style.opacity = historyStep <= 0 ? '0.5' : '1';
    }
    if(btnRedo) {
        btnRedo.disabled = historyStep >= historyStack.length - 1;
        btnRedo.style.opacity = historyStep >= historyStack.length - 1 ? '0.5' : '1';
    }
}

window.addEventListener('load', () => {
    saveState(); 
    // Init QR
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
});

interact('.draggable')
  .draggable({
    listeners: {
      move (event) {
        var target = event.target;
        var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
        var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
        var r = (parseFloat(target.getAttribute('data-rotation')) || 0);

        target.style.transform = 'translate(' + x + 'px, ' + y + 'px) rotate(' + r + 'deg)';
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
        
        if(activeElement !== target) {
             activeElement = target;
             updateToolbarFromElement(target);
        }
      },
      end (event) {
          saveState();
      }
    },
    modifiers: [
      interact.modifiers.restrictRect({
        restriction: 'parent',
        endOnly: true
      })
    ]
  })
  .resizable({
    edges: { left: true, right: true, bottom: true, top: true },
    listeners: {
      move (event) {
        var target = event.target;
        var x = (parseFloat(target.getAttribute('data-x')) || 0);
        var y = (parseFloat(target.getAttribute('data-y')) || 0);
        var r = (parseFloat(target.getAttribute('data-rotation')) || 0);

        if (event.altKey) {
            var oldWidth = event.rect.width - event.deltaRect.width;
            var oldHeight = event.rect.height - event.deltaRect.height;
            var deltaW = event.deltaRect.width;
            var deltaH = event.deltaRect.height;
            var newWidth = oldWidth + (deltaW * 2);
            var newHeight = oldHeight + (deltaH * 2);
            
            target.style.width = newWidth + 'px';
            target.style.height = newHeight + 'px';
            x -= deltaW;
            y -= deltaH;
        } else {
            target.style.width = event.rect.width + 'px';
            target.style.height = event.rect.height + 'px';
            x += event.deltaRect.left;
            y += event.deltaRect.top;
        }

        target.style.transform = 'translate(' + x + 'px, ' + y + 'px) rotate(' + r + 'deg)';
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
      },
      end (event) {
          saveState();
      }
    }
  })
  .on('tap', function(event) {
      activeElement = event.currentTarget;
      updateToolbarFromElement(activeElement);
  });
  
// --- Positioning Functions ---
window.centerSelectedElement = function() {
    if (!activeElement) {
        alert("Please select an element first.");
        return;
    }
    
    // Canvas dimensions (A4 Landscape)
    const canvasWidth = 1123;
    
    // Get element width
    // We use offsetWidth which gives the unscaled width in pixels within the container
    const elementWidth = activeElement.offsetWidth;
    
    // Calculate centered Left position
    const targetLeft = (canvasWidth - elementWidth) / 2;
    
    // Reset position logic
    // We set 'left' to the target position and reset 'data-x' translation to 0
    activeElement.style.left = targetLeft + 'px';
    activeElement.setAttribute('data-x', 0);
    
    // Preserve Y translation and Rotation
    const y = parseFloat(activeElement.getAttribute('data-y')) || 0;
    const rotation = activeElement.getAttribute('data-rotation') || 0;
    
    // Update transform
    activeElement.style.transform = `translate(0px, ${y}px) rotate(${rotation}deg)`;
    
    saveState();
}
  
function updateToolbarFromElement(el) {
    document.querySelectorAll('.cert-element').forEach(i => i.style.outline = 'none');
    el.style.outline = '2px solid #3498db';
    
    if(document.getElementById('selected-element-name'))
        document.getElementById('selected-element-name').innerText = el.id || (el.getAttribute('data-variable') ? "Variable: " + el.getAttribute('data-variable') : "Unnamed Element");
    
    // Show/Hide image tools
    if(document.getElementById('btn-replace-img')) {
        if(el.querySelector('img')) {
            document.getElementById('btn-replace-img').style.display = 'block';
        } else {
            document.getElementById('btn-replace-img').style.display = 'none';
        }
    }
    
    // Update controls
    const style = window.getComputedStyle(el);
    if(document.getElementById('font-family')) document.getElementById('font-family').value = style.fontFamily.replace(/"/g, "'"); 
    if(document.getElementById('font-size')) document.getElementById('font-size').value = parseInt(style.fontSize);
    if(document.getElementById('font-weight')) document.getElementById('font-weight').value = style.fontWeight;
    if(document.getElementById('line-height')) document.getElementById('line-height').value = style.lineHeight === 'normal' ? 1.2 : parseFloat(style.lineHeight);
    if(document.getElementById('opacity')) document.getElementById('opacity').value = style.opacity;
    
    let r = parseFloat(el.getAttribute('data-rotation')) || 0;
    if(document.getElementById('rotation-range')) document.getElementById('rotation-range').value = r;
    if(document.getElementById('rotation-number')) document.getElementById('rotation-number').value = r;
}

function updateRotation(value, isFinal) {
    if(activeElement) {
        let r = parseFloat(value);
        activeElement.setAttribute('data-rotation', r);
        
        var x = (parseFloat(activeElement.getAttribute('data-x')) || 0);
        var y = (parseFloat(activeElement.getAttribute('data-y')) || 0);
        activeElement.style.transform = 'translate(' + x + 'px, ' + y + 'px) rotate(' + r + 'deg)';
        
        if(isFinal) {
            saveState();
        }
    }
}

// Wrapper for style updates
function updateStyle(property, value) {
    if(activeElement) {
         activeElement.style[property] = value;
         saveState();
    }
}

function toggleStyle(property, value) {
    if(activeElement) {
        if (activeElement.style[property] === value) {
            activeElement.style[property] = 'normal';
        } else {
            activeElement.style[property] = value;
        }
        saveState();
    }
}

function changeZIndex(direction) {
    if(activeElement) {
        let currentZ = parseInt(window.getComputedStyle(activeElement).zIndex) || 1;
        activeElement.style.zIndex = currentZ + direction;
        saveState();
    }
}

function deleteSelectedElement() {
    if (activeElement) {
        if(confirm('Delete selected element?')) {
            activeElement.remove();
            activeElement = null;
            if(document.getElementById('selected-element-name'))
                document.getElementById('selected-element-name').innerText = "None selected";
            saveState();
        }
    }
}

function addTextElement() {
    const div = document.createElement('div');
    div.className = 'cert-element draggable';
    div.contentEditable = "true";
    div.innerHTML = "New Text";
    div.style.top = '100px';
    div.style.left = '100px';
    div.style.fontSize = '16px';
    div.style.fontFamily = "'Open Sans', sans-serif";
    document.getElementById('certificate-preview').appendChild(div);
    saveState();
}

function addImageElement(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            const div = document.createElement('div');
            div.className = 'cert-element draggable';
            div.style.top = '100px';
            div.style.left = '100px';
            const img = document.createElement('img');
            img.src = e.target.result;
            div.appendChild(img);
            document.getElementById('certificate-preview').appendChild(div);
            saveState();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function replaceSelectedImage() {
    if(activeElement && activeElement.querySelector('img')) {
        document.getElementById('img-replace').click();
    }
}

function performImageReplacement(input) {
    if (input.files && input.files[0] && activeElement) {
         var reader = new FileReader();
         reader.onload = function(e) {
             const img = activeElement.querySelector('img');
             if(img) {
                 img.src = e.target.result;
                 saveState();
             }
         }
         reader.readAsDataURL(input.files[0]);
    }
}

// Data Variable Insertion
function insertDataVariable() {
    const select = document.getElementById('data-variable-select');
    if (!select) return;
    const variable = select.value;
    if (!variable) return;

    const div = document.createElement('div');
    div.className = 'cert-element draggable';
    div.contentEditable = "true";
    div.setAttribute('data-variable', variable);
    
    // Get value from currentUserData if available
    let content = "{" + variable + "}";
    if (typeof currentUserData !== 'undefined' && currentUserData[variable]) {
        content = currentUserData[variable];
    } else if (typeof currentUserData !== 'undefined') {
         // Fallback checks
         if(variable === 'fullName') content = currentUserData.fullName;
         else if(variable === 'refNo') content = currentUserData.refNo;
    }
    
    div.innerHTML = content;
    div.style.top = '200px';
    div.style.left = '200px';
    div.setAttribute('data-x', 0);
    div.setAttribute('data-y', 0);
    div.style.fontSize = '18px';
    div.style.fontFamily = "'Open Sans', sans-serif";
    div.style.zIndex = '100'; 
    
    document.getElementById('certificate-preview').appendChild(div);
    saveState();
}

function refreshVariables() {
    if (typeof currentUserData === 'undefined') return;
    document.querySelectorAll('.cert-element').forEach(el => {
        const variable = el.getAttribute('data-variable');
        if (variable && currentUserData[variable]) {
            const content = currentUserData[variable];
            // Only update if we have content (or explicit empty)
            // Check if element has children (like images? no, text only for variables)
            const template = el.getAttribute('data-template');
            if (template) {
                 el.innerHTML = template.replace('{' + variable + '}', content);
            } else {
                 el.innerText = content;
            }
        }
    });
}

// Template Management
function saveTemplate() {
    const name = prompt("Enter a name for this template:");
    if (!name) return;
    
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
    
    const data = JSON.stringify(elements);
    
    const formData = new FormData();
    formData.append('action', 'save_template');
    formData.append('name', name);
    formData.append('data', data);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.status === 'success') {
            alert('Template saved successfully!');
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => alert('Error saving template'));
}

function showTemplateModal() {
    document.getElementById('template-modal').style.display = 'flex';
    loadTemplateList();
}

function loadTemplateList() {
    const list = document.getElementById('template-list');
    list.innerHTML = 'Loading...';
    
    const formData = new FormData();
    formData.append('action', 'get_templates');
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.status === 'success') {
            if(res.data.length === 0) {
                list.innerHTML = 'No templates found.';
                return;
            }
            
            let html = '<ul class="list-group">';
            res.data.forEach(tpl => {
                html += `<li class="list-group-item" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>${tpl.name} <small class="text-muted">(${tpl.created_at})</small></span>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="loadTemplate(${tpl.id})">Load</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTemplate(${tpl.id})"><i class="fa fa-trash"></i></button>
                    </div>
                </li>`;
            });
            html += '</ul>';
            list.innerHTML = html;
        } else {
            list.innerHTML = 'Error loading templates.';
        }
    });
}

function loadTemplate(id) {
    if(!confirm('Load this template? Current changes will be lost.')) return;
    
    const formData = new FormData();
    formData.append('action', 'load_template');
    formData.append('id', id);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.status === 'success') {
            applyTemplate(JSON.parse(res.data.data));
            document.getElementById('template-modal').style.display = 'none';
        } else {
            alert('Error: ' + res.message);
        }
    });
}

function deleteTemplate(id) {
    if(!confirm('Are you sure you want to delete this template?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_template');
    formData.append('id', id);
    
    fetch('ajax_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if(res.status === 'success') {
            loadTemplateList(); 
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => alert('Error deleting template'));
}

function applyTemplate(elements) {
    const preview = document.getElementById('certificate-preview');
    const border = preview.querySelector('.cert-border');
    preview.innerHTML = '';
    if(border) preview.appendChild(border);
    else preview.innerHTML = '<div class="cert-border"></div>';
    
    elements.forEach(data => {
        const div = document.createElement('div');
        div.id = data.id;
        div.className = 'cert-element draggable';
        div.setAttribute('style', data.style);
        if(data.dataX) div.setAttribute('data-x', data.dataX);
        if(data.dataY) div.setAttribute('data-y', data.dataY);
        if(data.dataRotation) div.setAttribute('data-rotation', data.dataRotation);
        if(data.dataVariable) div.setAttribute('data-variable', data.dataVariable);
        
        // Re-inject dynamic content
        if (div.id === 'recipient-name') {
            div.innerHTML = `« ${currentUserData.fullName} »`;
            div.contentEditable = "true";
        } else if (div.id === 'ref-no') {
            div.innerHTML = `Certificate Ref No. «${currentUserData.refNo}»`;
            div.contentEditable = "true";
        } else if (div.id === 'qr-code-container') {
             div.innerHTML = '';
        } else if (data.dataVariable && typeof currentUserData !== 'undefined' && currentUserData[data.dataVariable]) {
             div.innerHTML = currentUserData[data.dataVariable];
             div.contentEditable = "true";
        } else {
            div.innerHTML = data.content;
            if (!div.querySelector('img')) {
                div.contentEditable = "true";
            }
        }
        
        preview.appendChild(div);
    });
    
    // Re-init QR Code
    const qrContainer = document.getElementById("qr-code-container");
    if(qrContainer) {
        qrContainer.innerHTML = ''; 
        new QRCode(qrContainer, {
            text: currentUserData.verificationLink,
            width: 100,
            height: 100,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
    
    saveState();
}

function openSendModal() {
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
        const overrideCheckbox = document.getElementById('use-override-email');
        const overrideInput = document.getElementById('override-email');
        if (overrideCheckbox && overrideCheckbox.checked && overrideInput && overrideInput.value) {
             formData.append('override_email', overrideInput.value);
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

function exportPDF(dpi, returnBlob = false) {
    const element = document.getElementById('certificate-preview');
    const scale = dpi / 96;
    
    document.querySelectorAll('.cert-element').forEach(el => el.style.outline = 'none');
    
    return html2canvas(element, { 
        scale: scale, 
        useCORS: true, 
        logging: false,
        allowTaint: true
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/jpeg', 0.9);
        const pdf = new jspdf.jsPDF('l', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
        
        if (returnBlob) {
            return pdf.output('blob');
        } else {
            let filename = 'Certificate.pdf';
            if(typeof currentUserData !== 'undefined' && currentUserData.refNo) {
                filename = 'Certificate_' + currentUserData.refNo + '.pdf';
            }
            pdf.save(filename);
        }
    });
}

// Event Listeners
document.getElementById('certificate-preview').addEventListener('focusout', function(e) {
    if (e.target.isContentEditable) {
        saveState();
    }
});

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
        e.preventDefault();
        undo();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
        e.preventDefault();
        redo();
    }
});

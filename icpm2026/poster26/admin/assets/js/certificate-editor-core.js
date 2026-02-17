
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
            dataVariable: el.getAttribute('data-variable'),
            dataTemplate: el.getAttribute('data-template')
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
        if(data.dataTemplate) div.setAttribute('data-template', data.dataTemplate);
        
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
            dataVariable: el.getAttribute('data-variable'),
            dataTemplate: el.getAttribute('data-template')
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
                 // Fallback for elements with data-variable but missing data-template (legacy/broken saves)
                 // We assume the content should be just the variable
                 el.innerHTML = currentUserData[variable];
                 el.setAttribute('data-template', '{' + variable + '}');
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

// Listen for PostMessages from parent (for bulk generation)
window.addEventListener('message', function(event) {
    const msg = event.data;
    if (msg.action === 'generateCertificate') {
        const uid = msg.uid;
        // In this architecture, the page is already loaded with the correct UID via GET param
        // or we need to swap data. 
        // Since we are using client-side generation, we might need to swap data if the iframe is reused.
        // But the current bulk logic in manage-users.php reloads the iframe for each user (or uses one iframe and reloads it).
        // Let's check manage-users.php logic.
        // It uses: iframe.src = 'certificate-editor.php?uid=' + uid + ...
        // So the page reloads.
        
        // However, if we want to be super efficient, we could swap data without reload.
        // For now, we just acknowledge or trigger the capture.
        
        // Wait for fonts/images
        document.fonts.ready.then(async () => {
            // Small delay for rendering
            await new Promise(r => setTimeout(r, 800)); // Increased delay for safety
            
            // Hide tools
            const toolbar = document.getElementById('editor-toolbar');
            if(toolbar) toolbar.style.display = 'none';
            document.querySelectorAll('.ui-resizable-handle').forEach(e => e.style.display = 'none');
            if(selectedElement) selectedElement.style.outline = 'none';
            
            // Capture
            // Use html2canvas
             html2canvas(document.getElementById('certificate-preview'), {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff' // Ensure white background
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 0.85); // JPEG is faster/smaller
                
                // Send back
                // We need to send PDF data or just the image.
                // The backend expects PDF data base64 encoded.
                
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4');
                const imgProps = pdf.getImageProperties(imgData);
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                
                pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
                const pdfBase64 = pdf.output('datauristring').split(',')[1];
                
                // Reply
                event.source.postMessage({
                    action: 'certificateGenerated',
                    uid: uid,
                    pdfData: pdfBase64
                }, event.origin);
                
                // Restore tools (optional)
                if(toolbar) toolbar.style.display = 'flex';
            });
        });
    }
});

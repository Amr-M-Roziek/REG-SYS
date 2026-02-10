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
    selectElement(event.currentTarget);
  });

function dragMoveListener (event) {
  var target = event.target
  var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx
  var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy

  target.style.transform = 'translate(' + x + 'px, ' + y + 'px)'
  target.setAttribute('data-x', x)
  target.setAttribute('data-y', y)
  
  const rotation = target.getAttribute('data-rotation') || 0;
  target.style.transform += ` rotate(${rotation}deg)`;
}

interact('.draggable').on('dragend', function (event) {
    saveState();
});
interact('.draggable').on('resizeend', function (event) {
    saveState();
});

// --- Positioning Functions ---
window.centerSelectedElement = function() {
    if (!selectedElement) {
        alert("Please select an element first.");
        return;
    }
    const canvasWidth = 1123;
    const elementWidth = selectedElement.offsetWidth;
    const targetLeft = (canvasWidth - elementWidth) / 2;
    selectedElement.style.left = targetLeft + 'px';
    selectedElement.setAttribute('data-x', 0);
    const y = parseFloat(selectedElement.getAttribute('data-y')) || 0;
    const rotation = selectedElement.getAttribute('data-rotation') || 0;
    selectedElement.style.transform = `translate(0px, ${y}px) rotate(${rotation}deg)`;
    saveState();
}

function resizeMoveListener (event) {
  var target = event.target
  var x = (parseFloat(target.getAttribute('data-x')) || 0)
  var y = (parseFloat(target.getAttribute('data-y')) || 0)

  target.style.width = event.rect.width + 'px'
  target.style.height = event.rect.height + 'px'

  x += event.deltaRect.left
  y += event.deltaRect.top

  target.style.transform = 'translate(' + x + 'px, ' + y + 'px)'

  target.setAttribute('data-x', x)
  target.setAttribute('data-y', y)
  
  const rotation = target.getAttribute('data-rotation') || 0;
  target.style.transform += ` rotate(${rotation}deg)`;
}

function selectElement(element) {
    if (selectedElement) {
        selectedElement.style.outline = 'none';
        selectedElement.classList.remove('selected');
    }
    
    selectedElement = element;
    selectedElement.style.outline = '2px dashed #3498db';
    selectedElement.classList.add('selected');
    
    document.getElementById('selected-element-name').innerText = element.id || 'Unnamed Element';
    
    const style = window.getComputedStyle(element);
    const fontFamily = style.fontFamily.replace(/['"]/g, '');
    const fontSelect = document.getElementById('font-family');
    for(let i=0; i<fontSelect.options.length; i++) {
        if(fontFamily.includes(fontSelect.options[i].value.replace(/['",]/g, '').split(' ')[0])) {
            fontSelect.selectedIndex = i;
            break;
        }
    }
    document.getElementById('font-size').value = parseInt(style.fontSize) || 16;
    
    const rgb = style.color.match(/\d+/g);
    if(rgb) {
        const hex = "#" + ((1 << 24) + (parseInt(rgb[0]) << 16) + (parseInt(rgb[1]) << 8) + parseInt(rgb[2])).toString(16).slice(1);
        document.getElementById('text-color').value = hex;
    }
    
    const weight = style.fontWeight;
    document.getElementById('font-weight').value = (weight === '700' || weight === 'bold') ? 'bold' : (weight === '300' ? '300' : 'normal');
    document.getElementById('letter-spacing').value = parseInt(style.letterSpacing) || 0;
    
    const lh = parseFloat(style.lineHeight) / parseFloat(style.fontSize);
    document.getElementById('line-height').value = isNaN(lh) ? 1.2 : lh.toFixed(1);
    document.getElementById('opacity').value = style.opacity || 1;
    
    const rotation = element.getAttribute('data-rotation') || 0;
    document.getElementById('rotation-range').value = rotation;
    document.getElementById('rotation-number').value = rotation;
    
    const img = element.querySelector('img');
    const replaceBtn = document.getElementById('btn-replace-img');
    if (replaceBtn) replaceBtn.style.display = img ? 'block' : 'none';
}

document.getElementById('workspace').addEventListener('click', function(e) {
    if(e.target.id === 'workspace' || e.target.id === 'certificate-preview') {
        if (selectedElement) {
            selectedElement.style.outline = 'none';
            selectedElement.classList.remove('selected');
            selectedElement = null;
            document.getElementById('selected-element-name').innerText = 'None selected';
            document.getElementById('btn-replace-img').style.display = 'none';
        }
    }
});

document.getElementById('certificate-preview').addEventListener('dblclick', function(e) {
    if (e.target.classList.contains('draggable') && !e.target.querySelector('img') && e.target.id !== 'qr-code-container') {
        e.target.contentEditable = true;
        e.target.focus();
        e.target.classList.add('editing');
    }
});

document.getElementById('certificate-preview').addEventListener('focusout', function(e) {
    if (e.target.classList.contains('draggable')) {
        e.target.contentEditable = false;
        e.target.classList.remove('editing');
        saveState();
    }
});

// --- Toolbar Functions ---

function addQRCodeElement() {
    const id = 'qr-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'cert-element draggable';
    div.style.top = '200px';
    div.style.left = '200px';
    div.style.width = '100px';
    div.style.height = '100px';
    div.setAttribute('data-variable', 'qrcode');
    
    document.getElementById('certificate-preview').appendChild(div);
    selectElement(div);
    refreshData();
    saveState();
}

function insertDataVariable() {
    const select = document.getElementById('data-variable-select');
    if (!select) return;
    const variable = select.value;
    if (!variable) return;
    
    const div = document.createElement('div');
    div.className = 'cert-element draggable';
    div.setAttribute('data-variable', variable);
    div.style.top = '200px';
    div.style.left = '200px';
    div.setAttribute('data-x', 0);
    div.setAttribute('data-y', 0);
    div.style.zIndex = '100';
    div.style.fontSize = '18px';
    div.style.fontFamily = "'Open Sans', sans-serif";
    div.contentEditable = false;
    
    let content = "{" + variable + "}";
    if (typeof currentUserData !== 'undefined') {
        if (variable === 'fullName' && currentUserData.isParticipant) {
            content = "« " + (currentUserData.fullName || '') + " »";
        } else if (currentUserData[variable] !== undefined) {
            content = currentUserData[variable];
        }
    }
    
    div.innerHTML = content;
    document.getElementById('certificate-preview').appendChild(div);
    selectElement(div);
    saveState();
    select.value = '';
}

function insertPlaceholder(val) {
    if (!selectedElement || !val) return;
    const varName = val.replace(/[{}]/g, '');
    selectedElement.setAttribute('data-variable', varName);
    refreshData();
    saveState();
}

function addTextElement() {
    const id = 'text-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'cert-element draggable';
    div.contentEditable = false;
    div.innerHTML = 'New Text';
    div.style.top = '100px';
    div.style.left = '100px';
    div.style.fontSize = '20px';
    div.style.color = '#000000';
    
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
            
            const img = document.createElement('img');
            img.onload = function() {
                const aspectRatio = img.naturalWidth / img.naturalHeight;
                const initialWidth = 200;
                const initialHeight = initialWidth / aspectRatio;
                div.style.width = initialWidth + 'px';
                div.style.height = initialHeight + 'px';
            };
            
            img.src = e.target.result;
            img.className = 'cert-logo';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            
            div.appendChild(img);
            document.getElementById('certificate-preview').appendChild(div);
            selectElement(div);
            saveState();
        }
        reader.readAsDataURL(input.files[0]);
    }
    input.value = '';
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
            if(img) {
                img.src = e.target.result;
                saveState();
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
    input.value = '';
}

function updateStyle(prop, value) {
    if (selectedElement) {
        selectedElement.style[prop] = value;
        saveState();
    }
}

function toggleStyle(prop, value) {
    if (selectedElement) {
        if (selectedElement.style[prop] === value) {
            selectedElement.style[prop] = 'normal';
        } else {
            selectedElement.style[prop] = value;
        }
        saveState();
    }
}

function updateRotation(degrees, save) {
    if (selectedElement) {
        selectedElement.setAttribute('data-rotation', degrees);
        const x = parseFloat(selectedElement.getAttribute('data-x')) || 0;
        const y = parseFloat(selectedElement.getAttribute('data-y')) || 0;
        selectedElement.style.transform = `translate(${x}px, ${y}px) rotate(${degrees}deg)`;
        document.getElementById('rotation-range').value = degrees;
        document.getElementById('rotation-number').value = degrees;
        if(save) saveState();
    }
}

function changeZIndex(change) {
    if (selectedElement) {
        const currentZ = parseInt(window.getComputedStyle(selectedElement).zIndex) || 0;
        selectedElement.style.zIndex = currentZ + change;
        saveState();
    }
}

function deleteSelectedElement() {
    if (selectedElement) {
        selectedElement.remove();
        selectedElement = null;
        document.getElementById('selected-element-name').innerText = 'None selected';
        saveState();
    }
}

function resetTemplate() {
    if(confirm('Are you sure you want to reset all changes?')) {
        loadDefaultTemplate();
    }
}

function refreshData() {
    if(typeof currentUserData !== 'undefined') {
        const recipientName = document.getElementById('recipient-name');
        if(recipientName) {
            if (currentUserData.isParticipant) {
                recipientName.innerText = "« " + currentUserData.fullName + " »";
            } else {
                recipientName.innerText = currentUserData.fullName;
            }
        }
        
        const refNo = document.getElementById('ref-no');
        if(refNo) refNo.innerText = "Ref No. " + currentUserData.refNo;
        
        document.querySelectorAll('.cert-element[data-variable]').forEach(el => {
            const variable = el.getAttribute('data-variable');
            if (variable && variable !== 'qrcode') {
                let content = '';
                if (variable === 'fullName' && currentUserData.isParticipant) {
                    content = "« " + (currentUserData.fullName || '') + " »";
                } else if (currentUserData[variable] !== undefined) {
                    content = currentUserData[variable];
                } else {
                    content = "";
                }
                const template = el.getAttribute('data-template');
                if (template) {
                     el.innerHTML = template.replace('{' + variable + '}', content);
                } else {
                     el.innerText = content;
                }
            }
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
        
        saveState();
    }
}

// --- Undo/Redo System ---

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
            dataVariable: el.getAttribute('data-variable')
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
        div.innerHTML = data.content;
        
        if (data.dataVariable && data.content.includes('{')) {
             div.setAttribute('data-template', data.content);
        }
        
        if (!div.querySelector('img') && div.id !== 'qr-code-container') {
             div.contentEditable = "false";
        }
        
        preview.appendChild(div);
    });
    
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
    modal.style.display = 'flex';
    list.innerHTML = 'Loading...';
    
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
                list.innerHTML = '<p>No templates found.</p>';
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
            list.innerHTML = html;
        } else {
            list.innerHTML = '<p class="text-danger">Error loading templates.</p>';
        }
    })
    .catch(error => {
        list.innerHTML = '<p class="text-danger">Connection error.</p>';
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
            refreshData();
            saveState();
            document.getElementById('template-modal').style.display = 'none';
        } else {
            alert('Error loading template: ' + data.message);
        }
    })
    .catch(error => {
        alert('Connection error.');
    });
}

async function exportPDF(dpi) {
    const element = document.getElementById('certificate-preview');
    if (selectedElement) {
        selectedElement.style.outline = 'none';
    }
    
    const scale = dpi === 300 ? 3.125 : 1;
    
    try {
        const canvas = await html2canvas(element, {
            scale: scale,
            useCORS: true,
            allowTaint: true,
            logging: false
        });
        
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        const pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
        pdf.addImage(imgData, 'JPEG', 0, 0, 297, 210);
        pdf.save(`Certificate_${currentUserData.refNo}.pdf`);
        
    } catch (e) {
        console.error("PDF Export Error:", e);
        alert("Error generating PDF. Please check console.");
    }
    
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
    
    if(modal) modal.style.display = 'none';
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(bd => bd.style.display = 'none');
    
    await new Promise(r => setTimeout(r, 500));
    await document.fonts.ready;
    
    try {
        const element = document.getElementById('certificate-preview');
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
        
        const pdfDataUri = pdf.output('datauristring');
        const pdfBase64 = pdfDataUri.split(',')[1];
        
        const formData = new FormData();
        formData.append('action', 'send_certificate');
        
        let targetUid = uid;
        if (!targetUid && typeof currentUid !== 'undefined') targetUid = currentUid;
        if (!targetUid && typeof currentUserData !== 'undefined') targetUid = currentUserData.refNo;
        
        formData.append('uid', targetUid);
        formData.append('pdf_data', pdfBase64);

        const response = await fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const res = await response.json();
        
        if(res.status === 'success') {
             if(statusDiv) statusDiv.innerHTML = '<div class="alert alert-success">Certificate sent successfully!</div>';
             
             setTimeout(() => {
                 if(statusDiv) statusDiv.style.display = 'none';
                 if(btn) {
                     btn.disabled = false;
                     btn.innerHTML = 'Send PDF';
                 }
             }, 2000);

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

// --- UI Helper Functions (Added) ---

function toggleSidebar() {
    const toolbar = document.getElementById('editor-toolbar');
    const workspace = document.getElementById('workspace');
    
    if (toolbar.style.display === 'none') {
        toolbar.style.display = 'block';
        workspace.style.left = '320px'; 
        workspace.style.width = 'calc(100% - 320px)';
    } else {
        toolbar.style.display = 'none';
        workspace.style.left = '0';
        workspace.style.width = '100%';
    }
    setTimeout(resizeWorkspace, 50);
}

function loadDefaultTemplate() {
    location.reload();
}

function resizeWorkspace() {
    const workspace = document.getElementById('workspace');
    const preview = document.getElementById('certificate-preview');
    if (!workspace || !preview) return;

    preview.style.transform = 'none';
    preview.style.margin = '0';

    const targetWidth = 1123;
    const targetHeight = 794;
    
    const availableWidth = workspace.clientWidth - 40; 
    const availableHeight = workspace.clientHeight - 40;
    
    const scaleX = availableWidth / targetWidth;
    const scaleY = availableHeight / targetHeight;
    
    let scale = Math.min(scaleX, scaleY);
    if (scale > 1) scale = 1;
    
    const scaledWidth = targetWidth * scale;
    const scaledHeight = targetHeight * scale;
    
    const x = (availableWidth - scaledWidth) / 2;
    const y = (availableHeight - scaledHeight) / 2;
    
    preview.style.transformOrigin = 'top left';
    preview.style.transform = `translate(${x}px, ${y}px) scale(${scale})`;
}

function applyFinalCMEFix() {
    if(!confirm("This will reset the layout to the optimized 'Final-CME' standard. Any unsaved changes will be lost. Continue?")) return;

    const elements = [
        {
            id: 'logo-left',
            style: 'top: 50px; left: 60px; width: 150px; height: auto;',
            content: '<img src="../../admin/assets/img/icpm-gold-seal.png" class="cert-logo" alt="Logo 1">',
            dataX: '0', dataY: '0'
        },
        {
            id: 'logo-right',
            style: 'top: 50px; left: 913px; width: 150px; height: auto;',
            content: '<img src="../../admin/assets/img/icpm-gold-seal.png" class="cert-logo" alt="Logo 2">',
            dataX: '0', dataY: '0'
        },
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
        {
            id: 'awarded-to',
            style: 'top: 250px; left: 0px; width: 1123px; text-align: center; font-size: 18px; color: #333;',
            content: 'This Certificate is awarded to',
            dataX: '0', dataY: '0'
        },
        {
            id: 'recipient-name',
            style: 'top: 290px; left: 0px; width: 1123px; text-align: center; font-size: 42px; font-weight: bold; font-family: \'Times New Roman\', serif; color: #000;',
            content: 'Participant Name',
            dataVariable: 'fullName',
            dataX: '0', dataY: '0'
        },
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
        {
            id: 'sig-left',
            style: 'top: 660px; left: 100px; width: 250px; text-align: center; font-size: 14px;',
            content: '<strong>Prof. Omer Eladil Abdalla Hamid</strong><br>RAKMHSU',
            dataX: '0', dataY: '0'
        },
        {
            id: 'sig-center',
            style: 'top: 650px; left: 521px; width: 80px; opacity: 0.8;',
            content: '<img src="../../images/icpm-logo.png" style="width: 80px; opacity: 0.8;" alt="Stamp">',
            dataX: '0', dataY: '0'
        },
        {
            id: 'icpm-stamp-right',
            style: 'top: 620px; left: 800px; width: 170px; height: auto; z-index: 1;',
            content: '<img src="../../admin/assets/img/icpm-stamp-blue.png" alt="ICPM Stamp" style="width: 170px; height: auto;">',
            dataX: '0', dataY: '0'
        },
        {
            id: 'sig-right-img',
            style: 'top: 650px; left: 810px; width: 150px; height: auto; z-index: 2;',
            content: '<img src="../../admin/assets/img/dr-muneer-signature.png" alt="Signature" style="width: 150px; height: auto; pointer-events: none;">',
            dataX: '0', dataY: '0'
        },
        {
            id: 'sig-right-text',
            style: 'top: 730px; left: 800px; width: 200px; text-align: center; font-size: 12px; font-family: \'Times New Roman\', serif; z-index: 3;',
            content: '<strong>Dr. Muneer Rayan</strong><br>ICPM',
            dataX: '0', dataY: '0'
        },
        {
            id: 'qr-code-container',
            style: 'top: 680px; left: 50px; width: 80px; height: 80px;',
            content: '', 
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

// Initial Setup & Window Events
window.addEventListener('resize', resizeWorkspace);
window.addEventListener('load', async function() {
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
    
    // Auto-collapse sidebar on small screens
    if (window.self !== window.top && window.innerWidth < 1000) {
         const sidebar = document.getElementById('editor-toolbar'); // Used correct ID
         const workspace = document.getElementById('workspace');
         if (sidebar && workspace) {
             sidebar.style.display = 'none';
             workspace.style.left = '0';
             workspace.style.width = '100%';
         }
    }
    
    resizeWorkspace();
    setTimeout(resizeWorkspace, 100);

    // Template Loading / Autogen Logic
    const urlParams = new URLSearchParams(window.location.search);
    const autogen = urlParams.get('autogen');
    const templateId = urlParams.get('template_id');

    if (autogen === 'true') {
        console.log("Autogen started for UID: " + currentUserData.refNo);
        const overlay = document.createElement('div');
        Object.assign(overlay.style, {
            position: 'fixed', top: '0', left: '0', width: '100%', height: '100%',
            backgroundColor: 'rgba(255,255,255,0.9)', zIndex: '9999',
            display: 'flex', flexDirection: 'column', justifyContent: 'center', alignItems: 'center'
        });
        overlay.innerHTML = '<h2 style="color:#333"><i class="fa fa-cog fa-spin"></i> Generating Certificate...</h2><p>Please wait, do not close this window.</p>';
        document.body.appendChild(overlay);
    }

    try {
        let loaded = false;

        if (templateId) {
            const formData = new FormData();
            formData.append('action', 'load_template');
            formData.append('id', templateId);
            
            const res = await fetch('ajax_handler.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                console.log("Loaded template ID: " + templateId);
                const elements = JSON.parse(data.data.data);
                applyState(elements);
                loaded = true;
            } else {
                console.error('Template load failed: ' + data.message);
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
                    applyState(elements);
                    loaded = true;
                } else {
                    console.log("No default template found, using hardcoded layout.");
                }
            } catch (e) {
                console.error("Error loading default template", e);
            }
        }
        
        saveState();
        
        // Autogen Trigger
        if (autogen === 'true') {
            setTimeout(() => {
                sendCertificate(currentUserData.refNo, true);
            }, 1000);
        }

    } catch (e) {
        console.error("Initialization error:", e);
    }
});

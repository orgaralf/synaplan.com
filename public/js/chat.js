// Anonymous widget mode detection (set in c_chat.php)
// When true, restricts functionality for anonymous widget users:
// - File uploads limited to JPG, GIF, PNG, PDF
// - Microphone functionality disabled
// - Chat history loading disabled
// - Rate limiting applied
const isAnonymousWidget = typeof window.isAnonymousWidget !== 'undefined' ? window.isAnonymousWidget : false;

// Keep track of user-attached files (pasted or manually selected).
let attachedFiles = [];

// References
const attachButton  = document.getElementById('attachButton');
const filesDiv      = document.getElementById('filesDiv');
const closeFilesDiv = document.getElementById('closeFilesDiv');
const fileInput     = document.getElementById('fileInput');
const manualFileSelect = document.getElementById('manualFileSelect');
const filePreview   = document.getElementById('filePreview');
const messageInput  = document.getElementById('messageInput');
const sendButton    = document.getElementById('sendButton');
const sendButtonMobile = document.getElementById('sendButtonMobile');
let aiTextBuffer = [];
// ------------------------------------------------------------
const loaders = new Map();

// File type validation for anonymous users
function isFileTypeAllowed(file) {
    if (!isAnonymousWidget) {
        return true; // All file types allowed for authenticated users
    }
    
    // Anonymous users can only upload: JPG, GIF, PNG, PDF
    const allowedTypes = [
        'image/jpeg',
        'image/jpg', 
        'image/gif',
        'image/png',
        'application/pdf'
    ];
    
    const allowedExtensions = ['.jpg', '.jpeg', '.gif', '.png', '.pdf'];
    
    // Check MIME type
    if (allowedTypes.includes(file.type)) {
        return true;
    }
    
    // Check file extension as fallback
    const fileName = file.name.toLowerCase();
    return allowedExtensions.some(ext => fileName.endsWith(ext));
}

function startWaitingLoader(parentId) {
    //console.log('startWaitingLoader', parentId);
    const parent = document.getElementById(parentId);
    if (!parent) return;
    const loader = document.createElement('div');
    parent.appendChild(loader);
    loaders.set(parentId, loader);
    loader.className = 'rotating-loader';
}

function stopWaitingLoader(parentId) {
    //console.log('stopWaitingLoader', parentId);
    const parent = document.getElementById(parentId);
    const loader = loaders.get(parentId);
    if (parent && loader && parent.contains(loader)) {
        parent.removeChild(loader);
        loaders.delete(parentId);
    } else if (loader) {
        // If loader exists but is not a child of parent, just clean up the reference
        loaders.delete(parentId);
    }
}

// Function to reset the file upload section completely
function resetFileUploadSection() {
    // Clear the attached files array
    attachedFiles.length = 0;
    
    // Clear the file input value to allow re-uploading the same file
    if (fileInput) {
        fileInput.value = '';
    }
    
    // Update the file preview to clear it
    updateFilePreview();
    
    // Hide the files div
    if (filesDiv) {
        filesDiv.style.display = 'none';
        filesDiv.classList.remove('active');
    }
    
    // Stop any loading indicators
    stopLoading();
}

// Initialize event listeners only if elements exist
function initializeEventListeners() {
    // Show/hide the attach area
    if (attachButton) {
        attachButton.addEventListener('click', () => {
            if (filesDiv) {
                filesDiv.style.display = 'block';
                filesDiv.classList.add('active');
                startLoading();
                if (fileInput) fileInput.click();
            }
        });
    }
    
    if (closeFilesDiv) {
        closeFilesDiv.addEventListener('click', () => {
            if (filesDiv) {
                filesDiv.style.display = 'none';
                filesDiv.classList.remove('active');
            }
        });
    }

    // Clicking "Select Files" triggers the hidden file input
    if (manualFileSelect) {
        manualFileSelect.addEventListener('click', () => {
            if (fileInput) fileInput.click();
        });
    }

    // 1) Manually selected files
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            for (const file of fileInput.files) {
                if (isFileTypeAllowed(file)) {
                    attachedFiles.push(file);
                } else {
                    // Show error for disallowed file types
                    if (isAnonymousWidget) {
                        alert('Anonymous users can only upload JPG, GIF, PNG, and PDF files.');
                    }
                }
            }
            updateFilePreview();
        });
    }

    // 2) Pasting images/documents into the contenteditable
    if (messageInput) {
        messageInput.addEventListener('paste', (event) => {
            const items = event.clipboardData?.items || [];
            let didPasteFile = false;

            for (const item of items) {
                if (item.kind === 'file') {
                    const file = item.getAsFile();
                    if (file) {
                        if (isFileTypeAllowed(file)) {
                            attachedFiles.push(file);
                            didPasteFile = true;
                        } else {
                            // Show error for disallowed file types
                            if (isAnonymousWidget) {
                                alert('Anonymous users can only upload JPG, GIF, PNG, and PDF files.');
                            }
                        }
                    }
                }
            }
            
            // If we got any files, prevent them from appearing as raw text
            // and update the file preview UI.
            if (didPasteFile) {
                event.preventDefault();
                updateFilePreview();
            } else {
                // Handle text paste - strip color styling
                event.preventDefault();
                
                // Get plain text from clipboard
                const text = event.clipboardData.getData('text/plain');
                
                // Insert text at cursor position
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(document.createTextNode(text));
                    
                    // Move cursor to end of inserted text
                    range.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } else {
                    // Fallback: append to end
                    messageInput.textContent += text;
                }
            }
        });
        
        messageInput.addEventListener('keydown', function(e) {
            // Check if Enter is pressed along with either CTRL or ALT key
            if (e.key === 'Enter' && (e.ctrlKey || e.altKey)) {
                e.preventDefault();
                handleSendMessage();
            }
        });
        
        // Arrow key handling for message history
        messageInput.addEventListener("keydown", function (e) {
            if (e.key === "ArrowUp") {
                e.preventDefault();
                if (messageHistory.length > 0) {
                    if (historyIndex === -1) {
                        historyIndex = messageHistory.length - 1;
                    } else if (historyIndex > 0) {
                        historyIndex--;
                    }
                    setMessageInput(messageHistory[historyIndex]);
                }
            } else if (e.key === "ArrowDown") {
                e.preventDefault();
                if (historyIndex >= 0 && historyIndex < messageHistory.length - 1) {
                    historyIndex++;
                    setMessageInput(messageHistory[historyIndex]);
                } else {
                    historyIndex = -1;
                    setMessageInput('');
                }
            }
        });
    }
}


const loader = document.getElementById('loader');

function startLoading() {
  if (loader) {
    loader.classList.remove('hidden');
  }
}

function stopLoading() {
  if (loader) {
    loader.classList.add('hidden');
  }
}
// Enhanced file preview with modern grid layout
function updateFilePreview() {
  if (!filePreview) return;
  
  filePreview.innerHTML = '';

  if (attachedFiles.length > 0) {
    attachedFiles.forEach((file, index) => {
      const fileCard = document.createElement('div');
      fileCard.className = 'file-preview-card';
      
      // File type icon and preview
      const previewSection = document.createElement('div');
      previewSection.className = 'file-preview-section';

      if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'file-preview-image';
        img.onload = () => URL.revokeObjectURL(img.src);
        previewSection.appendChild(img);
      } else {
        // File type icon
        const iconElement = document.createElement('i');
        let iconClass = 'fas fa-file file-preview-icon default';
        let iconColorClass = '';
        
        if (file.type.includes('pdf')) {
          iconClass = 'fas fa-file-pdf file-preview-icon pdf';
        } else if (file.type.includes('word') || file.name.endsWith('.docx')) {
          iconClass = 'fas fa-file-word file-preview-icon word';
        } else if (file.type.includes('audio') || file.name.endsWith('.mp3')) {
          iconClass = 'fas fa-file-audio file-preview-icon audio';
        } else if (file.type.includes('video') || file.name.endsWith('.mp4')) {
          iconClass = 'fas fa-file-video file-preview-icon video';
        } else if (file.type.includes('text') || file.name.endsWith('.txt')) {
          iconClass = 'fas fa-file-alt file-preview-icon text';
        }
        
        iconElement.className = iconClass;
        previewSection.appendChild(iconElement);
      }

      // Remove button
      const removeBtn = document.createElement('button');
      removeBtn.innerHTML = '<i class="fas fa-times"></i>';
      removeBtn.className = 'file-remove-btn';
      removeBtn.onclick = () => {
        attachedFiles.splice(index, 1);
        updateFilePreview();
              if (attachedFiles.length === 0) {
        if (filesDiv) {
            filesDiv.style.display = 'none';
            filesDiv.classList.remove('active');
        }
      }
      };

      // File info
      const fileInfo = document.createElement('div');
      fileInfo.className = 'file-info';
      
      const fileName = document.createElement('div');
      fileName.textContent = file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name;
      fileName.className = 'file-name';
      
      const fileSize = document.createElement('div');
      fileSize.textContent = `${(file.size / 1024).toFixed(1)} KB`;
      fileSize.className = 'file-size';

      fileInfo.appendChild(fileName);
      fileInfo.appendChild(fileSize);
      
      fileCard.appendChild(previewSection);
      fileCard.appendChild(removeBtn);
      fileCard.appendChild(fileInfo);
      
      filePreview.appendChild(fileCard);
    });
    
    if (filesDiv) {
        filesDiv.style.display = 'block';
        filesDiv.classList.add('active');
    }
    stopLoading();
  } else {
    if (filesDiv) {
        filesDiv.style.display = 'none';
        filesDiv.classList.remove('active');
    }
  }
}
// history handling in the input

const messageHistory = [];
let historyIndex = -1;

function setMessageInput(text) {
    if (!messageInput) return;
    messageInput.textContent = text;
    placeCaretAtEnd(messageInput);
}

function placeCaretAtEnd(el) {
    const range = document.createRange();
    const sel = window.getSelection();
    range.selectNodeContents(el);
    range.collapse(false);
    sel.removeAllRanges();
    sel.addRange(range);
}



// 3) Sending logic (front-end demonstration)
function handleSendMessage() {
    // Grab the user's text - use textContent instead of innerText to preserve actual newlines
    if (!messageInput) return;
    const userMessage = messageInput.textContent.trim();
    const actionMessage = 'messageNew';
    
    // Debug: Log the message to verify newlines are preserved
    console.log('Sending message:', JSON.stringify(userMessage));
    console.log('User message (raw):', userMessage, Array.from(userMessage));
    
    // Get the selected prompt configuration
    const selectedPromptId = document.getElementById('promptConfigSelect')?.value || 'general';
    
    // Example: Show everything in console
    // console.log('Message text:', userMessage);
    //console.log('Attached files:', attachedFiles);
    if (userMessage) {
        messageHistory.push(userMessage);
        if (messageHistory.length > 10) messageHistory.shift(); // Keep last 10
    }
    historyIndex = -1;

    // Real-world usage: create FormData, append files, send via fetch/AJAX
    let formData = new FormData();
    formData.append('message', userMessage);
    formData.append('action', actionMessage);
    formData.append('promptId', selectedPromptId);
    
    attachedFiles.forEach(file => {
      formData.append('files[]', file, file.name);
    });

    fetch('api.php', {
      method: 'POST',
      body: formData
    })
    .then(res => {
      if (!res.ok) {
        if (res.status === 401) {
          throw new Error('Authentication required. Please refresh the page.');
        } else if (res.status === 429) {
          throw new Error('Rate limit exceeded. Please wait a moment before sending another message.');
        } else {
          throw new Error('Network response was not ok');
        }
      }
      return res.json();
    })
    .then(data => {
      console.log('Server response:', data);
      if (data.error) {
        // Handle API errors
        if (data.error.includes('Rate limit exceeded')) {
          alert('Rate limit exceeded. Please wait a moment before sending another message.');
        } else if (data.error.includes('Invalid anonymous widget session')) {
          alert('Session expired. Please refresh the page.');
        } else {
          alert('Error: ' + data.error);
        }
        return;
      }
      if (data.success) {
        // Clear out the form for demonstration
        data.message = data.message.replace(/\\\"/g, '"');
        
        // Extract file count from server response
        let fileCount = 0;
        if (data.fileCount !== undefined) {
            fileCount = data.fileCount;
        } else if (data.message && data.message.includes('(+ ') && data.message.includes(' files)')) {
            // Fallback: Extract file count from message like "message text<br><small>(+ 3 files)</small>"
            const match = data.message.match(/\(\+ (\d+) files?\)/);
            if (match) {
                fileCount = parseInt(match[1]);
            }
        }
        
        // Add user message with exact same styling as PHP-rendered messages
        let fileAttachmentHtml = '';
        if (fileCount > 0) {
            fileAttachmentHtml = `
                <div class="file-attachment-header" onclick="showMessageFiles(${data.lastIds[0]})">
                    <i class="fas fa-paperclip paperclip-icon"></i>
                    <span>${fileCount} file${fileCount > 1 ? 's' : ''} attached</span>
                    <i class="fas fa-chevron-down chevron-icon"></i>
                </div>
                <div id="files-${data.lastIds[0]}" class="message-files" style="display: none;">
                    <!-- File details will be loaded here -->
                </div>
            `;
        }
        
        // Clean up the message text to remove the file count info since we're showing it separately
        let cleanMessage = data.message;
        if (fileCount > 0) {
          cleanMessage = data.message.replace(/<br>\s*<small>\(\+ \d+ files?\)<\/small>/g, '');
        }
        
        // Convert literal \n to real newlines first
        cleanMessage = cleanMessage.replace(/\\r\\n/g, '\n').replace(/\\n/g, '\n').replace(/\\r/g, '');
        // Then convert newlines to <br>
        cleanMessage = cleanMessage.replace(/\n/g, '<br>');
        
        $("#chatHistory").append(`
          <li class="message-item user-message">
            <div class="message-bubble user-bubble">
              <p>${cleanMessage}</p>
              ${fileAttachmentHtml}
              <span class="message-time user-time">${data.time}</span>
            </div>
            <div class="user-avatar">
              <img src="api.php?action=getUserAvatar&t=${Date.now()}" class="rounded-circle" width="32" height="32" alt="User" onerror="this.src='up/avatars/default.png'">
            </div>
          </li>
        `);
        
        // now start the sse stream and show the server messages
        if(Array.isArray(data.lastIds)) {
          let AItextBlock = `START_${data.lastIds[0]}`;
          $("#chatHistory").append(`
            <li class="message-item ai-message" data-streaming-id="${data.lastIds[0]}">
              <div class="ai-avatar" id="avatar-${data.lastIds[0]}">
                <i class="fas fa-robot text-white"></i>
              </div>
              <div class="message-content">
                <span id="system${AItextBlock}" class="system-message"></span>
                <div class="message-bubble ai-bubble">
                  <div id="${AItextBlock}" class="message-content"></div>
                  <div class="card-footer bg-light border-0 mt-2" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-secondary">${data.time}</span>
                        <span class="badge bg-success model-tag-placeholder">Antwort von ...</span>
                      </div>
                      <div class="d-flex gap-1">
                        <button class="btn btn-outline-secondary btn-sm copy-btn" title="Text kopieren" onclick="copyMessageText('${AItextBlock}')">
                          <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-success btn-sm again-btn" title="${getTranslation('again_button_tooltip')}">
                          <i class="fas fa-redo"></i>
                          <span class="d-none d-md-inline">Again mit <span class="next-model-name">...</span></span>
                        </button>
                        <div class="dropdown">
                          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" onclick="toggleModelDropdown('temp')">
                            <i class="fas fa-chevron-down"></i>
                          </button>
                          <ul class="dropdown-menu" id="model-dropdown-temp">
                            <li><span class="dropdown-item-text text-center">
                              <i class="fas fa-spinner fa-spin"></i> ${getTranslation('loading_models')}
                            </span></li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          `);
          startWaitingLoader(AItextBlock);
          sseStream(data, AItextBlock);
          $("#chatModalBody").scrollTop( $("#chatModalBody").prop("scrollHeight") );
        }
        
        // Reset the file upload section only on successful upload
        resetFileUploadSection();
      }
    })
    .catch(err => {
      console.error(err);
      // Handle specific error messages for anonymous users
      if (isAnonymousWidget) {
        if (err.message.includes('Authentication required')) {
          alert('Session expired. Please refresh the page to continue.');
        } else if (err.message.includes('Rate limit exceeded')) {
          alert('Rate limit exceeded. Please wait a moment before sending another message.');
        } else {
          alert('Connection error. Please try again.');
        }
      } else {
        alert('Error: ' + err.message);
      }
      // Don't reset file upload section on error - let user retry with same files
    });
    
    // Clear out the form for demonstration
    messageInput.textContent = '';
    // Note: File upload section is now reset only on success via resetFileUploadSection()
}

// Add event listeners for both send buttons
if (sendButton) {
    sendButton.addEventListener('click', handleSendMessage);
}
if (sendButtonMobile) {
    sendButtonMobile.addEventListener('click', handleSendMessage);
}

// Initialize all event listeners
initializeEventListeners();

// ------------------------------------------------------------
function sseStream(data, outputObject) {
  aiTextBuffer[outputObject] = '';
  const ids = data.lastIds.join(',');
  const selectedPromptId = document.getElementById('promptConfigSelect')?.value || 'general';
  const eventSource = new EventSource(`api.php?action=chatStream&lastIds=${ids}&promptId=${selectedPromptId}`);

  eventSource.onmessage = function(event) {
    console.log('SSE message:', event.data);
    const eventMessage = JSON.parse(event.data);
    
    if(eventMessage.status == 'ai_processing') {
      //stopLoading(outputObject);
      if(!eventMessage.message.indexOf('<loading>')) {
        stopWaitingLoader(outputObject);
      }
      outMessage = eventMessage.message.replace(/\\\"/g, '"');
      aiTextBuffer[outputObject] += outMessage;
      $("#" + outputObject).html(aiTextBuffer[outputObject]);
      $("#chatModalBody").scrollTop( $("#chatModalBody").prop("scrollHeight") );
      //console.log('Processing:', eventMessage.step);
    }

    if(eventMessage.status == 'pre_processing') {
      if(eventMessage.message == 'status.show') {
          // later useage
      } else if(eventMessage.message == 'status.hide') {
          // later useage        
      } else {
        // Use proper DOM manipulation to avoid HTML escaping
        const systemElement = document.getElementById(`system${outputObject}`);
        if (systemElement) {
          const messageSpan = document.createElement('span');
          messageSpan.textContent = eventMessage.message;
          systemElement.appendChild(messageSpan);
        }
      }
      //console.log('Processing:', eventMessage.step);
    }

    if(eventMessage.status == 'done') {
      stopWaitingLoader(outputObject);
      eventSource.close(); // Optional
      aiRender(outputObject);
      
      // Setup Again button with persistierte DB-BID after streaming is complete
      setupAgainButtonAfterStreaming(outputObject, eventMessage);
    }
  };

  // ------------------------------------------------------------
  eventSource.onerror = function(error, outputObject) {
    console.error('SSE error:', error);
    eventSource.close(); // Optional
    stopWaitingLoader(outputObject);
    // JUST IN CASE
    aiRender(outputObject);
  };  
}

// function AI RENDER
function aiRender(targetId) {
  if (typeof window.md !== 'undefined') {
    mdText = window.md.render(aiTextBuffer[targetId]) + "<br>";
    $("#"+targetId).html(mdText);
  } else {
    // Fallback if markdown-it is not available
    mdText = aiTextBuffer[targetId].replace(/\n/g, '<br>');
    $("#ai_processing").html(mdText);
  }
  aiTextBuffer[targetId] = '';
}

// Global model cache (shared with chathistory.js)
if (typeof selectableModels === 'undefined') {
  var selectableModels = null;
}
if (typeof selectedModelOverrides === 'undefined') {
  var selectedModelOverrides = {};
}

// Simple translation function (duplicate from chathistory.js for consistency)
function getTranslation(key) {
    const translations = {
        'again_button_tooltip': 'Nochmal versuchen',
        'again_generating': 'Neue Antwort wird generiert...',
        'again_error': 'Fehler beim erneuten Versuch',
        'again_network_error': 'Netzwerkfehler. Bitte versuchen Sie es erneut.',
        'again_locked': 'Ein anderer Retry läuft bereits für diese Nachricht',
        'answer_from': 'Antwort von'
    };
    return translations[key] || key;
}

// Setup Again button after streaming with persistierte DB-BID
function setupAgainButtonAfterStreaming(outputObject, eventMessage) {
  // Extract streaming ID from outputObject (format: "START_123")
  const streamingId = outputObject.replace('START_', '');
  const messageElement = document.querySelector(`li[data-streaming-id="${streamingId}"]`);
  
  if (messageElement && eventMessage.aiResponseId) {
    // Use the AI Response ID (not the user message ID)
    const aiResponseBID = eventMessage.aiResponseId;
    messageElement.setAttribute('data-message-id', aiResponseBID);
    
    console.log('Setting up Again button for AI Response ID:', aiResponseBID);
    
    // Setup Again button with correct message ID
    const againBtn = messageElement.querySelector('.again-btn');
    const dropdownBtn = messageElement.querySelector('.dropdown-toggle');
    const dropdown = messageElement.querySelector('.dropdown-menu');
    
    if (againBtn && dropdownBtn && dropdown) {
      againBtn.setAttribute('data-message-id', aiResponseBID);
      againBtn.setAttribute('onclick', `window.handleAgainRequest(${aiResponseBID})`);
      
      dropdownBtn.setAttribute('data-message-id', aiResponseBID);
      dropdownBtn.setAttribute('onclick', `toggleModelDropdown(${aiResponseBID})`);
      
      dropdown.id = `model-dropdown-${aiResponseBID}`;
      
      // Show action bar
      const actionBar = messageElement.querySelector('.card-footer');
      if (actionBar) {
        actionBar.style.display = 'block';
      }
      
      // Add model tag and load models immediately
      const tagsContainer = messageElement.querySelector('.d-flex.flex-wrap');
      if (tagsContainer && eventMessage.aiModel) {
        console.log('Adding model tag for:', eventMessage.aiModel);
        
        // Load models first, then update UI
        fetch('api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=getSelectableModels'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success && data.models) {
            selectableModels = data.models;
            console.log('Loaded models:', data.models);
            
            // Find current model display name
            console.log('Looking for model with provider:', eventMessage.aiModel);
            console.log('Available models:', data.models.map(m => m.provider));
            const currentModel = data.models.find(m => m.provider === eventMessage.aiModel);
            
            // Use the model's actual name from database, not just provider
            let modelDisplayName = eventMessage.aiModel;
            if (currentModel && currentModel.tag && currentModel.tag !== 'chat') {
                modelDisplayName = currentModel.tag;
            }
            
            console.log('Found model:', currentModel);
            console.log('Display name:', modelDisplayName);
            
            // Replace placeholder with actual model tag
            const placeholder = messageElement.querySelector('.model-tag-placeholder');
            if (placeholder) {
              placeholder.textContent = `Antwort von ${modelDisplayName}`;
              placeholder.classList.remove('model-tag-placeholder');
            }
            
            // Update avatar with service icon
            const avatar = messageElement.querySelector('.ai-avatar');
            if (avatar && eventMessage.aiService) {
              avatar.innerHTML = getAIIcon(eventMessage.aiService);
              const cleanService = eventMessage.aiService.replace('AI', '').toLowerCase();
              avatar.className = `ai-avatar ai-avatar-${cleanService}`;
            }
            
            // Load next model for button
            loadNextModelForButton(aiResponseBID);
          }
        })
        .catch(error => {
          console.error('Failed to load models:', error);
          // Fallback with raw model name
          const placeholder = messageElement.querySelector('.model-tag-placeholder');
          if (placeholder) {
            placeholder.textContent = `Antwort von ${eventMessage.aiModel}`;
            placeholder.classList.remove('model-tag-placeholder');
          }
        });
      }
    }
  }
}

// Load next model and update button label
function loadNextModelForButton(messageId) {
  // Get predicted next model from backend
  fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `action=getNextModel&messageId=${messageId}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.next_model) {
      const nextModelName = data.next_model.tag;
      
      const button = document.querySelector(`button.again-btn[data-message-id="${messageId}"]`);
      if (button) {
        const btnText = button.querySelector('.d-none.d-md-inline');
        if (btnText) {
          btnText.innerHTML = `Again mit ${nextModelName}`;
        }
      }
    } else {
      // Fallback
      const button = document.querySelector(`button.again-btn[data-message-id="${messageId}"]`);
      if (button) {
        const btnText = button.querySelector('.d-none.d-md-inline');
        if (btnText) {
          btnText.innerHTML = 'Again';
        }
      }
    }
  })
  .catch(error => {
    console.error('Failed to load next model:', error);
  });
}



// Handle Again request (delegate to chathistory.js if available)
function handleAgainRequest(messageId, overrideModelBid = null) {
  // Use chathistory.js function if available
  if (typeof window.handleAgainRequest === 'function' && window.handleAgainRequest !== handleAgainRequest) {
    return window.handleAgainRequest(messageId, overrideModelBid);
  }
  
  // Prevent double-clicks
  const button = document.querySelector(`button[data-message-id="${messageId}"]`);
  if (button.disabled) {
    return;
  }
  
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  
  // Send Again request to API
  fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `action=againMessage&messageId=${messageId}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Mark original message as "agained"
      const messageElement = document.querySelector(`li[data-message-id="${messageId}"]`);
      if (messageElement) {
        messageElement.classList.add('message-agained');
        // Remove the again button
        const againBtn = messageElement.querySelector('.again-btn');
        if (againBtn) {
          againBtn.remove();
        }
      }
      
      // Show success message briefly
      showAgainStatus(getTranslation('again_generating'), 'success');
      
      // Scroll to bottom to see new message
      setTimeout(() => {
        $("#chatModalBody").scrollTop( $("#chatModalBody").prop("scrollHeight") );
      }, 1000);
      
    } else {
      // Show specific error message based on error code
      let errorMessage = data.error || getTranslation('again_error');
      if (data.error_code === 'LOCKED') {
        errorMessage = getTranslation('again_locked');
      }
      
      showAgainStatus(errorMessage, 'error');
      
      // Re-enable button
      button.disabled = false;
      button.innerHTML = '<i class="fas fa-redo"></i>';
    }
  })
  .catch(error => {
    console.error('Again request failed:', error);
    showAgainStatus(getTranslation('again_network_error'), 'error');
    
    // Re-enable button
    button.disabled = false;
    button.innerHTML = '<i class="fas fa-redo"></i>';
  });
}

// Show status message for Again operations
function showAgainStatus(message, type) {
  // Create or update status element
  let statusEl = document.getElementById('again-status');
  if (!statusEl) {
    statusEl = document.createElement('div');
    statusEl.id = 'again-status';
    statusEl.className = 'again-status';
    document.body.appendChild(statusEl);
  }
  
  statusEl.textContent = message;
  statusEl.className = `again-status ${type} show`;
  
  // Auto-hide after 3 seconds
  setTimeout(() => {
    statusEl.classList.remove('show');
      }, 3000);
}

// Toggle model dropdown (also in chathistory.js)
function toggleModelDropdown(messageId) {
  const dropdown = document.getElementById(`model-dropdown-${messageId}`);
  if (!dropdown) return;
  
  const isOpen = dropdown.classList.contains('show');
  
  // Close all other dropdowns
  document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
    menu.classList.remove('show');
  });
  
  if (!isOpen) {
    dropdown.classList.add('show');
    loadModelsForDropdown(messageId);
  }
}

// Load models for dropdown (also in chathistory.js)
function loadModelsForDropdown(messageId) {
  const dropdown = document.getElementById(`model-dropdown-${messageId}`);
  if (!dropdown) return;
  
  if (selectableModels) {
    renderModelDropdown(messageId, selectableModels);
    return;
  }
  
  // Show loading state
  dropdown.innerHTML = `<li><span class="dropdown-item-text text-center"><i class="fas fa-spinner fa-spin"></i> Lade Modelle...</span></li>`;
  
  // Fetch models from API
  fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=getSelectableModels'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.models) {
      selectableModels = data.models;
      renderModelDropdown(messageId, data.models);
    } else {
      dropdown.innerHTML = '<li><span class="dropdown-item-text text-center text-danger">Fehler beim Laden</span></li>';
    }
  })
  .catch(error => {
    console.error('Failed to load models:', error);
    dropdown.innerHTML = '<li><span class="dropdown-item-text text-center text-danger">Netzwerkfehler</span></li>';
  });
}

// Render model dropdown content (also in chathistory.js)
function renderModelDropdown(messageId, models) {
  const dropdown = document.getElementById(`model-dropdown-${messageId}`);
  if (!dropdown) return;
  
  const selectedBid = selectedModelOverrides[messageId];
  
  let html = '<li><h6 class="dropdown-header">Modell wählen</h6></li>';
  models.forEach(model => {
    const isSelected = selectedBid == model.bid;
    html += `
      <li><a class="dropdown-item ${isSelected ? 'active' : ''}" href="#" onclick="selectModel(${messageId}, ${model.bid}, '${model.tag}'); return false;">
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <span class="ai-icon">${getAIIcon('AI' + model.service)}</span>
            <span class="fw-medium">${model.tag}</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <small class="text-muted">${model.quality}</small>
            ${isSelected ? '<i class="fas fa-check text-success"></i>' : ''}
          </div>
        </div>
      </a></li>
    `;
  });
  
  dropdown.innerHTML = html;
}

// Select model from dropdown (also in chathistory.js)
function selectModel(messageId, modelBid, modelName) {
  selectedModelOverrides[messageId] = modelBid;
  
  // Update button label
  updateAgainButtonLabel(messageId);
  
  // Close dropdown
  const dropdown = document.getElementById(`model-dropdown-${messageId}`);
  if (dropdown) {
    dropdown.classList.remove('show');
  }
  
  // Store in localStorage for persistence
  try {
    localStorage.setItem('lastSelectedModel', modelBid);
  } catch (e) {
    // Ignore localStorage errors
  }
}

// Update Again button label with next model name (also in chathistory.js)
function updateAgainButtonLabel(messageId) {
  const button = document.querySelector(`button.again-btn[data-message-id="${messageId}"]`);
  if (!button) return;
  
  // Check if user has selected a specific model
  if (selectedModelOverrides[messageId]) {
    const selectedModelName = getModelNameById(selectedModelOverrides[messageId]);
    const btnText = button.querySelector('.d-none.d-md-inline, .btn-text');
    if (btnText) {
      btnText.innerHTML = `Again mit ${selectedModelName}`;
    }
    return;
  }
  
  // Get predicted next model from backend Round-Robin logic
  fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `action=getNextModel&messageId=${messageId}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.next_model) {
      const nextModelName = data.next_model.tag;
      const btnText = button.querySelector('.d-none.d-md-inline, .btn-text');
      if (btnText) {
        btnText.innerHTML = `Again mit ${nextModelName}`;
      }
    } else {
      // Fallback
      const btnText = button.querySelector('.d-none.d-md-inline, .btn-text');
      if (btnText) {
        btnText.innerHTML = 'Again';
      }
    }
  })
  .catch(error => {
    console.error('Failed to load next model:', error);
    const btnText = button.querySelector('.d-none.d-md-inline, .btn-text');
    if (btnText) {
      btnText.innerHTML = 'Again';
    }
  });
}

// Get model name by BID (also in chathistory.js)
function getModelNameById(modelBid) {
  if (!selectableModels) return 'Modell';
  const model = selectableModels.find(m => m.bid == modelBid);
  return model ? model.tag : 'Modell';
}

// Function to show file details for a specific message
function showMessageFiles(messageId) {
    const filesContainer = document.getElementById(`files-${messageId}`);
    if (!filesContainer) {
        console.error('Files container not found for message ID:', messageId);
        return;
    }
    
    const attachmentDiv = filesContainer.previousElementSibling;
    if (!attachmentDiv) {
        console.error('Attachment header not found for message ID:', messageId);
        return;
    }
    
    const chevronIcon = attachmentDiv.querySelector('.fa-chevron-down, .fa-chevron-up');
    
    if (filesContainer.style.display === 'none') {
        // Show files
        filesContainer.style.display = 'block';
        if (chevronIcon) {
            chevronIcon.className = 'fas fa-chevron-up';
        }
        
        // Show loading indicator
        filesContainer.innerHTML = '<div style="font-size: 0.75rem; opacity: 0.7; padding: 8px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading files...</div>';
        
        // Load file details via AJAX
        const formData = new FormData();
        formData.append('action', 'getMessageFiles');
        formData.append('messageId', messageId);
        
        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.files && data.files.length > 0) {
                let filesHtml = '';
                data.files.forEach(file => {
                    const fileName = file.BFILEPATH ? file.BFILEPATH.split('/').pop() : 'Unknown file';
                    const fileIcon = getFileIcon(file.BFILETYPE);
                    const fileUrl = `up/${file.BFILEPATH}`;
                    
                    filesHtml += `
                        <div class="file-item">
                            <i class="${fileIcon}" style="font-size: 0.8rem;"></i>
                            <span style="flex: 1; word-break: break-all;">${fileName}</span>
                            <a href="${fileUrl}" target="_blank" style="color: rgba(255,255,255,0.8);">
                                <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                            </a>
                        </div>
                    `;
                });
                filesContainer.innerHTML = filesHtml;
            } else {
                filesContainer.innerHTML = '<div style="font-size: 0.75rem; opacity: 0.7; padding: 8px;">No file details available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading file details:', error);
            filesContainer.innerHTML = '<div style="font-size: 0.75rem; opacity: 0.7; padding: 8px;">Error loading file details</div>';
        });
    } else {
        // Hide files
        filesContainer.style.display = 'none';
        if (chevronIcon) {
            chevronIcon.className = 'fas fa-chevron-down';
        }
    }
}

// Helper function to get appropriate file icon
function getFileIcon(fileType) {
    const iconMap = {
        'pdf': 'fas fa-file-pdf',
        'doc': 'fas fa-file-word',
        'docx': 'fas fa-file-word',
        'xls': 'fas fa-file-excel',
        'xlsx': 'fas fa-file-excel',
        'ppt': 'fas fa-file-powerpoint',
        'pptx': 'fas fa-file-powerpoint',
        'txt': 'fas fa-file-alt',
        'jpg': 'fas fa-file-image',
        'jpeg': 'fas fa-file-image',
        'png': 'fas fa-file-image',
        'gif': 'fas fa-file-image',
        'mp3': 'fas fa-file-audio',
        'wav': 'fas fa-file-audio',
        'mp4': 'fas fa-file-video',
        'avi': 'fas fa-file-video',
        'zip': 'fas fa-file-archive',
        'rar': 'fas fa-file-archive'
    };
    return iconMap[fileType.toLowerCase()] || 'fas fa-file';
}

// ------------------------------------------------------------

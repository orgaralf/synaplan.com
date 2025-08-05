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
          </li>
        `);
        
        // now start the sse stream and show the server messages
        if(Array.isArray(data.lastIds)) {
          let AItextBlock = `START_${data.lastIds[0]}`;
          $("#chatHistory").append(`
            <li class="message-item ai-message">
              <div class="ai-avatar">
                <i class="fas fa-robot text-white"></i>
              </div>
              <div class="message-content">
                <span id="system${AItextBlock}" class="system-message"></span>
                <div class="message-bubble ai-bubble">
                  <div id="${AItextBlock}" class="message-content"></div>
                  <span class="message-time ai-time">${data.time}</span>
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

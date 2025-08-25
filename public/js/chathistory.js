/**
 * Chat History Loading Functionality
 * 
 * This script handles the dynamic loading of chat history via API calls.
 * Users can click buttons to load 10, 20, or 30 messages, which will
 * replace the button interface with the actual chat history.
 * 
 * Dependencies:
 * - jQuery (for DOM manipulation and AJAX)
 * - markdown-it (for markdown processing)
 * - highlight.js (for syntax highlighting)
 * - Font Awesome (for icons)
 */

// Function to load chat history via API
function loadChatHistory(amount) {
    const buttonsContainer = document.getElementById('chatHistoryButtons');
    const chatHistory = document.getElementById('chatHistory');
    
    // Show loading state
    buttonsContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading ${amount} messages...</p>
        </div>
    `;
    
    // Make API call
    const formData = new FormData();
    formData.append('action', 'loadChatHistory');
    formData.append('amount', amount);
    formData.append('timestamp', Date.now()); // Add timestamp to prevent caching
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('Error:', data.error);
            buttonsContainer.innerHTML = `
                <div class="text-center py-4">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading chat history: ${data.error}
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-redo me-1"></i> Retry
                    </button>
                </div>
            `;
        } else if (data.success && data.messages) {
            // Remove the buttons container
            buttonsContainer.remove();
            
            // Render messages
            renderChatHistory(data.messages);
            
            // Scroll to bottom
            setTimeout(function() {
                $("#chatModalBody").scrollTop($("#chatModalBody").prop("scrollHeight"));
            }, 100);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        buttonsContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Network error loading chat history
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-redo me-1"></i> Retry
                </button>
            </div>
        `;
    });
}

// Function to render chat history messages
function renderChatHistory(messages) {
    const chatHistory = document.getElementById('chatHistory');
    
    messages.forEach(chat => {
        let messageHtml = '';
        
        if (chat.BDIRECT === 'IN') {
            // User message
            messageHtml = `
                <li class="message-item user-message">
                    <div class="message-bubble user-bubble">
                        <p>${escapeHtml(chat.BTEXT)}</p>
                        ${chat.FILECOUNT > 0 ? `
                            <div class="file-attachment-header" onclick="showMessageFiles(${chat.BID})">
                                <i class="fas fa-paperclip paperclip-icon"></i>
                                <span>${chat.FILECOUNT} file${chat.FILECOUNT > 1 ? 's' : ''} attached</span>
                                <i class="fas fa-chevron-down chevron-icon"></i>
                            </div>
                            <div id="files-${chat.BID}" class="message-files" style="display: none;">
                                <!-- File details will be loaded here -->
                            </div>
                        ` : ''}
                        <span class="message-time user-time">${formatDateTime(chat.BDATETIME)}</span>
                    </div>
                </li>
            `;
        } else {
            // AI message
            const displayText = chat.displayText || chat.BTEXT;
            const hasFile = chat.hasFile || false;
            
            // Process markdown
            const mdText = window.md ? window.md.render(displayText) : displayText;
            
            let fileHtml = '';
            if (hasFile && chat.BFILEPATH) {
                const fileUrl = "up/" + chat.BFILEPATH;
                
                if (['png', 'jpg', 'jpeg'].includes(chat.BFILETYPE)) {
                    fileHtml = `<div class='generated-file-container'><img src='${fileUrl}' class='generated-image' alt='Generated Image' loading='lazy'></div>`;
                } else if (['mp4', 'webm'].includes(chat.BFILETYPE)) {
                    fileHtml = `<div class='generated-file-container'><video src='${fileUrl}' class='generated-video' controls preload='metadata'>Your browser does not support the video tag.</video></div>`;
                }
            }
            
            // Check if message is "agained" (marked as replaced)
            const isAgained = chat.againStatus === 'AGAINED';
            const againedClass = isAgained ? ' message-agained' : '';
            
            // Get model info for avatar and display
            const modelService = chat.aiService || '';
            const modelName = chat.aiModel || '';
            const modelProviderRaw = chat.aiModelProvider || '';
            // Remove 'AI' prefix from service name for avatar class
            const cleanService = modelService.replace('AI', '');
            const avatarClass = getModelAvatarClass(cleanService);
            
            messageHtml = `
                <li class="message-item ai-message${againedClass}" data-message-id="${chat.BID}">
                    <div class="ai-avatar ${avatarClass} d-none d-md-flex">
                        ${chat.aiService ? (typeof getAIIconByModel === 'function' ? getAIIconByModel(modelProviderRaw || modelName, chat.aiService) : getAIIcon(chat.aiService)) : '<i class="fas fa-robot text-white"></i>'}
                    </div>
                    <div class="message-content">
                        <span id="system${chat.BID}" class="system-message"></span>
                        <div class="message-bubble ai-bubble">
                            <div id="rep${chat.BID}" class="message-content">
                                ${fileHtml}
                                ${mdText}
                            </div>
                            <div class="card-footer bg-light border-0 mt-2">
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-secondary">${formatDateTime(chat.BDATETIME)}</span>
                                        ${chat.aiModel ? `
                                            <span class="badge bg-success text-truncate" style="max-width: 200px;" title="${escapeHtml(chat.aiModel)}">${getTranslation('answer_from')} ${escapeHtml(chat.aiModel)}</span>
                                        ` : chat.aiService ? `
                                            <span class="badge bg-success text-truncate" style="max-width: 200px;" title="${escapeHtml(chat.aiService.replace('AI', ''))}">${getTranslation('answer_from')} ${escapeHtml(chat.aiService.replace('AI', ''))}</span>
                                        ` : ''}
                                        ${chat.BTOPIC && chat.BTOPIC !== 'general' ? `<span class="badge bg-info">${escapeHtml(chat.BTOPIC)}</span>` : ''}
                                    </div>
                                    ${!isAgained ? `
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-outline-secondary btn-sm copy-btn" data-message-id="${chat.BID}" title="Text kopieren" onclick="copyMessageText(${chat.BID})">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-success btn-sm again-btn" data-message-id="${chat.BID}" title="${getTranslation('again_button_tooltip')}" onclick="handleAgainRequest(${chat.BID})">
                                            <i class="fas fa-redo"></i>
                                            <span>Again mit <span class="next-model-name">...</span></span>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-disabled" type="button" disabled title="Nur bei neuester Nachricht verfügbar">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                            <ul class="dropdown-menu" id="model-dropdown-${chat.BID}">
                                                <li><span class="dropdown-item-text text-center text-muted">
                                                    Nur bei neuester Nachricht verfügbar
                                                </span></li>
                                            </ul>
                                        </div>
                                    </div>
                                    ` : `
                                    <div>
                                        <span class="badge bg-warning text-dark">${getTranslation('marked_as_inappropriate')}</span>
                                    </div>
                                    `}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            `;
        }
        
        chatHistory.innerHTML += messageHtml;
    });
    
    // Apply syntax highlighting if highlight.js is available
    if (window.hljs) {
        chatHistory.querySelectorAll('pre code').forEach((block) => {
            window.hljs.highlightElement(block);
        });
    }
    
    // Enable dropdown only for the newest non-agained message, disable all others
    const allMessages = chatHistory.querySelectorAll('.message-item.ai-message:not(.message-agained)');
    
    // First disable all dropdowns
    chatHistory.querySelectorAll('.dropdown-toggle').forEach(btn => {
        btn.disabled = true;
        btn.classList.add('dropdown-disabled');
        btn.title = 'Nur bei neuester Nachricht verfügbar';
        btn.removeAttribute('onclick');
    });
    
    // Then enable only the newest one
    if (allMessages.length > 0) {
        const newestMessage = allMessages[allMessages.length - 1];
        const dropdownBtn = newestMessage.querySelector('.dropdown-toggle');
        if (dropdownBtn) {
            dropdownBtn.disabled = false;
            dropdownBtn.classList.remove('dropdown-disabled');
            dropdownBtn.title = 'Modell wählen';
            dropdownBtn.setAttribute('onclick', `toggleModelDropdown(${newestMessage.dataset.messageId})`);
            dropdownBtn.setAttribute('data-message-id', newestMessage.dataset.messageId);
            
            // Update dropdown content
            const dropdown = newestMessage.querySelector('.dropdown-menu');
            if (dropdown) {
                dropdown.innerHTML = '<li><span class="dropdown-item-text text-center"><i class="fas fa-spinner fa-spin"></i> Lade Modelle...</span></li>';
            }
            
            // Load next model name for the newest message
            updateAgainButtonLabel(newestMessage.dataset.messageId);
        }
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to shorten long model names
function shortenModelName(modelName) {
    if (!modelName) return modelName;
    
     // If too long (> 14 chars), truncate and add ellipsis
     if (modelName.length > 14) {
        return modelName.substring(0, 12) + '...';
    }
    
    return modelName;
}

// Get model-specific avatar class
function getModelAvatarClass(service) {
    // Normalize service name (remove AI prefix and convert to uppercase)
    const normalizedService = service.replace('AI', '').toUpperCase();
    
    const serviceMap = {
        'GROQ': 'ai-avatar-groq',
        'OPENAI': 'ai-avatar-openai',
        'ANTHROPIC': 'ai-avatar-claude',
        'GOOGLE': 'ai-avatar-google',
        'OLLAMA': 'ai-avatar-ollama',
        'DEEPSEEK': 'ai-avatar-deepseek'
    };
    return serviceMap[normalizedService] || 'ai-avatar-default';
}

// Simple translation function
function getTranslation(key) {
    const translations = {
        'again_button_tooltip': 'Nochmal versuchen',
        'again_generating': 'Neue Antwort wird generiert...',
        'again_error': 'Fehler beim erneuten Versuch',
        'again_network_error': 'Netzwerkfehler. Bitte versuchen Sie es erneut.',
        'again_locked': 'Ein anderer Retry läuft bereits für diese Nachricht',
        'answer_from': 'Antwort von',
        'marked_as_inappropriate': 'als unpassend markiert',
        'loading_models': 'Lade Modelle...',
        'select_model': 'Modell wählen'
    };
    return translations[key] || key;
}

// Global model cache (check if already defined)
if (typeof selectableModels === 'undefined') {
    var selectableModels = null;
}
if (typeof selectedModelOverrides === 'undefined') {
    var selectedModelOverrides = {};
}

// Make functions globally available
window.handleAgainRequest = handleAgainRequest;
window.toggleModelDropdown = toggleModelDropdown;
window.selectModel = selectModel;
window.updateAgainButtonLabel = updateAgainButtonLabel;
window.setAgainButtonState = setAgainButtonState;

// Handle Again request with optional model override
function handleAgainRequest(messageId, overrideModelBid = null) {
    // Use override from dropdown if not explicitly provided
    if (!overrideModelBid && selectedModelOverrides[messageId]) {
        overrideModelBid = selectedModelOverrides[messageId];
    }
    
    // Prevent double-clicks
    const button = document.querySelector(`button.again-btn[data-message-id="${messageId}"]`);
    if (button && button.disabled) {
        return;
    }
    
    // Track analytics
    trackAgainEvent('again_clicked', messageId, overrideModelBid);
    
    // Set pending state
    setAgainButtonState(messageId, 'pending');
    
    // Prepare request body
    let requestBody = `action=againMessage&messageId=${messageId}`;
    if (overrideModelBid) {
        requestBody += `&modelBid=${overrideModelBid}`;
    }
    
    // Send Again request to API
    fetch('api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: requestBody
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
            
            // Track success
            trackAgainEvent('again_succeeded', messageId, data.retry_model_bid);
            
            // Show success message briefly
            showAgainStatus(getTranslation('again_generating'), 'success');
            
            // Trigger SSE stream for the retry message
            const retryData = {
                lastIds: [data.retry_message_id],
                time: new Date().toLocaleString('de-DE')
            };
            
            const AItextBlock = `START_${data.retry_message_id}`;
            $("#chatHistory").append(`
                <li class="message-item ai-message" data-streaming-id="${data.retry_message_id}">
                    <div class="ai-avatar ai-avatar-${data.retry_model_service.toLowerCase()} d-none d-md-flex">
                        ${getAIIcon('AI' + data.retry_model_service)}
                    </div>
                    <div class="message-content">
                        <div class="message-bubble ai-bubble">
                            <div id="${AItextBlock}" class="message-content"></div>
                            <div class="card-footer bg-light border-0 mt-2" style="display: none;">
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-secondary">${retryData.time}</span>
                                        <span class="badge bg-success text-truncate" style="max-width: 200px;" title="${data.retry_model}">Antwort von ${data.retry_model}</span>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-outline-secondary btn-sm copy-btn" title="Text kopieren" onclick="copyMessageText('${AItextBlock}')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-success btn-sm again-btn" data-message-id="${data.retry_message_id}" title="${getTranslation('again_button_tooltip')}" onclick="handleAgainRequest(${data.retry_message_id})">
                                            <i class="fas fa-redo"></i>
                                            <span>Again mit <span class="next-model-name">...</span></span>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-message-id="${data.retry_message_id}" onclick="toggleModelDropdown(${data.retry_message_id})">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                            <ul class="dropdown-menu" id="model-dropdown-${data.retry_message_id}">
                                                <li><span class="dropdown-item-text text-center">
                                                    <i class="fas fa-spinner fa-spin"></i> Lade Modelle...
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
            sseStream(retryData, AItextBlock);
            $("#chatModalBody").scrollTop($("#chatModalBody").prop("scrollHeight"));
            
            // Load next model name for the new retry message
            setTimeout(() => {
                updateAgainButtonLabel(data.retry_message_id);
                
                // Disable all other dropdowns and enable only the newest one
                const allMessages = document.querySelectorAll('.message-item.ai-message:not(.message-agained)');
                
                // First disable all dropdowns
                document.querySelectorAll('.dropdown-toggle').forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('dropdown-disabled');
                    btn.title = 'Nur bei neuester Nachricht verfügbar';
                    btn.removeAttribute('onclick');
                });
                
                // Then enable only the newest one
                if (allMessages.length > 0) {
                    const newestMessage = allMessages[allMessages.length - 1];
                    const dropdownBtn = newestMessage.querySelector('.dropdown-toggle');
                    if (dropdownBtn) {
                        dropdownBtn.disabled = false;
                        dropdownBtn.classList.remove('dropdown-disabled');
                        dropdownBtn.title = 'Modell wählen';
                        dropdownBtn.setAttribute('onclick', `toggleModelDropdown(${newestMessage.dataset.messageId})`);
                        dropdownBtn.setAttribute('data-message-id', newestMessage.dataset.messageId);
                        
                        // Update dropdown content
                        const dropdown = newestMessage.querySelector('.dropdown-menu');
                        if (dropdown) {
                            dropdown.innerHTML = '<li><span class="dropdown-item-text text-center"><i class="fas fa-spinner fa-spin"></i> Lade Modelle...</span></li>';
                        }
                    }
                }
            }, 100);
            
        } else {
            // Show specific error message based on error code
            let errorMessage = data.error || getTranslation('again_error');
            const errorMappings = {
                'LOCKED': 'wird bereits verarbeitet',
                'NO_ALTERNATIVE_MODEL': 'Kein alternatives Modell verfügbar',
                'MESSAGE_NOT_FOUND': 'Nachricht nicht gefunden',
                'RETRY_FAILED': 'Retry nicht möglich'
            };
            
            if (data.error_code && errorMappings[data.error_code]) {
                errorMessage = errorMappings[data.error_code];
            }
            
            // Track failure
            trackAgainEvent('again_failed', messageId, null, data.error_code);
            
            showAgainStatus(errorMessage, 'error');
            
            // Reset button state
            setAgainButtonState(messageId, 'error', errorMessage);
        }
    })
    .catch(error => {
        console.error('Again request failed:', error);
        trackAgainEvent('again_failed', messageId, null, 'NETWORK_ERROR');
        showAgainStatus(getTranslation('again_network_error'), 'error');
        
        // Reset button state
        setAgainButtonState(messageId, 'error', getTranslation('again_network_error'));
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

// Set Again button state (idle/pending/error)
function setAgainButtonState(messageId, state, errorMessage = '') {
    const button = document.querySelector(`button.again-btn[data-message-id="${messageId}"]`);
    const dropdown = document.querySelector(`.btn-group button[data-message-id="${messageId}"]`);
    
    if (!button) return;
    
    switch (state) {
                        case 'pending':
            button.disabled = true;
            dropdown && (dropdown.disabled = true);
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="d-none d-md-inline">Wird bearbeitet...</span>';
            break;
        case 'error':
            button.disabled = false;
            dropdown && (dropdown.disabled = false);
            button.className = 'btn btn-warning btn-sm again-btn';
            button.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <span class="d-none d-md-inline">${errorMessage}</span>`;
            // Reset to normal after 3 seconds
            setTimeout(() => {
                button.className = 'btn btn-success btn-sm again-btn';
                setAgainButtonState(messageId, 'idle');
            }, 3000);
            break;
        case 'idle':
        default:
            button.disabled = false;
            dropdown && (dropdown.disabled = false);
            button.className = 'btn btn-success btn-sm again-btn';
            updateAgainButtonLabel(messageId);
            break;
    }
}

// Update Again button label with next model name
function updateAgainButtonLabel(messageId) {
    const button = document.querySelector(`button.again-btn[data-message-id="${messageId}"]`);
    if (!button) return;
    
    // Check if user has selected a specific model
    if (selectedModelOverrides[messageId]) {
        const selectedModelName = shortenModelName(getModelNameById(selectedModelOverrides[messageId]));
        const btnText = button.querySelector('.next-model-name');
        if (btnText) {
            btnText.textContent = selectedModelName;
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
            const nextModelName = shortenModelName(data.next_model.tag);
            const btnText = button.querySelector('.next-model-name');
            if (btnText) {
                btnText.textContent = nextModelName;
            }
        } else {
            // Fallback - keep "..." as placeholder
        }
    })
    .catch(error => {
        console.error('Failed to load next model:', error);
        // Keep "..." as fallback on error
    });
}

// Toggle model dropdown
function toggleModelDropdown(messageId) {
    const dropdown = document.getElementById(`model-dropdown-${messageId}`);
    const button = document.querySelector(`button[onclick*="toggleModelDropdown(${messageId})"]`);
    
    if (!dropdown || !button) {
        console.error('Dropdown or button not found:', messageId);
        return;
    }
    
    const isOpen = dropdown.classList.contains('show');
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
        menu.classList.remove('show');
    });
    
    if (!isOpen) {
        const rect = button.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        // Default position below button
        let top = rect.bottom + 6;
        let maxHeight = viewportHeight - rect.bottom - 16; // space below

        // If there is not enough space below, open upwards
        if (maxHeight < 160) { // 160px minimal dropdown height
            maxHeight = rect.top - 16; // space above
            top = rect.top - Math.min(400, maxHeight) - 6;
            dropdown.classList.add('dropup');
        } else {
            dropdown.classList.remove('dropup');
        }

        // Align within viewport width and set fixed positioning for overlay
        const menuWidth = 280;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        let left = rect.right - menuWidth;
        left = Math.max(12, Math.min(left, viewportWidth - menuWidth - 12));

        dropdown.style.position = 'fixed';
        dropdown.style.left = left + 'px';
        dropdown.style.top = top + 'px';
        dropdown.style.maxHeight = Math.max(160, maxHeight) + 'px';
        dropdown.style.overflowY = 'auto';
        dropdown.style.minWidth = menuWidth + 'px';
        dropdown.style.zIndex = 2000;

        dropdown.classList.add('show');
        loadModelsForDropdown(messageId);
    }
}

// Load models for dropdown
function loadModelsForDropdown(messageId) {
    const dropdown = document.getElementById(`model-dropdown-${messageId}`);
    if (!dropdown) return;
    
    if (selectableModels) {
        renderModelDropdown(messageId, selectableModels);
        return;
    }
    
    // Show loading state only if not already loaded
    if (!selectableModels) {
        dropdown.innerHTML = `<li><span class="dropdown-item-text text-center"><i class="fas fa-spinner fa-spin"></i> ${getTranslation('loading_models')}</span></li>`;
    }
    
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
            dropdown.innerHTML = '<li><span class="dropdown-item-text text-center text-danger">Fehler beim Laden der Modelle</span></li>';
        }
    })
    .catch(error => {
        console.error('Failed to load models:', error);
        dropdown.innerHTML = '<li><span class="dropdown-item-text text-center text-danger">Netzwerkfehler</span></li>';
    });
}

// Render model dropdown content
function renderModelDropdown(messageId, models) {
    const dropdown = document.getElementById(`model-dropdown-${messageId}`);
    if (!dropdown) {
        console.error('Dropdown not found for messageId:', messageId);
        return;
    }
    
    const selectedBid = selectedModelOverrides[messageId];
    console.log('Rendering dropdown for messageId:', messageId, 'with models:', models.length);
    
    let html = '<li><h6 class="dropdown-header">Modell wählen</h6></li>';
    models.forEach(model => {
        const isSelected = selectedBid == model.bid;
        html += `
            <li><a class="dropdown-item ${isSelected ? 'active' : ''}" href="#" onclick="window.selectModel(${messageId}, ${model.bid}, '${model.tag}'); return false;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="ai-icon">${(typeof getAIIconByModel === 'function') ? getAIIconByModel(model.provider, 'AI' + model.service) : getAIIcon('AI' + model.service)}</span>
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
    console.log('Dropdown rendered with HTML:', html);
}

// Select model from dropdown
function selectModel(messageId, modelBid, modelName) {
    selectedModelOverrides[messageId] = modelBid;
    
    // Update button label
    updateAgainButtonLabel(messageId);
    
    // Close dropdown
    const dropdown = document.getElementById(`model-dropdown-${messageId}`);
    if (dropdown) {
        dropdown.classList.remove('show');
    }
    
    // Update dropdown display to show selected model
    renderModelDropdown(messageId, selectableModels);
    
    // Store in localStorage for persistence
    try {
        localStorage.setItem('lastSelectedModel', modelBid);
    } catch (e) {
        // Ignore localStorage errors
    }
}

// Get model name by BID
function getModelNameById(modelBid) {
    if (!selectableModels) return 'Modell';
    const model = selectableModels.find(m => m.bid == modelBid);
    return model ? model.tag : 'Modell';
}

// Scroll to message with highlight
function scrollToMessage(messageId) {
    const messageElement = document.querySelector(`li[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        messageElement.classList.add('highlight');
        setTimeout(() => messageElement.classList.remove('highlight'), 2000);
    }
}

// Track analytics events
function trackAgainEvent(eventType, messageId, modelBid = null, errorCode = null) {
    // Simple client-side analytics (no PII)
    const eventData = {
        event: eventType,
        messageId: messageId, // This is just a telemetry ID
        timestamp: Date.now()
    };
    
    if (modelBid) eventData.modelBid = modelBid;
    if (errorCode) eventData.errorCode = errorCode;
    
    // Log to console for debugging (replace with actual analytics service)
    console.log('AgainAnalytics:', eventData);
    
    // Store in session for potential batch sending
    try {
        const events = JSON.parse(sessionStorage.getItem('againEvents') || '[]');
        events.push(eventData);
        sessionStorage.setItem('againEvents', JSON.stringify(events));
    } catch (e) {
        // Ignore storage errors
    }
}

// Copy message text function
function copyMessageText(messageId) {
    const messageElement = document.querySelector(`li[data-message-id="${messageId}"] .message-content, #${messageId}`);
    if (!messageElement) return;
    
    const textContent = messageElement.textContent || messageElement.innerText;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(textContent).then(() => {
            showAgainStatus('Text kopiert!', 'success');
        }).catch(err => {
            console.error('Failed to copy text:', err);
            fallbackCopyText(textContent);
        });
    } else {
        fallbackCopyText(textContent);
    }
}

// Fallback copy function
function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        showAgainStatus('Text kopiert!', 'success');
    } catch (err) {
        console.error('Fallback copy failed:', err);
        showAgainStatus('Kopieren fehlgeschlagen', 'error');
    }
    document.body.removeChild(textArea);
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Helper function to format datetime
function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return '';
    
    // Convert YmdHis format to readable format
    const year = dateTimeStr.substring(0, 4);
    const month = dateTimeStr.substring(4, 6);
    const day = dateTimeStr.substring(6, 8);
    const hour = dateTimeStr.substring(8, 10);
    const minute = dateTimeStr.substring(10, 12);
    const second = dateTimeStr.substring(12, 14);
    
    return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
}

// Initialize chat history functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if not in anonymous widget mode and elements exist
    if (typeof window.isAnonymousWidget === 'undefined' || !window.isAnonymousWidget) {
        // Add event listeners to history loading buttons
        const historyButtons = document.querySelectorAll('.load-history-btn');
        if (historyButtons.length > 0) {
            historyButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const amount = parseInt(this.getAttribute('data-amount'));
                    loadChatHistory(amount);
                });
            });
        }
        
        // Add event listener to hide history button
        const hideButtons = document.querySelectorAll('.hide-history-btn');
        if (hideButtons.length > 0) {
            hideButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const buttonsContainer = document.getElementById('chatHistoryButtons');
                    if (buttonsContainer) {
                        buttonsContainer.remove();
                    }
                });
            });
        }
    }
}); 
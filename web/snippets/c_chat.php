<?php
// this page can be included in the widget loader page
// or as part of the admin section of synaplan
// We have to set the values and the prompt config correctly.
?>
<link rel="stylesheet" href="fa/css/all.min.css">
<!-- Add highlight.js CSS -->
<link rel="stylesheet" href="node_modules/@highlightjs/cdn-assets/styles/googlecode.min.css">
<main class="col-md-9 ms-sm-auto col-lg-10 px-1 px-md-3 py-2 py-md-1 content-main-bg" id="contentMain">
    <!-- Chat Page Header (now inside main, above chat-container) -->
    <div class="chat-page-header">
        <div style="flex: 1; min-width: 280px;">
            <div class="dropdown custom-prompt-dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="promptDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="dropdown-main">🤖 Default AI Assistant</span>
                    <span class="dropdown-desc">General-purpose AI assistant for all tasks</span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="promptDropdownBtn">
                    <li>
                        <a class="dropdown-item" href="#" data-value="tools:sort">
                            <span class="dropdown-main">🤖 Default AI Assistant</span>
                            <span class="dropdown-desc">General-purpose AI assistant for all tasks</span>
                        </a>
                    </li>
                    <?php
                        $prompts = BasicAI::getAllPrompts();
                        foreach($prompts as $prompt) {
                            $ownerHint = $prompt['BOWNERID'] != 0 ? "👤" : "🏢";
                            $desc = htmlspecialchars(substr($prompt['BSHORTDESC'],0,84));
                            echo "<li>
                                <a class='dropdown-item' href='#' data-value='".htmlspecialchars($prompt['BTOPIC'])."'>
                                    <span class='dropdown-main'>{$ownerHint} {$prompt['BTOPIC']}</span>
                                    <span class='dropdown-desc'>{$desc}</span>
                                </a>
                            </li>";
                        }
                    ?>
                </ul>
                <input type="hidden" name="promptConfigSelect" id="promptConfigSelect" value="tools:sort">
            </div>
        </div>
    </div>
    <!-- Modern Chat Interface -->
    <div class="chat-container">
        <!-- Enhanced chatbox with shadow and modern styling -->
        <div class="chatbox">
            <div class="chat-messages" id="chatModalBody">
                <div class="messages-container">
                    <ul id="chatHistory">
                        <?php
                        $historyChatArr = Frontend::getLatestChats(10);
                        if(count($historyChatArr) > 0) {
                            foreach($historyChatArr as $chat) {
                                // Fetch AI service and model information for AI messages
                                $aiService = '';
                                $aiModel = '';
                                if($chat['BDIRECT'] == 'OUT') {
                                    $serviceSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'AISERVICE' ORDER BY BID DESC LIMIT 1";
                                    $serviceRes = db::Query($serviceSQL);
                                    if($serviceArr = db::FetchArr($serviceRes)) {
                                        $aiService = $serviceArr['BVALUE'];
                                    }
                                    //print "<li>aiService: ".$aiService. $serviceSQL."</li>";
                                    $modelSQL = "SELECT BVALUE FROM BMESSAGEMETA WHERE BMESSID = ".intval($chat['BID'])." AND BTOKEN = 'AIMODEL' ORDER BY BID DESC LIMIT 1";
                                    $modelRes = db::Query($modelSQL);
                                    if($modelArr = db::FetchArr($modelRes)) {
                                        $aiModel = $modelArr['BVALUE'];
                                    }
                                    //print "<li>aiModel: ".$aiModel."</li>";
                                }
                                // **************************************************************************************************
                                // IN OR OUT?
                                // **************************************************************************************************
                                if($chat['BDIRECT'] == 'IN') { ?>
                                    <li class="message-item user-message">
                                        <div class="message-bubble user-bubble">
                                            <p><?php echo $chat['BTEXT']; ?></p>
                                            <?php if(isset($chat['FILECOUNT']) && $chat['FILECOUNT'] > 0) { ?>
                                                <div class="file-attachment-header" onclick="showMessageFiles(<?php echo $chat['BID']; ?>)">
                                                    <i class="fas fa-paperclip paperclip-icon"></i>
                                                    <span><?php echo $chat['FILECOUNT']; ?> file<?php echo $chat['FILECOUNT'] > 1 ? 's' : ''; ?> attached</span>
                                                    <i class="fas fa-chevron-down chevron-icon"></i>
                                                </div>
                                                <div id="files-<?php echo $chat['BID']; ?>" class="message-files" style="display: none;">
                                                    <!-- File details will be loaded here -->
                                                </div>
                                            <?php } ?>
                                            <span class="message-time user-time"><?php echo Tools::myDateTime($chat['BDATETIME']); ?></span>
                                        </div>
                                    </li>
                                <?php } else { 
                                    // Process markdown for AI messages, but handle files specially
                                    $displayText = $chat['BTEXT'];
                                    if(substr($chat['BTEXT'], 0, 1) == '/') {
                                        $displayText = "File generated";
                                    }
                                    $hasFile = ($chat['BFILE'] > 0 && !empty($chat['BFILETYPE']) && !empty($chat['BFILEPATH']) && strpos($chat['BFILEPATH'], '/') !== false);
                                    
                                    // Debug output for localhost
                                    if (substr_count($_SERVER["SERVER_NAME"], "localhost") > 0 AND 1==2) {
                                        error_log("DEBUG Chat Message BID {$chat['BID']}: BFILE={$chat['BFILE']}, BFILETYPE='{$chat['BFILETYPE']}', BFILEPATH='{$chat['BFILEPATH']}', hasFile=" . ($hasFile ? 'true' : 'false'));
                                    }
                                    
                                    // If the message starts with a tool command but has a file, show a better message
                                    if ($hasFile && substr($chat['BTEXT'], 0, 1) == '/') {
                                        if ($chat['BFILETYPE'] == 'mp4' || $chat['BFILETYPE'] == 'webm') {
                                            $displayText = "Video";
                                        } elseif (in_array($chat['BFILETYPE'], ['png', 'jpg', 'jpeg', 'gif'])) {
                                            $displayText = "Image";
                                        } else {
                                            $displayText = "File";
                                        }
                                    }
                                    
                                    $Parsedown = new Parsedown();
                                    $mdText = $Parsedown->text($displayText);
                                    ?>
                                    <li class="message-item ai-message">
                                        <div class="ai-avatar">
                                            <i class="fas fa-robot text-white"></i>
                                        </div>
                                        <div class="message-content">
                                            <span id="system<?php echo $chat['BID']; ?>" class="system-message"></span>
                                            <div class="message-bubble ai-bubble">
                                                <div id="rep<?php echo $chat['BID']; ?>" class="message-content">
                                                    <?php 
                                                    // Debug output for localhost
                                                    if (substr_count($_SERVER["SERVER_NAME"], "localhost") > 0) {
                                                        echo "<!-- DEBUG: Message {$chat['BID']}, hasFile=" . ($hasFile ? 'true' : 'false') . ", BFILETYPE='{$chat['BFILETYPE']}' -->";
                                                    }
                                                    
                                                    if($hasFile) {
                                                        // Construct file URL safely
                                                        $baseUrl = $GLOBALS["baseUrl"];
                                                        $fileUrl = $baseUrl . "up/" . $chat['BFILEPATH'];
                                                        
                                                        // Debug output
                                                        if (substr_count($_SERVER["SERVER_NAME"], "localhost") > 0 AND 1==2) {
                                                            error_log("DEBUG: Rendering file - URL: $fileUrl, Type: {$chat['BFILETYPE']}");
                                                        }
                                                        
                                                        if($chat['BFILETYPE'] == 'png' OR $chat['BFILETYPE'] == 'jpg' OR $chat['BFILETYPE'] == 'jpeg') {
                                                            echo "<div class='generated-file-container'>";
                                                            echo "<img src='".$fileUrl."' class='generated-image' alt='Generated Image' loading='lazy'>";
                                                            echo "</div>";
                                                        }
                                                        if($chat['BFILETYPE'] == 'mp4' OR $chat['BFILETYPE'] == 'webm') {
                                                            echo "<div class='generated-file-container'>";
                                                            echo "<video src='".$fileUrl."' class='generated-video' controls preload='metadata'>";
                                                            echo "Your browser does not support the video tag.";
                                                            echo "</video>";
                                                            echo "</div>";
                                                        }
                                                        
                                                        // Debug: Add a small link to check if file exists
                                                        if (substr_count($_SERVER["SERVER_NAME"], "localhost") > 0) {
                                                            echo "<small><a href='".$fileUrl."' target='_blank' class='generated-file-link'>🔗 " . basename($chat['BFILEPATH']) . "</a></small><br>";
                                                        }
                                                    } else {
                                                        // Debug: Why no file?
                                                        if (substr_count($_SERVER["SERVER_NAME"], "localhost") > 0 AND 1==2) {
                                                            error_log("DEBUG: No file for message {$chat['BID']} - BFILE={$chat['BFILE']}, BFILETYPE='{$chat['BFILETYPE']}', BFILEPATH='{$chat['BFILEPATH']}'");
                                                        }
                                                    }
                                                    echo $mdText;
                                                    ?>
                                                </div>
                                                <span class="ai-details ai-time">
                                                    <span class="message-time"><?php echo $chat['BTOPIC']; ?></span>
                                                    <?php if(!empty($aiService) || !empty($aiModel)) { ?>
                                                        <?php if(!empty($aiService)) { ?>
                                                            <span class="ai-service"><?php echo htmlspecialchars($aiService); ?></span>
                                                        <?php } ?>
                                                        <?php if(!empty($aiModel)) { ?>
                                                            <span class="ai-model"><?php echo htmlspecialchars($aiModel); ?></span>
                                                        <?php } ?>
                                                        <span class="message-time"><?php echo Tools::myDateTime($chat['BDATETIME']); ?></span>
                                                    <?php } else { ?>
                                                        <span class="message-time"><?php echo Tools::myDateTime($chat['BDATETIME']); ?></span>
                                                    <?php } ?>
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                <?php 
                                } ?>
                            <?php 
                            } 
                        }
                        ?>
                            </ul>
                        </div>
                    </div>
                    <!-- Enhanced Chat Input Area -->
                    <div class="chat-input-container px-2 px-md-3 py-2 py-md-3">
                        <!-- Enhanced File Preview Area (Overlay) -->
                        <div id="filesDiv" class="file-preview-container">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-paperclip text-primary me-2"></i>
                                    <h6 class="mb-0 text-primary fw-bold">Attached Files</h6>
                                </div>
                                <button type="button" id="closeFilesDiv" class="btn btn-sm btn-outline-danger close-files-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div class="file-actions mb-3">
                                <button type="button" id="manualFileSelect" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Add More Files
                                </button>
                                <span id="loader" class="ms-2 text-muted hidden">
                                    <i class="fas fa-spinner fa-spin"></i> Processing...
                                </span>
                            </div>
                            
                            <input id="fileInput" type="file" multiple style="display:none;" />
                            
                            <!-- Enhanced File Preview Grid -->
                            <div id="filePreview" class="file-preview-grid"></div>
                        </div>

                        <form id="chatForm" enctype="multipart/form-data">

                            <!-- Enhanced Input Controls -->
                            <div class="input-controls-wrapper">
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons d-flex gap-1">
                                    <button type="button" id="attachButton" class="btn btn-light attach-btn">
                                        <i class="fas fa-paperclip text-primary"></i>
                                    </button>
                                    <button type="button" id="speakButton" class="btn btn-light d-none d-md-flex speak-btn" title="Hold and speak">
                                        <i class="fas fa-microphone text-success"></i>
                                    </button>
                                </div>

                                <!-- Message Input -->
                                <div class="message-input-wrapper">
                                    <div id="messageInput" 
                                        class="message-input" 
                                        contenteditable="true" 
                                        placeholder="Type your message...">
                                    </div>
                                </div>

                                <!-- Send Button -->
                                <button type="button" id="sendButton" class="btn btn-primary send-btn">
                                    <i class="fas fa-paper-plane text-white"></i>
                                </button>
                            </div>
                        </form>
                    </div>
        </div>
    </div>
</main>
<script src="node_modules/markdown-it/dist/markdown-it.min.js"></script>
<!-- Add highlight.js JS -->
<script src="node_modules/@highlightjs/cdn-assets/highlight.min.js"></script> 
<script src="node_modules/@highlightjs/cdn-assets/languages/php.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/json.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/javascript.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/python.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/java.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/cpp.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/csharp.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/sql.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/bash.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/css.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/xml.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/sql.min.js"></script>
<script src="node_modules/@highlightjs/cdn-assets/languages/go.min.js"></script>
<script>
    // enable everything
    const md = window.markdownit({
        html: true,
        linkify: true,
        typographer: true,
        breaks: true,
    });
    // After: const md = window.markdownit({ ... });
    md.renderer.rules.link_open = function (tokens, idx, options, env, self) {
    // If the link does not already have target, add target="_blank"
    const aIndex = tokens[idx].attrIndex('target');
    if (aIndex < 0) {
        tokens[idx].attrPush(['target', '_blank']); // add new attribute
    } else {
        tokens[idx].attrs[aIndex][1] = '_blank';    // replace value
    }
    // Add rel="noopener noreferrer" for security
    const relIndex = tokens[idx].attrIndex('rel');
    if (relIndex < 0) {
        tokens[idx].attrPush(['rel', 'noopener noreferrer']);
    } else {
        tokens[idx].attrs[relIndex][1] = 'noopener noreferrer';
    }
    return self.renderToken(tokens, idx, options);
    };

    // Make md globally accessible for chat.js
    window.md = md;

    // Global variable to store current prompt configuration
    let currentPromptConfig = null;

    // Called when prompt configuration dropdown changes
    function onPromptConfigChange() {
        const selected = document.getElementById('promptConfigSelect').value;
        console.log("Selected prompt config:", selected);
        
        // Fetch prompt configuration details including tools and AI model
        const formData = new FormData();
        formData.append('action', 'getPromptDetails');
        formData.append('promptKey', selected);

        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                alert('Error loading prompt configuration: ' + data.error);
            } else {
                // Store the complete configuration for future use
                currentPromptConfig = {
                    BTOPIC: data.BTOPIC,
                    BPROMPT: data.BPROMPT,
                    BSHORTDESC: data.BSHORTDESC,
                    SETTINGS: data.SETTINGS || []
                };
                
                console.log("Loaded prompt config:", currentPromptConfig);
                
                // You can add UI updates here if needed
                // For example, showing which tools are enabled, current AI model, etc.
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the prompt configuration');
        });
    }

    // Function to get current prompt configuration
    function getCurrentPromptConfig() {
        return currentPromptConfig;
    }
</script>
<script src="js/speech.js"></script>
<script src="js/chat.js"></script>

<script>
    // when document is ready, scroll to the bottom of the chat modal
    $(document).ready(function() {
        onPromptConfigChange();
        // delay the scroll to the bottom of the chat modal
        setTimeout(function() {
            $("#chatModalBody").scrollTop( $("#chatModalBody").prop("scrollHeight") );
        }, 500);
    });
</script>

<script>
document.querySelectorAll('.custom-prompt-dropdown .dropdown-item').forEach(function(item) {
  item.addEventListener('click', function(e) {
    e.preventDefault();
    // Update button text
    var main = this.querySelector('.dropdown-main').textContent;
    var desc = this.querySelector('.dropdown-desc').textContent;
    document.getElementById('promptDropdownBtn').innerHTML =
      '<span class="dropdown-main">' + main + '</span>' +
      '<span class="dropdown-desc">' + desc + '</span>';
    // Update hidden input
    document.getElementById('promptConfigSelect').value = this.getAttribute('data-value');
    // Optionally, trigger your config change logic
    if (typeof onPromptConfigChange === 'function') onPromptConfigChange();
  });
});
</script>

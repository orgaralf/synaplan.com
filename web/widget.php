<?php
// Set content type to JavaScript
require_once('inc/_confsys.php');

header('Content-Type: application/javascript');

// Get the current domain for security checks
$currentDomain = $_SERVER['HTTP_HOST'];

// Get widget ID from URL parameters
$widgetId = isset($_REQUEST['widgetid']) ? $_REQUEST['widgetid'] : '';

// Get the base URL for the widget
$baseUrl = $GLOBALS["baseUrl"];
$widgetUrl = $baseUrl . "widgetloader.php" . ($widgetId ? "?widgetid=" . urlencode($widgetId) : "");

// Widget Configuration
$config = [
    // Button gradient colors (CSS color values)
    'button_gradient_from' => '#FF6B6B',
    'button_gradient_to' => '#4ECDC4',
    
    // Button position ('left' or 'right')
    'button_position' => 'right',
    
    // Button icon (Font Awesome class, e.g. 'fa-robot', 'fa-comments', 'fa-headset')
    'button_icon' => 'fa-comments',
    
    // Icon size in pixels
    'icon_size' => '24px'
];
// Output the widget JavaScript
?>
//alert('<?php echo $widgetUrl; ?>');
(function() {
    // Create widget container
    const widgetContainer = document.createElement('div');
    widgetContainer.id = 'synaplan-chat-widget';
    widgetContainer.style.cssText = `
        position: fixed;
        bottom: 20px;
        <?php echo $config['button_position']; ?>: 20px;
        z-index: 999999;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    `;

    // Create floating button
    const chatButton = document.createElement('button');
    chatButton.id = 'synaplan-chat-button';
    chatButton.style.cssText = `
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, <?php echo $config['button_gradient_from']; ?>, <?php echo $config['button_gradient_to']; ?>);
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        color: white;
        z-index: 999999;
    `;
    chatButton.innerHTML = '<i class="fas <?php echo $config['button_icon']; ?>" style="font-size: <?php echo $config['icon_size']; ?>;"></i>';

    // Create overlay container
    const overlay = document.createElement('div');
    overlay.id = 'synaplan-chat-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999998;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;

    // Create chat container
    const chatContainer = document.createElement('div');
    chatContainer.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 380px;
        height: 600px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        z-index: 9999999;
        display: none;
        opacity: 0;
        transition: all 0.3s ease;
        overflow: hidden;
    `;

    // Create close button
    const closeButton = document.createElement('button');
    closeButton.style.cssText = `
        position: absolute;
        top: 12px;
        right: 12px;
        background: none;
        border: none;
        color: #6c757d;
        font-size: 20px;
        cursor: pointer;
        z-index: 10000000;
        padding: 4px;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    `;
    closeButton.innerHTML = '×';
    closeButton.onmouseover = () => closeButton.style.background = '#f8f9fa';
    closeButton.onmouseout = () => closeButton.style.background = 'transparent';

    // Create iframe container (initially empty)
    const iframeContainer = document.createElement('div');
    iframeContainer.style.cssText = `
        width: 100%;
        height: 100%;
        background: white;
    `;

    // Add Font Awesome for icons
    const fontAwesome = document.createElement('link');
    fontAwesome.rel = 'stylesheet';
    fontAwesome.href = '<?php echo $baseUrl; ?>fa/css/all.min.css';
    document.head.appendChild(fontAwesome);

    // Assemble the widget
    chatContainer.appendChild(closeButton);
    chatContainer.appendChild(iframeContainer);
    overlay.appendChild(chatContainer);
    widgetContainer.appendChild(chatButton);
    document.body.appendChild(widgetContainer);
    document.body.appendChild(overlay);

    // Function to create and load iframe
    const loadChatFrame = () => {
        if (iframeContainer.children.length === 0) {
            const chatFrame = document.createElement('iframe');
            chatFrame.style.cssText = `
                width: 100%;
                height: 100%;
                border: none;
                background: white;
            `;
            chatFrame.src = '<?php echo $widgetUrl; ?>';
            iframeContainer.appendChild(chatFrame);
        }
    };

    // Handle button click
    chatButton.addEventListener('click', () => {
        overlay.style.display = 'block';
        chatContainer.style.display = 'block';
        setTimeout(() => {
            overlay.style.opacity = '1';
            chatContainer.style.opacity = '1';
            loadChatFrame(); // Load iframe content when button is clicked
        }, 10);
    });

    // Handle close button click
    closeButton.addEventListener('click', () => {
        overlay.style.opacity = '0';
        chatContainer.style.opacity = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
            chatContainer.style.display = 'none';
        }, 300);
    });

    // Close on overlay click
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeButton.click();
        }
    });

    // Add hover effect to chat button
    chatButton.onmouseover = () => {
        chatButton.style.transform = 'scale(1.1)';
        chatButton.style.boxShadow = '0 6px 16px rgba(0, 0, 0, 0.2)';
    };
    chatButton.onmouseout = () => {
        chatButton.style.transform = 'scale(1)';
        chatButton.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    };
})(); 
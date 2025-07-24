<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="contentMain">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Chat Widget Configuration</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.href='index.php/chat'">
                    <i class="fas fa-comments"></i> Chat
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.href='index.php/tools'">
                    <i class="fas fa-tools"></i> Tools
                </button>
            </div>
        </div>
    </div>

    <form id="webwidgetForm" method="POST" action="index.php/webwidget">
        <input type="hidden" name="action" id="action" value="updateWebwidget">
        
        <!-- Widget Appearance Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-palette"></i> Widget Appearance</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="widgetTitle" class="col-sm-2 col-form-label"><strong>Widget Title:</strong></label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" name="widgetTitle" id="widgetTitle" placeholder="Chat with us" value="Chat with us">
                        <div class="form-text">Title displayed in the chat widget header</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="widgetColor" class="col-sm-2 col-form-label"><strong>Primary Color:</strong></label>
                    <div class="col-sm-4">
                        <input type="color" class="form-control form-control-color" name="widgetColor" id="widgetColor" value="#007bff">
                        <div class="form-text">Main color theme for the widget</div>
                    </div>
                    <label for="widgetPosition" class="col-sm-2 col-form-label"><strong>Position:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="widgetPosition" id="widgetPosition">
                            <option value="bottom-right">Bottom Right</option>
                            <option value="bottom-left">Bottom Left</option>
                            <option value="top-right">Top Right</option>
                            <option value="top-left">Top Left</option>
                        </select>
                        <div class="form-text">Widget position on your website</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="widgetSize" class="col-sm-2 col-form-label"><strong>Widget Size:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="widgetSize" id="widgetSize">
                            <option value="small">Small</option>
                            <option value="medium" selected>Medium</option>
                            <option value="large">Large</option>
                        </select>
                        <div class="form-text">Size of the chat widget</div>
                    </div>
                    <label for="widgetLanguage" class="col-sm-2 col-form-label"><strong>Language:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="widgetLanguage" id="widgetLanguage">
                            <option value="en">English</option>
                            <option value="de">Deutsch</option>
                            <option value="fr">Français</option>
                            <option value="es">Español</option>
                            <option value="it">Italiano</option>
                        </select>
                        <div class="form-text">Widget interface language</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget Behavior Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-cog"></i> Widget Behavior</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="autoOpen" class="col-sm-2 col-form-label"><strong>Auto Open:</strong></label>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="autoOpen" id="autoOpen">
                            <label class="form-check-label" for="autoOpen">
                                Automatically open widget after page load
                            </label>
                        </div>
                        <div class="form-text">Opens chat widget automatically</div>
                    </div>
                    <label for="delaySeconds" class="col-sm-2 col-form-label"><strong>Delay (seconds):</strong></label>
                    <div class="col-sm-4">
                        <input type="number" class="form-control" name="delaySeconds" id="delaySeconds" placeholder="5" min="0" max="60" value="5">
                        <div class="form-text">Delay before auto-opening (if enabled)</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="showGreeting" class="col-sm-2 col-form-label"><strong>Show Greeting:</strong></label>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="showGreeting" id="showGreeting" checked>
                            <label class="form-check-label" for="showGreeting">
                                Show welcome message
                            </label>
                        </div>
                        <div class="form-text">Display initial greeting message</div>
                    </div>
                    <label for="greetingMessage" class="col-sm-2 col-form-label"><strong>Greeting Message:</strong></label>
                    <div class="col-sm-4">
                        <input type="text" class="form-control" name="greetingMessage" id="greetingMessage" placeholder="Hello! How can I help you today?" value="Hello! How can I help you today?">
                        <div class="form-text">Initial message shown to visitors</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integration Code Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-code"></i> Integration Code</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="integrationCode" class="col-sm-2 col-form-label"><strong>Embed Code:</strong></label>
                    <div class="col-sm-10">
                        <textarea class="form-control" name="integrationCode" id="integrationCode" rows="6" readonly>
<!-- Synaplan Chat Widget -->
<script>
(function() {
    var script = document.createElement('script');
    script.src = 'https://your-domain.com/widget.js';
    script.async = true;
    document.head.appendChild(script);
})();
</script>
                        </textarea>
                        <div class="form-text">Copy this code to your website's &lt;head&gt; section</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i> Copy Code
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="generateNewCode()">
                            <i class="fas fa-refresh"></i> Generate New Code
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body text-center">
                <div class="btn-group" role="group" aria-label="Webwidget actions">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg" onclick="loadWebwidgetConfig()">
                        <i class="fas fa-refresh"></i> Reset Form
                    </button>
                    <button type="button" class="btn btn-info btn-lg" onclick="previewWidget()">
                        <i class="fas fa-eye"></i> Preview Widget
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Widget configuration will be applied to all pages where the integration code is installed.</small>
                </div>
            </div>
        </div>

    </form>
</main>

<script>
    // Load webwidget configuration when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadWebwidgetConfig();
    });

    // Function to load current webwidget configuration
    function loadWebwidgetConfig() {
        // TODO: Implement API call to load configuration
        console.log('Loading webwidget configuration...');
    }

    // Function to copy integration code to clipboard
    function copyToClipboard() {
        const textArea = document.getElementById('integrationCode');
        textArea.select();
        document.execCommand('copy');
        
        // Show feedback
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    }

    // Function to generate new integration code
    function generateNewCode() {
        // TODO: Implement code generation
        console.log('Generating new integration code...');
    }

    // Function to preview widget
    function previewWidget() {
        // TODO: Implement widget preview
        console.log('Opening widget preview...');
    }
</script>

<style>
    .card-header h5 {
        color: #495057;
    }
    .btn-group .btn {
        min-width: 150px;
    }
    .text-muted {
        font-size: 0.875rem;
    }
    .form-control-color {
        width: 100%;
        height: 38px;
    }
</style> 
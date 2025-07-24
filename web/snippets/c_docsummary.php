<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="contentMain">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Document Summary Tool</h1>
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

    <form id="docSummaryForm" method="POST" action="index.php/docsummary">
        <input type="hidden" name="action" id="action" value="processSummary">
        
        <!-- Input Configuration Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-cog"></i> Summary Configuration</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="summaryType" class="col-sm-2 col-form-label"><strong>Summary Type:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="summaryType" id="summaryType">
                            <option value="extractive">Extractive Summary</option>
                            <option value="abstractive">Abstractive Summary</option>
                            <option value="bullet_points">Bullet Points</option>
                            <option value="key_points">Key Points</option>
                            <option value="executive">Executive Summary</option>
                        </select>
                        <div class="form-text">Type of summary to generate</div>
                    </div>
                    <label for="summaryLength" class="col-sm-2 col-form-label"><strong>Length:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="summaryLength" id="summaryLength">
                            <option value="short">Short (100-200 words)</option>
                            <option value="medium" selected>Medium (200-500 words)</option>
                            <option value="long">Long (500-1000 words)</option>
                            <option value="custom">Custom Length</option>
                        </select>
                        <div class="form-text">Desired summary length</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="customLength" class="col-sm-2 col-form-label"><strong>Custom Length:</strong></label>
                    <div class="col-sm-4">
                        <input type="number" class="form-control" name="customLength" id="customLength" placeholder="300" min="50" max="2000" disabled>
                        <div class="form-text">Number of words (50-2000)</div>
                    </div>
                    <label for="language" class="col-sm-2 col-form-label"><strong>Output Language:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="language" id="language">
                            <option value="en">English</option>
                            <option value="de">Deutsch</option>
                            <option value="fr">Français</option>
                            <option value="es">Español</option>
                            <option value="it">Italiano</option>
                        </select>
                        <div class="form-text">Language for the summary output</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="focusAreas" class="col-sm-2 col-form-label"><strong>Focus Areas:</strong></label>
                    <div class="col-sm-10">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="focusAreas[]" id="focusMain" value="main_ideas" checked>
                            <label class="form-check-label" for="focusMain">Main Ideas</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="focusAreas[]" id="focusKey" value="key_facts" checked>
                            <label class="form-check-label" for="focusKey">Key Facts</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="focusAreas[]" id="focusConclusions" value="conclusions">
                            <label class="form-check-label" for="focusConclusions">Conclusions</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="focusAreas[]" id="focusActions" value="action_items">
                            <label class="form-check-label" for="focusActions">Action Items</label>
                        </div>
                        <div class="form-text">Select what to focus on in the summary</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Input Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-file-alt"></i> Document Input</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="inputMethod" class="col-sm-2 col-form-label"><strong>Input Method:</strong></label>
                    <div class="col-sm-10">
                        <div class="btn-group" role="group" aria-label="Input method">
                            <input type="radio" class="btn-check" name="inputMethod" id="textInput" value="text" checked>
                            <label class="btn btn-outline-primary" for="textInput">
                                <i class="fas fa-keyboard"></i> Text Input
                            </label>
                            
                            <input type="radio" class="btn-check" name="inputMethod" id="fileUpload" value="file">
                            <label class="btn btn-outline-primary" for="fileUpload">
                                <i class="fas fa-upload"></i> File Upload
                            </label>
                            
                            <input type="radio" class="btn-check" name="inputMethod" id="urlInput" value="url">
                            <label class="btn btn-outline-primary" for="urlInput">
                                <i class="fas fa-link"></i> URL
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Text Input -->
                <div id="textInputSection" class="input-section">
                    <div class="row mb-3">
                        <label for="documentText" class="col-sm-2 col-form-label"><strong>Document Text:</strong></label>
                        <div class="col-sm-10">
                            <textarea class="form-control" name="documentText" id="documentText" rows="15" placeholder="Paste your document text here..."></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span> characters | 
                                <span id="wordCount">0</span> words | 
                                <span id="estimatedTokens">0</span> estimated tokens
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Upload -->
                <div id="fileUploadSection" class="input-section" style="display: none;">
                    <div class="row mb-3">
                        <label for="documentFile" class="col-sm-2 col-form-label"><strong>Upload File:</strong></label>
                        <div class="col-sm-10">
                            <input type="file" class="form-control" name="documentFile" id="documentFile" accept=".txt,.doc,.docx,.pdf,.rtf">
                            <div class="form-text">Supported formats: TXT, DOC, DOCX, PDF, RTF (max 10MB)</div>
                        </div>
                    </div>
                </div>

                <!-- URL Input -->
                <div id="urlInputSection" class="input-section" style="display: none;">
                    <div class="row mb-3">
                        <label for="documentUrl" class="col-sm-2 col-form-label"><strong>Document URL:</strong></label>
                        <div class="col-sm-10">
                            <input type="url" class="form-control" name="documentUrl" id="documentUrl" placeholder="https://example.com/document">
                            <div class="form-text">Enter the URL of the document to summarize</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Processing Options Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-sliders-h"></i> Processing Options</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="includeMetadata" class="col-sm-2 col-form-label"><strong>Include Metadata:</strong></label>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="includeMetadata" id="includeMetadata" checked>
                            <label class="form-check-label" for="includeMetadata">
                                Include document metadata
                            </label>
                        </div>
                        <div class="form-text">Add source info and processing details</div>
                    </div>
                    <label for="preserveFormatting" class="col-sm-2 col-form-label"><strong>Preserve Formatting:</strong></label>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="preserveFormatting" id="preserveFormatting">
                            <label class="form-check-label" for="preserveFormatting">
                                Keep original formatting
                            </label>
                        </div>
                        <div class="form-text">Maintain paragraph structure</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="confidenceThreshold" class="col-sm-2 col-form-label"><strong>Confidence Threshold:</strong></label>
                    <div class="col-sm-4">
                        <input type="range" class="form-range" name="confidenceThreshold" id="confidenceThreshold" min="0" max="100" value="70">
                        <div class="form-text">Minimum confidence level: <span id="confidenceValue">70</span>%</div>
                    </div>
                    <label for="maxProcessingTime" class="col-sm-2 col-form-label"><strong>Max Processing Time:</strong></label>
                    <div class="col-sm-4">
                        <select class="form-select" name="maxProcessingTime" id="maxProcessingTime">
                            <option value="30">30 seconds</option>
                            <option value="60" selected>1 minute</option>
                            <option value="120">2 minutes</option>
                            <option value="300">5 minutes</option>
                        </select>
                        <div class="form-text">Maximum time to wait for processing</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body text-center">
                <div class="btn-group" role="group" aria-label="Document summary actions">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-magic"></i> Generate Summary
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg" onclick="clearForm()">
                        <i class="fas fa-eraser"></i> Clear Form
                    </button>
                    <button type="button" class="btn btn-info btn-lg" onclick="loadSampleText()">
                        <i class="fas fa-file-text"></i> Load Sample
                    </button>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Processing large documents may take several minutes. Please be patient.</small>
                </div>
            </div>
        </div>

    </form>

    <!-- Results Section (Hidden by default) -->
    <div id="resultsSection" class="card mt-4" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line text-success"></i> Summary Results
            </h5>
        </div>
        <div class="card-body">
            <div id="summaryContent">
                <!-- Summary content will be inserted here -->
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-outline-primary" onclick="downloadSummary()">
                    <i class="fas fa-download"></i> Download Summary
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="copySummary()">
                    <i class="fas fa-copy"></i> Copy to Clipboard
                </button>
            </div>
        </div>
    </div>
</main>

<script>
    // Load page when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeForm();
    });

    // Initialize form functionality
    function initializeForm() {
        // Handle input method changes
        document.querySelectorAll('input[name="inputMethod"]').forEach(radio => {
            radio.addEventListener('change', toggleInputSections);
        });

        // Handle summary length changes
        document.getElementById('summaryLength').addEventListener('change', toggleCustomLength);

        // Handle confidence threshold slider
        document.getElementById('confidenceThreshold').addEventListener('input', updateConfidenceValue);

        // Handle text input changes for character/word counting
        document.getElementById('documentText').addEventListener('input', updateCounts);
    }

    // Toggle input sections based on selected method
    function toggleInputSections() {
        const sections = document.querySelectorAll('.input-section');
        sections.forEach(section => section.style.display = 'none');
        
        const selectedMethod = document.querySelector('input[name="inputMethod"]:checked').value;
        document.getElementById(selectedMethod + 'Section').style.display = 'block';
    }

    // Toggle custom length input
    function toggleCustomLength() {
        const customLength = document.getElementById('customLength');
        const isCustom = document.getElementById('summaryLength').value === 'custom';
        customLength.disabled = !isCustom;
    }

    // Update confidence value display
    function updateConfidenceValue() {
        const value = document.getElementById('confidenceThreshold').value;
        document.getElementById('confidenceValue').textContent = value;
    }

    // Update character and word counts
    function updateCounts() {
        const text = document.getElementById('documentText').value;
        const charCount = text.length;
        const wordCount = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        const estimatedTokens = Math.ceil(charCount / 4); // Rough estimation

        document.getElementById('charCount').textContent = charCount.toLocaleString();
        document.getElementById('wordCount').textContent = wordCount.toLocaleString();
        document.getElementById('estimatedTokens').textContent = estimatedTokens.toLocaleString();
    }

    // Clear form
    function clearForm() {
        document.getElementById('docSummaryForm').reset();
        document.getElementById('documentText').value = '';
        updateCounts();
        document.getElementById('resultsSection').style.display = 'none';
    }

    // Load sample text
    function loadSampleText() {
        const sampleText = `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.`;
        
        document.getElementById('documentText').value = sampleText;
        updateCounts();
    }

    // Download summary
    function downloadSummary() {
        // TODO: Implement download functionality
        console.log('Downloading summary...');
    }

    // Copy summary to clipboard
    function copySummary() {
        // TODO: Implement copy functionality
        console.log('Copying summary to clipboard...');
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
    .form-range {
        height: 6px;
    }
    .input-section {
        transition: all 0.3s ease;
    }
</style> 
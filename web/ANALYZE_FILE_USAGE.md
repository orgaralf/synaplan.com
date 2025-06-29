# File Analysis with OpenAI

## Overview

The `analyzeFile` method in the `AIOpenAI` class allows you to upload files to OpenAI and get comprehensive analysis of their content. This method supports various file types including:

- Office documents (Word, Excel, PowerPoint)
- PDF files
- Code files
- Text documents
- And other document types supported by OpenAI

## Method Signature

```php
public static function analyzeFile($msgArr, $stream = false): array|string|bool
```

## Parameters

### `$msgArr` (array)
A message array containing file information and analysis prompt:

```php
$msgArr = [
    'BID' => 12345,                    // Message ID
    'BUSERID' => 67890,                // User ID
    'BFILE' => 1,                      // File flag (1 = has file)
    'BFILEPATH' => 'document.pdf',     // File path relative to ./up/ directory
    'BFILETYPE' => 'pdf',              // File type/extension
    'BTEXT' => 'Please analyze this document...', // Analysis prompt (optional)
    'BLANG' => 'en',                   // Language code
    'BDIRECT' => 'IN',                 // Message direction
    'BDATETIME' => '2024-01-01 12:00:00', // Timestamp
    'BUNIXTIMES' => 1704110400         // Unix timestamp
];
```

### `$stream` (bool, optional)
Whether to stream progress updates. Default: `false`

## Return Value

### Success
Returns an updated message array with the analysis results:

```php
[
    'BID' => 12345,
    'BTEXT' => 'Analysis results...',  // The analysis text
    'BDIRECT' => 'OUT',               // Changed to OUT
    'BDATETIME' => '2024-01-01 12:05:00',
    'BUNIXTIMES' => 1704110700,
    'BFILE' => 0,                     // Cleared
    'BFILEPATH' => '',                // Cleared
    'BFILETYPE' => '',                // Cleared
    'BFILETEXT' => ''                 // Cleared
]
```

### Error
Returns an error string starting with "*API Error - ":

```php
"*API Error - File not found: document.pdf"
"*API Error - Failed to initialize OpenAI client"
"*API Error - Analysis failed with status: failed"
```

## Usage Examples

### Basic Usage

```php
// Prepare message array
$msgArr = [
    'BID' => 12345,
    'BUSERID' => 67890,
    'BFILE' => 1,
    'BFILEPATH' => 'report.pdf',
    'BFILETYPE' => 'pdf',
    'BTEXT' => 'Please analyze this report and summarize the key findings.',
    'BLANG' => 'en',
    'BDIRECT' => 'IN',
    'BDATETIME' => date('Y-m-d H:i:s'),
    'BUNIXTIMES' => time()
];

// Analyze the file
$result = AIOpenAI::analyzeFile($msgArr);

if (is_array($result)) {
    echo "Analysis: " . $result['BTEXT'];
} else {
    echo "Error: " . $result;
}
```

### With Streaming Updates

```php
$result = AIOpenAI::analyzeFile($msgArr, true);

// The method will automatically call Frontend::printToStream() 
// with progress updates like:
// - "Uploading file to OpenAI... "
// - "File uploaded: file-abc123... "
// - "Analyzing file... "
// - "Processing: 1... "
// - "Analysis completed, extracting results... "
// - "Analysis completed successfully "
```

### Custom Analysis Prompt

```php
$msgArr['BTEXT'] = 'Please analyze this code file and identify any potential security vulnerabilities, code quality issues, and suggest improvements.';

$result = AIOpenAI::analyzeFile($msgArr);
```

### Default Analysis Prompt

If no custom prompt is provided in `BTEXT`, the method uses this default prompt:

```
"Please analyze the attached file. Provide a comprehensive analysis including: 
1) Document type and structure, 2) Key content and main points, 
3) Important findings or insights, 4) Any recommendations or observations. 
Be thorough and professional in your analysis."
```

## File Requirements

- File must exist in the `./up/` directory
- File path in `BFILEPATH` should be relative to `./up/`
- Supported file types depend on OpenAI's file analysis capabilities
- File size limits apply according to OpenAI's API specifications

## Database Integration

The method automatically stores file metadata in the `BMESSAGEMETA` table:

```sql
INSERT INTO BMESSAGEMETA (BID, BMESSID, BTOKEN, BVALUE) 
VALUES (DEFAULT, 12345, 'OPENAI_FILE_ID', 'file-abc123');
```

This allows tracking of uploaded files for future reference.

## Error Handling

The method handles various error scenarios:

- **File not found**: Returns error if file doesn't exist
- **API key missing**: Returns error if OpenAI API key is not configured
- **Upload failure**: Returns error if file upload fails
- **Analysis timeout**: Returns error if analysis takes longer than 5 minutes
- **Analysis failure**: Returns error if OpenAI analysis fails

## Integration with Existing Code

This method follows the same patterns as other methods in the `AIOpenAI` class:

- Uses the same error message format
- Follows the same return value structure
- Integrates with the existing streaming system
- Uses the same database patterns for metadata storage

## Testing

Use the provided test file `test_analyze_file.php` to test the functionality:

```bash
php test_analyze_file.php
```

Make sure to:
1. Place a test file in the `./up/` directory
2. Update the file path in the test
3. Ensure OpenAI API key is configured
4. Check that all dependencies are installed 
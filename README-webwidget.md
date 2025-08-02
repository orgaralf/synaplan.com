# Anonymous Widget Implementation Guide

## Overview

The Anonymous Widget system allows external websites to embed a chat widget from synaplan.com without requiring users to log in. Anonymous users can interact with the AI chat system while maintaining security and data privacy compliance.

## Architecture

### Core Components

1. **Widget Loader** (`web/widgetloader.php`)
   - Entry point for embedded widgets
   - Creates anonymous sessions
   - Loads widget configuration

2. **Session Management** (`web/inc/_frontend.php`)
   - Handles anonymous session creation and validation
   - Manages session timeouts (24 hours)
   - Prevents login access for anonymous users

3. **API Layer** (`web/api.php`)
   - RESTful API endpoints
   - Rate limiting (30 requests/minute for anonymous users)
   - Authentication and authorization

4. **Frontend Interface** (`web/snippets/c_chat.php`, `web/js/chat.js`)
   - Chat interface for anonymous users
   - File upload restrictions (JPG, GIF, PNG, PDF only)
   - Disabled features for anonymous users

5. **Data Cleanup** (`web/cleanup_anonymous_data.php`)
   - Automatic deletion of old anonymous data (4 weeks)
   - GDPR compliance

## Usage

### For Widget Owners (Website Administrators)

1. **Configure Widget**:
   ```php
   // Access widget configuration in synaplan admin panel
   // Set color, position, auto-message, and AI prompt
   ```

2. **Embed Widget**:
   ```html
   <iframe src="https://synaplan.com/web/widgetloader.php?uid=YOUR_USER_ID&widgetid=1" 
           width="400" height="600" 
           frameborder="0">
   </iframe>
   ```

### For Anonymous Users

- No registration required
- Chat with AI assistant
- Upload files (JPG, GIF, PNG, PDF only)
- Session expires after 24 hours
- Data automatically deleted after 4 weeks

## Code Structure

### Session Variables

```php
// Anonymous widget session variables
$_SESSION["is_widget"] = true;                    // Identifies anonymous session
$_SESSION["widget_owner_id"] = $uid;             // Widget owner's user ID
$_SESSION["widget_id"] = $widgetId;              // Widget configuration ID (1-9)
$_SESSION["anonymous_session_id"] = $hash;       // Unique session identifier
$_SESSION["anonymous_session_created"] = $time;  // Session creation timestamp
```

### Database Schema

**BMESSAGES Table**:
- `BUSERID`: Set to widget owner's ID
- `BTRACKID`: CRC32 hash of anonymous session ID for grouping
- `BTEXT`: Prefixed with "WEBWIDGET: " for identification

**BRAG Table**:
- `BGROUPKEY`: Set to "WIDGET" for anonymous files
- `BMID`: Links to message ID

## Configuration Options

### Widget Settings (BCONFIG Table)

```sql
-- Widget configuration stored in BCONFIG table
-- Group: "widget_1" to "widget_9"
-- Settings: color, position, autoMessage, prompt
```

### Rate Limiting

```php
// In api.php - Rate limiting configuration
$rateLimitResult = checkRateLimit($rateLimitKey, 60, 30); // 30 requests per minute
```

### Session Timeout

```php
// In _frontend.php - Session timeout configuration
$sessionTimeout = 86400; // 24 hours in seconds
```

### Data Retention

```php
// In cleanup_anonymous_data.php - Data retention configuration
$retentionDays = 28; // 4 weeks
```

## API Endpoints

### Anonymous Users (Limited Access)
- `messageNew` - Send new messages
- `chatStream` - Stream AI responses
- `getMessageFiles` - Get file details

### Authenticated Users (Full Access)
- `ragUpload` - Upload RAG files
- `docSum` - Document summarization
- `promptLoad` - Load prompts
- `loadChatHistory` - Load chat history
- `getWidgets` - Get widget configurations
- `saveWidget` - Save widget settings
- `deleteWidget` - Delete widget

## Security Features

### Session Isolation
- Anonymous sessions never set `$_SESSION["USERPROFILE"]`
- Complete separation from authenticated sessions
- No access to authenticated-only features

### Rate Limiting
- 30 requests per minute per widget
- Session-based rate limiting
- Automatic blocking of excessive requests

### File Upload Restrictions
- Anonymous users: JPG, GIF, PNG, PDF only
- Authenticated users: All file types allowed
- MIME type validation on both client and server

### Data Privacy
- No personal data collection
- Automatic data deletion after 4 weeks
- GDPR compliant

## Customization Guide

### Modifying Widget Appearance

**Widget Header** (`web/widgetloader.php`):
```php
// Change widget title
<div class="widget-header">
    Chat Support  <!-- Modify this text -->
</div>

// Change default styling
<style>
    .widget-header {
        background: <?php echo $config['color']; ?>;  // Customizable color
        /* Add custom CSS here */
    }
</style>
```

### Adding New Widget Settings

1. **Database Configuration**:
```sql
-- Add new setting to BCONFIG table
INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) 
VALUES (user_id, 'widget_1', 'newSetting', 'defaultValue');
```

2. **Load Configuration** (`web/widgetloader.php`):
```php
$config = [
    'color' => '#007bff',
    'position' => 'bottom-right',
    'autoMessage' => '',
    'prompt' => 'general',
    'newSetting' => 'defaultValue'  // Add new setting
];
```

3. **Use in Frontend**:
```php
// Access new setting
$newValue = $config['newSetting'];
```

### Modifying Rate Limits

**Change Rate Limit** (`web/api.php`):
```php
// Modify these values
$rateLimitResult = checkRateLimit($rateLimitKey, 60, 30); 
//                                    ^window ^maxRequests
```

### Adding New File Types

**Server-side** (`web/inc/_central.php`):
```php
public static function checkMimeTypesForAnonymous($extension, $mimeType): bool {
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt']; // Add new types
    $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'text/plain'  // Add new MIME types
    ];
    // ... rest of function
}
```

**Client-side** (`web/js/chat.js`):
```javascript
function isFileTypeAllowed(file) {
    if (!isAnonymousWidget) {
        return true;
    }
    
    const allowedTypes = [
        'image/jpeg',
        'image/jpg', 
        'image/gif',
        'image/png',
        'application/pdf',
        'text/plain'  // Add new types
    ];
    
    const allowedExtensions = ['.jpg', '.jpeg', '.gif', '.png', '.pdf', '.txt']; // Add extensions
    // ... rest of function
}
```

### Modifying Session Timeout

**Change Timeout** (`web/inc/_frontend.php`):
```php
public static function validateAnonymousSession(): bool {
    // ... existing code ...
    $sessionTimeout = 86400; // 24 hours - modify this value
    // ... rest of function
}
```

### Adding New API Endpoints

1. **Define Access Level** (`web/api.php`):
```php
// Add to appropriate array
$anonymousAllowedEndpoints = [
    'messageNew',
    'chatStream',
    'getMessageFiles',
    'newEndpoint'  // Add new endpoint
];
```

2. **Add Handler** (`web/api.php`):
```php
switch($apiAction) {
    // ... existing cases ...
    case 'newEndpoint':
        $resArr = YourClass::newMethod();
        break;
}
```

## Maintenance

### Daily Cleanup

**Setup Cron Job**:
```bash
# Add to crontab (runs daily at 2 AM)
0 2 * * * /path/to/synaplan.com/web/cron_cleanup_anonymous.sh
```

**Manual Cleanup**:
```bash
cd /path/to/synaplan.com/web/
php cleanup_anonymous_data.php
```

### Monitoring

**Check Logs**:
```bash
# View cleanup logs
tail -f web/logs/cleanup_anonymous.log

# View cron logs
tail -f web/logs/cron_cleanup.log
```

### Database Queries

**Check Anonymous Usage**:
```sql
-- Count anonymous messages
SELECT COUNT(*) FROM BMESSAGES WHERE BTEXT LIKE 'WEBWIDGET: %';

-- Check anonymous RAG entries
SELECT COUNT(*) FROM BRAG WHERE BGROUPKEY = 'WIDGET';

-- Find old anonymous data
SELECT * FROM BMESSAGES 
WHERE BTEXT LIKE 'WEBWIDGET: %' 
AND BUNIXTIMES < (UNIX_TIMESTAMP() - (28 * 24 * 60 * 60));
```

## Troubleshooting

### Common Issues

1. **Widget Not Loading**:
   - Check `uid` and `widgetid` parameters
   - Verify widget configuration exists in BCONFIG table
   - Check server logs for errors

2. **Session Expired**:
   - Anonymous sessions expire after 24 hours
   - Users need to refresh the page
   - Check session timeout configuration

3. **Rate Limit Exceeded**:
   - Anonymous users limited to 30 requests/minute
   - Check rate limiting configuration
   - Monitor for abuse

4. **File Upload Fails**:
   - Anonymous users restricted to JPG, GIF, PNG, PDF
   - Check file type validation
   - Verify MIME type checking

### Debug Mode

**Enable Debug Logging**:
```php
// In _coreincludes.php or configuration
$GLOBALS["debug"] = true;
```

**Check Session State**:
```php
// Add to any PHP file for debugging
var_dump($_SESSION);
```

## Security Checklist

- [ ] Anonymous sessions never set `$_SESSION["USERPROFILE"]`
- [ ] Rate limiting is enabled and configured
- [ ] File upload restrictions are enforced
- [ ] Session timeout is working
- [ ] Data cleanup is running daily
- [ ] API endpoints are properly protected
- [ ] No personal data is collected
- [ ] GDPR compliance measures are in place

## Support

For issues or questions about the anonymous widget implementation:

1. Check this documentation
2. Review the code comments
3. Check server logs
4. Verify database configuration
5. Test with different browsers/devices

## Version History

- **v1.0**: Initial implementation with basic chat functionality
- **v1.1**: Added file upload support with restrictions
- **v1.2**: Implemented rate limiting and session timeout
- **v1.3**: Added automatic data cleanup for GDPR compliance 
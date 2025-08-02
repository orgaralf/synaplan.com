# Anonymous Widget Implementation Plan

## Overview
This document outlines the implementation steps to enable anonymous usage of the chat widget from external domains, where users can interact with the chat without being logged into the synaplan system.

## Process

I have various steps for you. I will post them here, step by step and push you forward. Do not try to be faster than my prompts.
There are 7 steps following. Do 1 step and then ask for instructions to the next, please.

## Current System Analysis

### Current Widget Flow
1. `widgetloader.php` loads widget configuration from database using `uid` parameter
2. Sets session variables `WIDGET_PROMPT` and `WIDGET_AUTO_MESSAGE`
3. Includes `c_chat.php` which requires `$_SESSION["USERPROFILE"]` for all operations
4. All API calls (`api.php`) require authenticated user session
5. File uploads and chat messages are tied to specific user IDs

### Key Issues for Anonymous Usage
1. No session management for anonymous users
2. All database operations require a user id to store the message in BMESSAGES (BUSERID) set from `$_SESSION["USERPROFILE"]["BID"]`
3. File uploads are stored in user-specific directories
4. Chat history and message processing require authenticated user context
5. No mechanism to distinguish between authenticated and anonymous sessions

## Implementation Steps

## Step 1: Session Management for Anonymous Users

### Step 1.1: Modify widgetloader.php
- **File**: `web/widgetloader.php`
- **Changes**:
  - Add session variable `$_SESSION["is_widget"] = true` when widget is loaded
  - Add session variable `$_SESSION["widget_owner_id"] = $uid` to track widget owner
  - Add session variable `$_SESSION["widget_id"] = $widgetId` for widget configuration
  - Set base href correctly for CSS/JS resources in iframe context
  - Ensure proper headers for cross-origin iframe embedding

### Step 1.2: Create Anonymous User Session Handler
- **File**: `web/inc/_frontend.php` (new method)
- **Method**: `setAnonymousWidgetSession($ownerId, $widgetId)`
- **Purpose**: Create temporary session for anonymous widget users
- **Implementation**:
  - Generate unique anonymous session ID
  - Store widget owner ID and widget ID in session
  - Set `$_SESSION["is_widget"] = true`
  - Set `$_SESSION["anonymous_session_id"] = unique_id`
  - Do NOT set `$_SESSION["USERPROFILE"]` to prevent login access
  - Save the messages as the widget owner

### Step 2: Database Schema Updates

#### Step 2.1: Do not create temporary users
- **Table**: `BUSER`
- **Approach**: Implement an anonymous posting of support requests
- **Implementation**:
  - Prepend "WEBWIDGET: " in the ProcessMethods::saveAnswwerToDB() method before BTEXT
  - Set a unique tracking ID in BTRACKID for that session for that user to avoid cross-loading of parallel chats
  - Set the tracking ID for the session and reference it, if the anonymous user continues to chat in the Thread loading: Central::getThread(), use a switch by is_widget session setting. Check in @BUSER.sql file in db-loadfiles, if the tracking ID column is allowing 32 chars

### Step 3: API and Backend Modifications

#### Step 3.1: Update Frontend::saveWebMessages()
- **File**: `web/inc/_frontend.php`
- **Changes**:
  - Check for `$_SESSION["is_widget"]` flag
  - If widget mode and no `$_SESSION["USERPROFILE"]`, use anonymous user ID
  - Store messages with anonymous settings but link to widget owner in BUSERID
  - Limit file uploads to JPG, GIF, PNG, PDF only for anonymous users, use the session switch
  - Store the files in the user directory, but set the grouping default to "WIDGET" in the BRAG table (vectorization)

#### Step 3.2: Update Frontend::getLatestChats()
- **File**: `web/inc/_frontend.php`
- **Changes**:
  - Handle anonymous user chat history retrieval, use the tracking ID mentioned earlier (BTRACKID)
  - Filter messages by anonymous session ID
  - Group messages by widget session rather than user session

#### Step 3.3: Update Frontend::chatStream()
- **File**: `web/inc/_frontend.php`
- **Changes**:
  - Support anonymous user message processing
  - Use widget owner's prompt configuration
  - Ensure proper session context for AI processing

#### Step 3.4: Update API Authentication
- **File**: `web/api.php`
- **Changes**:
  - Add anonymous session validation (webwidget calls with ID and owner reference)
  - Allow API calls for anonymous widget users
  - Implement session-based rate limiting for anonymous users
  - Prevent anonymous users from accessing authenticated-only endpoints

### Phase 4: Frontend Interface Modifications

#### Step 4.1: Update c_chat.php for Anonymous Mode
- **File**: `web/snippets/c_chat.php`
- **Changes**:
  - Detect anonymous widget mode using `$_SESSION["is_widget"]`
  - Hide authenticated-only features (microphone, prompt dropdown)
  - Simplify interface for anonymous users with simple if/then blocks to hide complex features
  - Use widget-specific prompt configuration
  - Ensure proper base href for resources

#### Step 4.2: Update chat.js for Anonymous Users
- **File**: `web/js/chat.js`
- **Changes**:
  - Disable microphone functionality for anonymous users
  - Hide prompt selection dropdown
  - Limit file upload types to JPG, GIF, PNG, PDF
  - Add anonymous session handling in API calls
  - Ensure proper error handling for anonymous sessions

#### Step 4.3: Update chathistory.js
- **File**: `web/js/chathistory.js`
- **Changes**:
  - Handle anonymous user chat history loading
  - Ensure proper session context in API calls
  - Limit history loading for anonymous users (e.g., max 10 messages)

### Phase 5: File Upload and Storage

#### Step 5.1: Anonymous File Storage
- **Directory Structure**: `web/up/anonymous/{session_id}/{date}/`
- **Implementation**:
  - Create anonymous-specific upload directories
  - Store files with anonymous session ID
  - Link files to widget owner for processing
  - Implement cleanup for old anonymous files

#### Step 5.2: File Type Validation
- **Allowed Types**: JPG, GIF, PNG, PDF only
- **Implementation**:
  - Update `Central::checkMimeTypes()` for anonymous users
  - Add file type validation in frontend JavaScript
  - Show appropriate error messages for unsupported file types

### Phase 6: Security and Access Control

#### Step 6.1: Prevent Anonymous Login Access
- **Implementation**:
  - Ensure `$_SESSION["USERPROFILE"]` is never set for anonymous users
  - Add checks in login-related functions
  - Prevent anonymous users from accessing authenticated areas
  - Implement session validation in all authenticated endpoints

#### Step 6.2: Rate Limiting and Abuse Prevention
- **Implementation**:
  - Add rate limiting for anonymous widget sessions
  - Implement session timeout for anonymous users
  - Add abuse detection and prevention
  - Monitor and log anonymous widget usage

#### Step 6.3: Data Privacy and Cleanup
- **Implementation**:
  - Implement automatic cleanup of old anonymous sessions
  - Add data retention policies for anonymous chat data
  - Ensure GDPR compliance for anonymous user data
  - Add privacy notices for anonymous widget usage

### Phase 7: Testing and Validation

#### Step 7.1: Widget Functionality Testing
- **Test Cases**:
  - Anonymous widget loading from external domains
  - Chat functionality without authentication
  - File upload with restricted file types
  - Chat history loading for anonymous sessions
  - Proper resource loading (CSS, JS, images)

#### Step 7.2: Security Testing
- **Test Cases**:
  - Anonymous users cannot access authenticated areas
  - Session isolation between anonymous and authenticated users
  - File upload security and validation
  - Cross-origin iframe security
  - Rate limiting effectiveness

#### Step 7.3: Integration Testing
- **Test Cases**:
  - Widget integration on external websites
  - Cross-browser compatibility
  - Mobile device compatibility
  - Performance under load
  - Error handling and recovery

## Implementation Order

### Priority 1 (Core Functionality)
1. Session management for anonymous users
2. Basic chat functionality for anonymous users
3. File upload restrictions and storage
4. API modifications for anonymous support

### Priority 2 (User Experience)
1. Frontend interface simplifications
2. Proper resource loading in iframe
3. Chat history for anonymous sessions
4. Error handling and user feedback

### Priority 3 (Security & Polish)
1. Security hardening and access control
2. Rate limiting and abuse prevention
3. Data cleanup and privacy compliance
4. Performance optimization

## Database Changes Required

### Message Metadata for Widget Tracking
```sql
-- Add widget tracking to message metadata
INSERT INTO BMESSAGEMETA (BMESSID, BTOKEN, BVALUE)
VALUES (message_id, 'WIDGET_OWNER', owner_id);
```

## Configuration Requirements

### Widget Owner Configuration
- Widget owners must have valid user accounts
- Widget configuration stored in `BCONFIG` table
- Prompt configuration must be loadable to anonymous users
- File upload limits and restrictions configurable per widget

### System Configuration
- Anonymous session timeout settings
- File storage quotas for anonymous users
- Rate limiting configuration
- Data retention policies

## Monitoring and Maintenance

### Usage Monitoring
- Track anonymous widget usage statistics
- Monitor file upload patterns
- Track chat session durations
- Monitor for abuse patterns

### Maintenance Tasks
- Regular cleanup of expired anonymous sessions
- File storage cleanup for old anonymous uploads
- Database optimization for anonymous message storage
- Security audit and updates

## Success Criteria

1. **Functionality**: Anonymous users can successfully use the chat widget
2. **Security**: Anonymous users cannot access authenticated areas
3. **Performance**: Widget loads quickly and functions smoothly
4. **Compatibility**: Works across different browsers and devices
5. **Integration**: Easy to integrate on external websites
6. **Maintenance**: System is maintainable and scalable

## Risk Mitigation

1. **Security Risks**: Implement strict session isolation and access controls
2. **Performance Risks**: Add rate limiting and resource monitoring
3. **Data Privacy Risks**: Implement proper data retention and cleanup
4. **Abuse Risks**: Add monitoring and abuse prevention measures
5. **Integration Risks**: Provide clear documentation and examples

## Future Enhancements

1. **Analytics**: Add widget usage analytics for widget owners
2. **Customization**: Allow more widget customization options
3. **Integration**: Add more integration options (APIs, webhooks)
4. **Features**: Add more features for anonymous users (file sharing, etc.)
5. **Scalability**: Optimize for high-volume anonymous usage 
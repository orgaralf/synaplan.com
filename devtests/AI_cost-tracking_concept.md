# AI Cost Tracking Concept & Implementation Guide

## Overview

This document outlines a comprehensive approach to track AI model usage and calculate user spending in the Synaplan.com system. The current implementation has basic tracking infrastructure but lacks proper token counting, real-time cost calculation, and comprehensive usage monitoring.

## Current State Analysis

### What's Already Implemented
- Basic tracking infrastructure in `XSControl::countBytes()` and `XSControl::storeAIDetails()`
- Model pricing data in `BMODELS` table with various pricing models
- Message metadata storage in `BMESSAGEMETA` table
- Session tracking with `BTRACKID` for conversation grouping

### Current Gaps
- **Token counting**: Only tracking bytes, not actual tokens
- **Real-time cost calculation**: No pricing calculations during AI calls
- **Comprehensive usage tracking**: Many AI calls bypass the tracking
- **Session-based billing aggregation**: No cost aggregation per session

## Recommended Architecture

### 1. Tracking Strategy: Hybrid Approach

**Real-time tracking** for immediate user feedback and rate limiting
**Cronjob aggregation** for billing reports and analytics

**Benefits:**
- Real-time prevents users from exceeding limits
- Cronjob reduces database load and provides accurate billing
- Allows for cost optimization and fraud detection

### 2. Optimal Monitoring Points

#### Primary Monitoring Points
1. **`ProcessMethods::processMessage()`** - Main AI processing entry point
2. **`BasicAI::toolPrompt()`** - Tool execution (image generation, etc.)
3. **Each AI service class** (`AIOpenAI`, `AIGoogle`, etc.) - Direct API calls
4. **`ProcessMethods::saveAnswerToDB()`** - Final cost aggregation

#### Secondary Monitoring Points
- `ProcessMethods::sortMessage()` - For sorting operations
- `ProcessMethods::fileTranslation()` - For translation costs

## Database Schema Enhancements

### New Tables Required

#### BAIUSAGE Table - Individual AI Call Tracking
```sql
CREATE TABLE BAIUSAGE (
    BID bigint(20) NOT NULL AUTO_INCREMENT,
    BMESSID bigint(20) NOT NULL,
    BUSERID bigint(20) NOT NULL,
    BTRACKID bigint(20) NOT NULL,
    BMODELID bigint(20) NOT NULL,
    BAISERVICE varchar(32) NOT NULL,
    BAIMODEL varchar(96) NOT NULL,
    BTOKENSIN int(11) DEFAULT 0,
    BTOKENSOUT int(11) DEFAULT 0,
    BCOSTIN decimal(10,6) DEFAULT 0.000000,
    BCOSTOUT decimal(10,6) DEFAULT 0.000000,
    BUNITTYPE varchar(24) DEFAULT 'per1M',
    BUNITSIN int(11) DEFAULT 0,
    BUNITSOUT int(11) DEFAULT 0,
    BCALLTYPE varchar(32) DEFAULT 'chat',
    BUNIXTIMES int(11) NOT NULL,
    PRIMARY KEY (BID),
    KEY BMESSID (BMESSID),
    KEY BUSERID (BUSERID),
    KEY BTRACKID (BTRACKID)
);
```

#### BSESSIONCOSTS Table - Session Cost Aggregation
```sql
CREATE TABLE BSESSIONCOSTS (
    BID bigint(20) NOT NULL AUTO_INCREMENT,
    BUSERID bigint(20) NOT NULL,
    BTRACKID bigint(20) NOT NULL,
    BTOTALCOST decimal(10,6) DEFAULT 0.000000,
    BTOTALTOKENSIN int(11) DEFAULT 0,
    BTOTALTOKENSOUT int(11) DEFAULT 0,
    BSTARTTIME int(11) NOT NULL,
    BENDTIME int(11) DEFAULT NULL,
    BSTATUS varchar(16) DEFAULT 'active',
    PRIMARY KEY (BID),
    KEY BUSERID (BUSERID),
    KEY BTRACKID (BTRACKID)
);
```

## Enhanced XSControl Methods

### Core Tracking Method
```php
/**
 * Track AI usage with cost calculation
 */
public static function trackAIUsage($msgArr, $modelId, $tokensIn, $tokensOut, $callType = 'chat', $unitsIn = 0, $unitsOut = 0): array {
    // Get model pricing from BMODELS table
    $modelDetails = BasicAI::getModelDetails($modelId);
    
    // Calculate costs based on pricing model
    $costIn = self::calculateCost($modelDetails, $tokensIn, $unitsIn, 'input');
    $costOut = self::calculateCost($modelDetails, $tokensOut, $unitsOut, 'output');
    
    // Store usage record in BAIUSAGE table
    $usageSQL = "INSERT INTO BAIUSAGE (BMESSID, BUSERID, BTRACKID, BMODELID, BAISERVICE, BAIMODEL, 
                 BTOKENSIN, BTOKENSOUT, BCOSTIN, BCOSTOUT, BUNITTYPE, BUNITSIN, BUNITSOUT, BCALLTYPE, BUNIXTIMES) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Update session costs
    self::updateSessionCosts($msgArr['BUSERID'], $msgArr['BTRACKID'], $costIn + $costOut);
    
    return [
        'cost_in' => $costIn,
        'cost_out' => $costOut,
        'total_cost' => $costIn + $costOut,
        'tokens_in' => $tokensIn,
        'tokens_out' => $tokensOut
    ];
}
```

### Cost Calculation Method
```php
/**
 * Calculate cost based on pricing model from BMODELS table
 */
private static function calculateCost($modelDetails, $tokens, $units, $type): float {
    $cost = 0;
    
    switch($modelDetails['BINUNIT']) {
        case 'per1M':
            $cost = ($tokens / 1000000) * ($type == 'input' ? $modelDetails['BPRICEIN'] : $modelDetails['BPRICEOUT']);
            break;
        case 'perpic':
            $cost = $units * ($type == 'input' ? $modelDetails['BPRICEIN'] : $modelDetails['BPRICEOUT']);
            break;
        case 'persec':
            $cost = $units * ($type == 'input' ? $modelDetails['BPRICEIN'] : $modelDetails['BPRICEOUT']);
            break;
        case 'perhour':
            $cost = ($units / 3600) * ($type == 'input' ? $modelDetails['BPRICEIN'] : $modelDetails['BPRICEOUT']);
            break;
    }
    
    return round($cost, 6);
}
```

## Integration Points

### 1. ProcessMethods::processMessage()
```php
// After AI call, before saving to DB
$usageData = XSControl::trackAIUsage(
    self::$msgArr, 
    $modelId, 
    $response['usage']['input_tokens'] ?? 0,
    $response['usage']['output_tokens'] ?? 0,
    'chat'
);
```

### 2. AI Service Classes (AIOpenAI, AIGoogle, etc.)
```php
// In AIOpenAI::topicPrompt(), AIGoogle::topicPrompt(), etc.
$response = $client->responses()->create([...]);

// Track usage immediately after API call
$usageData = XSControl::trackAIUsage(
    $msgArr,
    $modelId,
    $response->usage->inputTokens,
    $response->usage->outputTokens,
    'chat'
);

// Add usage data to response for ProcessMethods
$response['_USAGE_DATA'] = $usageData;
```

### 3. Tool Processing (Image Generation, etc.)
```php
// In BasicAI::toolPrompt() for image/video/audio generation
$usageData = XSControl::trackAIUsage(
    $msgArr,
    $modelId,
    0, // No input tokens for media generation
    0, // No output tokens for media generation
    'media',
    1, // 1 image/video/audio unit
    0
);
```

## Pricing Models Supported

Based on the BMODELS table structure, the system supports:

1. **per1M** - Per million tokens (chat models)
2. **perpic** - Per image (DALL-E, Imagen)
3. **persec** - Per second (video generation)
4. **perhour** - Per hour (Whisper audio processing)

## Session Tracking Enhancement

### Session Cost Tracking
```php
// In ProcessMethods::init()
if (!isset(self::$msgArr['BTRACKID'])) {
    self::$msgArr['BTRACKID'] = (int) (microtime(true) * 1000000);
}

// Start session cost tracking
XSControl::startSessionTracking(self::$msgArr['BUSERID'], self::$msgArr['BTRACKID']);
```

### Real-time Cost Monitoring
```php
// In ProcessMethods::processMessage()
$sessionCost = XSControl::getSessionCost(self::$msgArr['BUSERID'], self::$msgArr['BTRACKID']);
$userLimit = self::$usrArr['DETAILS']['MONTHLY_LIMIT'] ?? 10.00; // $10 default

if ($sessionCost > $userLimit) {
    if(self::$stream) {
        Frontend::statusToStream(self::$msgId, 'error', 'Monthly usage limit exceeded.');
    }
    return;
}
```

## Billing Aggregation Cronjob

### Daily Cost Aggregation
```php
// cronjob/billing_aggregation.php
class BillingAggregation {
    public static function aggregateDailyCosts(): void {
        // Aggregate costs by user and session
        // Generate billing reports
        // Update user account balances
        // Send notifications for high usage
    }
    
    public static function checkUserLimits(): void {
        // Check if users exceed their limits
        // Implement rate limiting
        // Send warnings
    }
}
```

## Implementation Priority

### Phase 1: Foundation (Week 1-2)
- [ ] Create BAIUSAGE and BSESSIONCOSTS tables
- [ ] Implement `trackAIUsage()` method in XSControl
- [ ] Add cost calculation logic

### Phase 2: Core Integration (Week 3-4)
- [ ] Integrate tracking into AI service classes
- [ ] Add usage tracking to ProcessMethods::processMessage()
- [ ] Implement session cost tracking

### Phase 3: Advanced Features (Week 5-6)
- [ ] Add real-time cost monitoring and limits
- [ ] Implement billing aggregation cronjob
- [ ] Create user dashboard with cost analytics

### Phase 4: Optimization (Week 7-8)
- [ ] Performance optimization
- [ ] Fraud detection
- [ ] Advanced reporting features

## Benefits

1. **Accurate Cost Tracking**: Real token counting instead of byte estimation
2. **Real-time Monitoring**: Immediate feedback on usage and costs
3. **Flexible Pricing**: Support for all pricing models in BMODELS table
4. **Session-based Billing**: Track costs per conversation session
5. **Scalable Architecture**: Hybrid approach balances performance and accuracy
6. **Comprehensive Coverage**: Tracks all AI operations across the system

## Risk Mitigation

1. **Database Performance**: Use indexes and batch operations for large datasets
2. **API Rate Limits**: Implement exponential backoff for failed tracking
3. **Data Consistency**: Use database transactions for critical operations
4. **Cost Overruns**: Implement real-time limits and alerts
5. **Fraud Prevention**: Monitor unusual usage patterns and implement safeguards

## Monitoring and Analytics

### Key Metrics to Track
- Total cost per user per month
- Cost per session/conversation
- Most expensive AI operations
- Usage patterns and trends
- Model performance vs cost

### Dashboard Features
- Real-time cost display
- Monthly usage summaries
- Cost breakdown by AI service
- Usage alerts and notifications
- Export capabilities for billing

This comprehensive approach ensures accurate cost tracking while maintaining system performance and providing valuable insights for both users and administrators. 
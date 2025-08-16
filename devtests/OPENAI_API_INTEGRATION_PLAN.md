# OpenAI API Integration Plan for Synaplan.com

## Overview
This document outlines the plan to add OpenAI-compatible API functionality to the existing synaplan.com API system. The goal is to provide a unified interface that can handle both the current custom API endpoints and new OpenAI-compatible endpoints, while maintaining backward compatibility.

### Specs library (single source of truth)
Machine-readable specs and templates in `devtests/` to be consumed by the implementation (no direct hard-coding):
- `OPENAI_API_FORMAT_CHAT_COMPLETIONS.json`
- `OPENAI_API_ERRORS.json`
- `OPENAI_API_STREAMING_SSE.md`
- `OPENAI_API_ROUTING_MAP.json`
- `OPENAI_API_PROVIDER_INTERFACE.php.stub`
- `OPENAI_API_RESPONSE_TEMPLATES.json`
- `OPENAI_API_MODEL_MAPPING.json`
- `OPENAI_API_CORS_SECURITY.md`
- `OPENAI_API_TEST_MATRIX.md`
- `OPENAI_API_IMPLEMENTATION_CHECKLIST.md`

## Current Architecture Analysis

### Existing AI Classes
- **`_aiopenai.php`** - OpenAI API integration (most complete)
- **`_aianthropic.php`** - Anthropic Claude API integration
- **`_aigroq.php`** - Groq API integration
- **`_aigoogle.php`** - Google Gemini API integration
- **`_aiollama.php`** - Local Ollama integration
- **`_aithehive.php`** - The Hive AI integration

### Current API Structure
- Bearer token authentication already implemented
- Action-based routing via `$_REQUEST['action']`
- Session-based user management
- Rate limiting for anonymous users
- JSON-RPC support for MCP compatibility

### Existing AI Capabilities
- **Chat/Text Generation**: All AI classes support this
- **Image Generation**: OpenAI, The Hive, and some others
- **Image Analysis**: OpenAI, Ollama, Google Gemini
- **Audio Transcription**: OpenAI (Whisper), Groq
- **Text-to-Speech**: OpenAI, Anthropic, Google Gemini
- **Streaming**: OpenAI, Ollama, Groq support streaming
- **Video Generation**: Google Gemini only

## Integration Strategy

### 1. Model-Based Routing System
Implement a model-based routing system where the `model` parameter in API requests determines which AI service to use:

```
Model Format: "provider/model-name"
Examples:
- "openai/gpt-4" → OpenAI GPT-4
- "anthropic/claude-3" → Anthropic Claude
- "groq/llama3-70b" → Groq Llama
- "ollama/llama-70b" → Local Ollama
- "google/gemini-pro" → Google Gemini
- "thehive/stable-diffusion" → The Hive
```
These are not the only ones, look at the current list of models in db-loadfiles/BMODELS.sql.

### 2. New API Endpoints Structure

#### A. Chat Completions (`/v1/chat/completions`)
- **Method**: POST
- **Purpose**: Text generation and conversation
- **Features**: 
  - Support for all AI providers
  - Streaming and non-streaming modes (see `OPENAI_API_STREAMING_SSE.md`)
  - Message history context
  - Model selection via `model` parameter (see `OPENAI_API_MODEL_MAPPING.json`)

#### B. Audio Transcription (`/v1/audio/transcriptions`)
- **Method**: POST
- **Purpose**: Convert audio to text
- **Features**:
  - Support for OpenAI Whisper
  - Support for Groq audio transcription
  - File upload handling (see `OPENAI_API_CORS_SECURITY.md`)

#### C-1. Image Generation (`/v1/images/generations`)
- **Method**: POST
- **Purpose**: Generate images from text prompts
- **Features**:
  - Support for OpenAI DALL-E
  - Support for The Hive Stable Diffusion
  - Configurable image parameters

#### C-2. Video Generation (`/v1/images/generations`)
- **Method**: POST
- **Purpose**: Generate short videos from text prompts
- **Features**:
  - Support for Google Veo Model
  - Support for later enhancements with other models

#### D. Image Analysis (`/v1/chat/completions` with image input)
- **Method**: POST
- **Purpose**: Analyze image content
- **Features**:
  - Support for OpenAI Vision models
  - Support for Ollama vision models
  - Support for Google Gemini vision

#### E. Text-to-Speech (`/v1/audio/speech`)
- **Method**: POST
- **Purpose**: Convert text to speech
- **Features**:
  - Support for OpenAI TTS
  - Support for Anthropic TTS
  - Support for Google TTS

### 3. Implementation Phases

#### Phase 1: Core Infrastructure
1. Load routing map from `OPENAI_API_ROUTING_MAP.json` in `api.php`
2. Model parsing and alias resolution from `OPENAI_API_MODEL_MAPPING.json`
3. Provider factory using `OPENAI_API_PROVIDER_INTERFACE.php.stub`
4. Error normalization using `OPENAI_API_ERRORS.json`
5. CORS/preflight and payload limits per `OPENAI_API_CORS_SECURITY.md`

#### Phase 2: Chat Completions
1. Implement `/v1/chat/completions` using `OPENAI_API_FORMAT_CHAT_COMPLETIONS.json`
2. SSE streaming per `OPENAI_API_STREAMING_SSE.md` (normalize provider deltas to OpenAI chunks)
3. Message/response format conversion using `OPENAI_API_RESPONSE_TEMPLATES.json`
4. Conversation context management (existing logic)

#### Phase 3: Audio Processing
1. Implement `/v1/audio/transcriptions` (spec to be added as `OPENAI_API_FORMAT_AUDIO_TRANSCRIPTIONS.json`)
2. Implement `/v1/audio/speech` (spec to be added as `OPENAI_API_FORMAT_AUDIO_SPEECH.json`)
3. File upload handling per `OPENAI_API_CORS_SECURITY.md`
4. Support multiple audio formats (enumerated in respective format spec)

#### Phase 4: Image and Video Processing
1. Implement `/v1/images/generations` (spec to be added as `OPENAI_API_FORMAT_IMAGES_GENERATIONS.json`)
   sub-task: add the video implementation - `/v1/images/videos`
2. Add image analysis to chat via multimodal content parts (see chat format spec)
3. Handle image uploads per `OPENAI_API_CORS_SECURITY.md`
4. Support multiple image formats

#### Phase 5: Testing and Optimization
1. Execute `OPENAI_API_TEST_MATRIX.md`
2. Performance optimization for streaming; tune chunk size per SSE guide
3. Verify error handling parity per `OPENAI_API_ERRORS.json`
4. Documentation and examples (reference `OPENAI_API_FORMAT_*` schemas)

### 4. Technical Implementation Details

#### A. New API Structure
```php
// New routing logic in api.php
if (strpos($_SERVER['REQUEST_URI'], '/v1/') === 0) {
    // Handle OpenAI-compatible endpoints
    handleOpenAICompatibleRequest();
} else {
    // Handle existing action-based endpoints
    handleLegacyRequest();
}
```

#### B. AI Provider Factory
```php
class AIProviderFactory {
    public static function createProvider($modelString) {
        $parts = explode('/', $modelString);
        $provider = $parts[0];
        
        switch ($provider) {
            case 'openai':
                return new AIOpenAI();
            case 'anthropic':
                return new AIAnthropic();
            case 'groq':
                return new AIGroq();
            case 'ollama':
                return new AIOllama();
            case 'google':
                return new AIGoogle();
            case 'thehive':
                return new AITheHive();
            default:
                throw new Exception("Unknown AI provider: $provider");
        }
    }
}
```

#### C. Endpoint Handler Structure
```php
function handleOpenAICompatibleRequest() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ("$method $path") {
        case 'POST /v1/chat/completions':
            handleChatCompletions();
            break;
        case 'POST /v1/audio/transcriptions':
            handleAudioTranscriptions();
            break;
        case 'POST /v1/images/generations':
            handleImageGeneration();
            break;
        case 'POST /v1/audio/speech':
            handleTextToSpeech();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
    }
}
```

### 5. Data Format Compatibility

#### A. OpenAI Request Format → Internal Format
```php
// OpenAI format
{
    "model": "ollama/llama-70b",
    "messages": [
        {"role": "user", "content": "Hello"}
    ],
    "stream": false
}

// Convert to internal format
$msgArr = [
    'BTEXT' => $message['content'],
    'BTOPIC' => 'general',
    'BLANG' => 'en',
    'BDIRECT' => 'IN'
];
```

#### B. Internal Format → OpenAI Response Format
```php
// Internal response
$response = AIOllama::topicPrompt($msgArr, $threadArr, $stream);

// Convert to OpenAI format (see OPENAI_API_RESPONSE_TEMPLATES.json)
$openAIResponse = [
    'id' => 'chatcmpl-' . uniqid(),
    'object' => 'chat.completion',
    'created' => time(),
    'model' => $model,
    'choices' => [
        [
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => $response['BTEXT']
            ],
            'finish_reason' => 'stop'
        ]
    ],
    'usage' => [
        'prompt_tokens' => $response['BTOKENS_PROMPT'] ?? 0,
        'completion_tokens' => $response['BTOKENS_COMPLETION'] ?? 0,
        'total_tokens' => ($response['BTOKENS_PROMPT'] ?? 0) + ($response['BTOKENS_COMPLETION'] ?? 0)
    ]
];
```

### 6. Backward Compatibility

#### A. Existing Endpoints
- All current `action`-based endpoints remain unchanged
- Existing authentication and rate limiting preserved
- No breaking changes to current functionality

#### B. Session Management
- Bearer token authentication works for both systems
- Session-based user management maintained
- Widget and anonymous user support preserved

### 7. Configuration and Environment

#### A. New Configuration Options
```php
// Add to _confdefaults.php or similar
$GLOBALS["OPENAI_COMPATIBLE_API"] = [
    "ENABLED" => true,
    "DEFAULT_MODEL" => "openai/gpt-4",
    "RATE_LIMIT" => [
        "REQUESTS_PER_MINUTE" => 60,
        "REQUESTS_PER_HOUR" => 1000
    ]
];
```

#### B. Model Mapping
```php
$GLOBALS["MODEL_MAPPING"] = [
    "gpt-4" => "openai/gpt-4",
    "gpt-3.5-turbo" => "openai/gpt-3.5-turbo",
    "claude-3" => "anthropic/claude-3",
    "llama-70b" => "ollama/llama-70b",
    "gemini-pro" => "google/gemini-pro"
];
```

### 8. Security Considerations

#### A. Rate Limiting
- Implement per-user rate limiting for new endpoints
- Maintain existing rate limiting for anonymous users
- Add provider-specific rate limiting

#### B. Input Validation
- Validate model strings to prevent injection
- Sanitize all user inputs
- Implement file upload security

#### C. Authentication
- Bearer token validation for all endpoints
- API key management for different providers
- User permission checking

### 9. Testing Strategy

#### A. Unit Tests
- Test AI provider factory
- Test model parsing
- Test format conversion functions

#### B. Integration Tests
- Test each endpoint with different providers
- Test streaming functionality
- Test error handling

#### C. Compatibility Tests
- Test with existing OpenAI clients
- Test with different model combinations
- Test backward compatibility

### 10. Documentation and Examples

#### A. API Documentation
- OpenAPI/Swagger specification
- Endpoint descriptions
- Request/response examples

#### B. Client Examples
- Python client examples
- JavaScript client examples
- cURL examples

#### C. Migration Guide
- How to migrate from existing endpoints
- Best practices for new API usage
- Troubleshooting guide

## Implementation Timeline

- **Week 1-2**: Phase 1 - Core Infrastructure
- **Week 3-4**: Phase 2 - Chat Completions
- **Week 5-6**: Phase 3 - Audio Processing
- **Week 7-8**: Phase 4 - Image Processing
- **Week 9-10**: Phase 5 - Testing and Optimization

## Risk Assessment

### Low Risk
- Backward compatibility maintained
- Existing functionality preserved
- Modular design allows gradual rollout

### Medium Risk
- New routing system complexity
- Format conversion edge cases
- Performance impact of new endpoints

### High Risk
- Streaming implementation complexity
- File upload security
- Rate limiting across multiple providers

## Success Criteria

1. **Functionality**: All OpenAI-compatible endpoints working
2. **Performance**: Response times within acceptable limits
3. **Compatibility**: Works with standard OpenAI clients
4. **Reliability**: 99.9% uptime for new endpoints
5. **Security**: No security vulnerabilities introduced

## Conclusion

This integration plan provides a comprehensive approach to adding OpenAI-compatible API functionality while maintaining the existing system's integrity. The phased implementation approach minimizes risk and allows for iterative improvements based on testing and user feedback.

The model-based routing system provides flexibility for future AI providers while maintaining a clean, intuitive API structure that developers familiar with OpenAI will find familiar and easy to use.

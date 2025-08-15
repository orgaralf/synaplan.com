### Implementation checklist

1) Load `OPENAI_API_ROUTING_MAP.json` and dispatch /v1/* in `api.php`
2) Load `OPENAI_API_MODEL_MAPPING.json` for alias→provider/model and whitelist
3) Enforce CORS/auth/limits per `OPENAI_API_CORS_SECURITY.md`
4) Implement `/v1/chat/completions`:
   - Validate request against `OPENAI_API_FORMAT_CHAT_COMPLETIONS.json.schemas.request`
   - If stream=false: call provider, then format via `OPENAI_API_RESPONSE_TEMPLATES.json.chat_completion`
   - If stream=true: emit SSE per `OPENAI_API_STREAMING_SSE.md` using `...chat_chunk`
   - Map errors via `OPENAI_API_ERRORS.json`
5) Provider factory returns an object implementing `OPENAI_API_PROVIDER_INTERFACE.php.stub`
6) Add tests from `OPENAI_API_TEST_MATRIX.md`; verify with OpenAI SDK baseURL override
7) Document examples referencing these schemas

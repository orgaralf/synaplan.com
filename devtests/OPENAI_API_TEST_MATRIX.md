### Test matrix

- Chat completions (non-streaming):
  - Minimal request (user message only) → 200, content present, usage present
  - With temperature/top_p/stop → respected or gracefully ignored
  - With tools/tool_choice → echoed structure if unsupported
- Chat completions (streaming):
  - SSE headers present; first chunk sets role; ends with [DONE]
  - Chunk order reconstructs full content
- Models:
  - Each alias in `OPENAI_API_MODEL_MAPPING.json.aliases` resolves and returns `model` echo
- Errors:
  - Missing auth → 401 body per `OPENAI_API_ERRORS.json`
  - Bad model → 400 invalid_request_error
  - Rate limit → 429 with Retry-After
- Audio/image (when implemented): basic happy-path per formats

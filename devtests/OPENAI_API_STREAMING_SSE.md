### SSE contract for /v1/chat/completions (stream=true)

- Required headers:
  - Content-Type: text/event-stream
  - Cache-Control: no-cache
  - Connection: keep-alive
  - X-Accel-Buffering: no (nginx) or disable proxy buffering equivalently
- Framing:
  - Each event line prefixed with `data: ` followed by a single JSON chunk, then a blank line
  - Stream termination with `data: [DONE]` then blank line
- Chunk schema: see `OPENAI_API_FORMAT_CHAT_COMPLETIONS.json.schemas.streaming_chunk`

Example start:
```
data: {"id":"chatcmpl_abc","object":"chat.completion.chunk","created": 1710000000,"model":"openai/gpt-4o-mini","choices":[{"index":0,"delta":{"role":"assistant","content":"He"},"finish_reason":null}]}

```
...
```
data: {"id":"chatcmpl_abc","object":"chat.completion.chunk","created": 1710000001,"model":"openai/gpt-4o-mini","choices":[{"index":0,"delta":{"content":"llo"},"finish_reason":null}]}

```
End sentinel:
```
data: [DONE]

```

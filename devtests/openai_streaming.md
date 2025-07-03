## **Streaming**

When you [create a Response](https://platform.openai.com/docs/api-reference/responses/create) with `stream` set to `true`, the server will emit server-sent events to the client as the Response is generated. This section contains the events that are emitted by the server.

[Learn more about streaming responses](https://platform.openai.com/docs/guides/streaming-responses?api-mode=responses).

## 

## **response.created**

An event that is emitted when a response is created.

**response**

object

The response that was created.

Show properties

**sequence_number**

integer

The sequence number for this event.

**type**

string

The type of the event. Always `response.created`.

OBJECT response.created

```JSON
{
  "type": "response.created",
  "response": {
    "id": "resp_67ccfcdd16748190a91872c75d38539e09e4d4aac714747c",
    "object": "response",
    "created_at": 1741487325,
    "status": "in_progress",
    "error": null,
    "incomplete_details": null,
    "instructions": null,
    "max_output_tokens": null,
    "model": "gpt-4o-2024-08-06",
    "output": [],
    "parallel_tool_calls": true,
    "previous_response_id": null,
    "reasoning": {
      "effort": null,
      "summary": null
    },
    "store": true,
    "temperature": 1,
    "text": {
      "format": {
        "type": "text"
      }
    },
    "tool_choice": "auto",
    "tools": [],
    "top_p": 1,
    "truncation": "disabled",
    "usage": null,
    "user": null,
    "metadata": {}
  },
  "sequence_number": 1
}
```

## **response.in_progress**

Emitted when the response is in progress.

**response**

object

The response that is in progress.

Show properties

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.in_progress`.

OBJECT response.in_progress

```JSON
{
  "type": "response.in_progress",
  "response": {
    "id": "resp_67ccfcdd16748190a91872c75d38539e09e4d4aac714747c",
    "object": "response",
    "created_at": 1741487325,
    "status": "in_progress",
    "error": null,
    "incomplete_details": null,
    "instructions": null,
    "max_output_tokens": null,
    "model": "gpt-4o-2024-08-06",
    "output": [],
    "parallel_tool_calls": true,
    "previous_response_id": null,
    "reasoning": {
      "effort": null,
      "summary": null
    },
    "store": true,
    "temperature": 1,
    "text": {
      "format": {
        "type": "text"
      }
    },
    "tool_choice": "auto",
    "tools": [],
    "top_p": 1,
    "truncation": "disabled",
    "usage": null,
    "user": null,
    "metadata": {}
  },
  "sequence_number": 1
}
```

## **response.completed**

Emitted when the model response is complete.

**response**

object

Properties of the completed response.

Show properties

**sequence_number**

integer

The sequence number for this event.

**type**

string

The type of the event. Always `response.completed`.

OBJECT response.completed

```JSON
{
  "type": "response.completed",
  "response": {
    "id": "resp_123",
    "object": "response",
    "created_at": 1740855869,
    "status": "completed",
    "error": null,
    "incomplete_details": null,
    "input": [],
    "instructions": null,
    "max_output_tokens": null,
    "model": "gpt-4o-mini-2024-07-18",
    "output": [
      {
        "id": "msg_123",
        "type": "message",
        "role": "assistant",
        "content": [
          {
            "type": "output_text",
            "text": "In a shimmering forest under a sky full of stars, a lonely unicorn named Lila discovered a hidden pond that glowed with moonlight. Every night, she would leave sparkling, magical flowers by the water's edge, hoping to share her beauty with others. One enchanting evening, she woke to find a group of friendly animals gathered around, eager to be friends and share in her magic.",
            "annotations": []
          }
        ]
      }
    ],
    "previous_response_id": null,
    "reasoning_effort": null,
    "store": false,
    "temperature": 1,
    "text": {
      "format": {
        "type": "text"
      }
    },
    "tool_choice": "auto",
    "tools": [],
    "top_p": 1,
    "truncation": "disabled",
    "usage": {
      "input_tokens": 0,
      "output_tokens": 0,
      "output_tokens_details": {
        "reasoning_tokens": 0
      },
      "total_tokens": 0
    },
    "user": null,
    "metadata": {}
  },
  "sequence_number": 1
}
```

## **response.failed**

An event that is emitted when a response fails.

**response**

object

The response that failed.

Show properties

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.failed`.

OBJECT response.failed

```JSON
{
  "type": "response.failed",
  "response": {
    "id": "resp_123",
    "object": "response",
    "created_at": 1740855869,
    "status": "failed",
    "error": {
      "code": "server_error",
      "message": "The model failed to generate a response."
    },
    "incomplete_details": null,
    "instructions": null,
    "max_output_tokens": null,
    "model": "gpt-4o-mini-2024-07-18",
    "output": [],
    "previous_response_id": null,
    "reasoning_effort": null,
    "store": false,
    "temperature": 1,
    "text": {
      "format": {
        "type": "text"
      }
    },
    "tool_choice": "auto",
    "tools": [],
    "top_p": 1,
    "truncation": "disabled",
    "usage": null,
    "user": null,
    "metadata": {}
  }
}
```

## **response.incomplete**

An event that is emitted when a response finishes as incomplete.

**response**

object

The response that was incomplete.

Show properties

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.incomplete`.

OBJECT response.incomplete

```JSON
{
  "type": "response.incomplete",
  "response": {
    "id": "resp_123",
    "object": "response",
    "created_at": 1740855869,
    "status": "incomplete",
    "error": null, 
    "incomplete_details": {
      "reason": "max_tokens"
    },
    "instructions": null,
    "max_output_tokens": null,
    "model": "gpt-4o-mini-2024-07-18",
    "output": [],
    "previous_response_id": null,
    "reasoning_effort": null,
    "store": false,
    "temperature": 1,
    "text": {
      "format": {
        "type": "text"
      }
    },
    "tool_choice": "auto",
    "tools": [],
    "top_p": 1,
    "truncation": "disabled",
    "usage": null,
    "user": null,
    "metadata": {}
  },
  "sequence_number": 1
}
```

## 

## **response.output_item.added**

Emitted when a new output item is added.

**item**

object

The output item that was added.

Show possible types

**output_index**

integer

The index of the output item that was added.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.output_item.added`.

OBJECT response.output_item.added

```JSON
{
  "type": "response.output_item.added",
  "output_index": 0,
  "item": {
    "id": "msg_123",
    "status": "in_progress",
    "type": "message",
    "role": "assistant",
    "content": []
  },
  "sequence_number": 1
}
```

## **response.output_item.done**

Emitted when an output item is marked done.

**item**

object

The output item that was marked done.

Show possible types

**output_index**

integer

The index of the output item that was marked done.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.output_item.done`.

OBJECT response.output_item.done

```JSON
{
  "type": "response.output_item.done",
  "output_index": 0,
  "item": {
    "id": "msg_123",
    "status": "completed",
    "type": "message",
    "role": "assistant",
    "content": [
      {
        "type": "output_text",
        "text": "In a shimmering forest under a sky full of stars, a lonely unicorn named Lila discovered a hidden pond that glowed with moonlight. Every night, she would leave sparkling, magical flowers by the water's edge, hoping to share her beauty with others. One enchanting evening, she woke to find a group of friendly animals gathered around, eager to be friends and share in her magic.",
        "annotations": []
      }
    ]
  },
  "sequence_number": 1
}
```

## 

## **response.content_part.added**

Emitted when a new content part is added.

**content_index**

integer

The index of the content part that was added.

**item_id**

string

The ID of the output item that the content part was added to.

**output_index**

integer

The index of the output item that the content part was added to.

**part**

object

The content part that was added.

Show possible types

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.content_part.added`.

OBJECT response.content_part.added

```JSON
{
  "type": "response.content_part.added",
  "item_id": "msg_123",
  "output_index": 0,
  "content_index": 0,
  "part": {
    "type": "output_text",
    "text": "",
    "annotations": []
  },
  "sequence_number": 1
}
```

## **response.content_part.done**

Emitted when a content part is done.

**content_index**

integer

The index of the content part that is done.

**item_id**

string

The ID of the output item that the content part was added to.

**output_index**

integer

The index of the output item that the content part was added to.

**part**

object

The content part that is done.

Show possible types

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.content_part.done`.

OBJECT response.content_part.done

```JSON
{
  "type": "response.content_part.done",
  "item_id": "msg_123",
  "output_index": 0,
  "content_index": 0,
  "sequence_number": 1,
  "part": {
    "type": "output_text",
    "text": "In a shimmering forest under a sky full of stars, a lonely unicorn named Lila discovered a hidden pond that glowed with moonlight. Every night, she would leave sparkling, magical flowers by the water's edge, hoping to share her beauty with others. One enchanting evening, she woke to find a group of friendly animals gathered around, eager to be friends and share in her magic.",
    "annotations": []
  }
}
```

## 

## **response.output_text.delta**

Emitted when there is an additional text delta.

**content_index**

integer

The index of the content part that the text delta was added to.

**delta**

string

The text delta that was added.

**item_id**

string

The ID of the output item that the text delta was added to.

**output_index**

integer

The index of the output item that the text delta was added to.

**sequence_number**

integer

The sequence number for this event.

**type**

string

The type of the event. Always `response.output_text.delta`.

OBJECT response.output_text.delta

```JSON
{
  "type": "response.output_text.delta",
  "item_id": "msg_123",
  "output_index": 0,
  "content_index": 0,
  "delta": "In",
  "sequence_number": 1
}
```

## **response.output_text.done**

Emitted when text content is finalized.

**content_index**

integer

The index of the content part that the text content is finalized.

**item_id**

string

The ID of the output item that the text content is finalized.

**output_index**

integer

The index of the output item that the text content is finalized.

**sequence_number**

integer

The sequence number for this event.

**text**

string

The text content that is finalized.

**type**

string

The type of the event. Always `response.output_text.done`.

OBJECT response.output_text.done

```JSON
{
  "type": "response.output_text.done",
  "item_id": "msg_123",
  "output_index": 0,
  "content_index": 0,
  "text": "In a shimmering forest under a sky full of stars, a lonely unicorn named Lila discovered a hidden pond that glowed with moonlight. Every night, she would leave sparkling, magical flowers by the water's edge, hoping to share her beauty with others. One enchanting evening, she woke to find a group of friendly animals gathered around, eager to be friends and share in her magic.",
  "sequence_number": 1
}
```

## 

## **response.refusal.delta**

Emitted when there is a partial refusal text.

**content_index**

integer

The index of the content part that the refusal text is added to.

**delta**

string

The refusal text that is added.

**item_id**

string

The ID of the output item that the refusal text is added to.

**output_index**

integer

The index of the output item that the refusal text is added to.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.refusal.delta`.

OBJECT response.refusal.delta

```JSON
{
  "type": "response.refusal.delta",
  "item_id": "msg_123",
  "output_index": 0,
  "content_index": 0,
  "delta": "refusal text so far",
  "sequence_number": 1
}
```

## **response.refusal.done**

Emitted when refusal text is finalized.

**content_index**

integer

The index of the content part that the refusal text is finalized.

**item_id**

string

The ID of the output item that the refusal text is finalized.

**output_index**

integer

The index of the output item that the refusal text is finalized.

**refusal**

string

The refusal text that is finalized.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.refusal.done`.

OBJECT response.refusal.done

```JSON
{
  "type": "response.refusal.done",
  "item_id": "item-abc",
  "output_index": 1,
  "content_index": 2,
  "refusal": "final refusal text",
  "sequence_number": 1
}
```

## 

## **response.function_call_arguments.delta**

Emitted when there is a partial function-call arguments delta.

**delta**

string

The function-call arguments delta that is added.

**item_id**

string

The ID of the output item that the function-call arguments delta is added to.

**output_index**

integer

The index of the output item that the function-call arguments delta is added to.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.function_call_arguments.delta`.

OBJECT response.function_call_arguments.delta

```JSON
{
  "type": "response.function_call_arguments.delta",
  "item_id": "item-abc",
  "output_index": 0,
  "delta": "{ \"arg\":"
  "sequence_number": 1
}
```

## **response.function_call_arguments.done**

Emitted when function-call arguments are finalized.

**arguments**

string

The function-call arguments.

**item_id**

string

The ID of the item.

**output_index**

integer

The index of the output item.

**sequence_number**

integer

The sequence number of this event.

**type**

string

OBJECT response.function_call_arguments.done

```JSON
{
  "type": "response.function_call_arguments.done",
  "item_id": "item-abc",
  "output_index": 1,
  "arguments": "{ \"arg\": 123 }",
  "sequence_number": 1
}
```

## 

## **response.file_search_call.in_progress**

Emitted when a file search call is initiated.

**item_id**

string

The ID of the output item that the file search call is initiated.

**output_index**

integer

The index of the output item that the file search call is initiated.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.file_search_call.in_progress`.

OBJECT response.file_search_call.in_progress

```JSON
{
  "type": "response.file_search_call.in_progress",
  "output_index": 0,
  "item_id": "fs_123",
  "sequence_number": 1
}
```

## **response.file_search_call.searching**

Emitted when a file search is currently searching.

**item_id**

string

The ID of the output item that the file search call is initiated.

**output_index**

integer

The index of the output item that the file search call is searching.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.file_search_call.searching`.

OBJECT response.file_search_call.searching

```JSON
{
  "type": "response.file_search_call.searching",
  "output_index": 0,
  "item_id": "fs_123",
  "sequence_number": 1
}
```

## **response.file_search_call.completed**

Emitted when a file search call is completed (results found).

**item_id**

string

The ID of the output item that the file search call is initiated.

**output_index**

integer

The index of the output item that the file search call is initiated.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `response.file_search_call.completed`.

OBJECT response.file_search_call.completed

```JSON
{
  "type": "response.file_search_call.completed",
  "output_index": 0,
  "item_id": "fs_123",
  "sequence_number": 1
}
```

## 

## **response.web_search_call.in_progress**

Emitted when a web search call is initiated.

**item_id**

string

Unique ID for the output item associated with the web search call.

**output_index**

integer

The index of the output item that the web search call is associated with.

**sequence_number**

integer

The sequence number of the web search call being processed.

**type**

string

The type of the event. Always `response.web_search_call.in_progress`.

OBJECT response.web_search_call.in_progress

```JSON
{
  "type": "response.web_search_call.in_progress",
  "output_index": 0,
  "item_id": "ws_123",
  "sequence_number": 0
}
```

## **response.web_search_call.searching**

Emitted when a web search call is executing.

**item_id**

string

Unique ID for the output item associated with the web search call.

**output_index**

integer

The index of the output item that the web search call is associated with.

**sequence_number**

integer

The sequence number of the web search call being processed.

**type**

string

The type of the event. Always `response.web_search_call.searching`.

OBJECT response.web_search_call.searching

```JSON
{
  "type": "response.web_search_call.searching",
  "output_index": 0,
  "item_id": "ws_123",
  "sequence_number": 0
}
```

## **response.web_search_call.completed**

Emitted when a web search call is completed.

**item_id**

string

Unique ID for the output item associated with the web search call.

**output_index**

integer

The index of the output item that the web search call is associated with.

**sequence_number**

integer

The sequence number of the web search call being processed.

**type**

string

The type of the event. Always `response.web_search_call.completed`.

OBJECT response.web_search_call.completed

```JSON
{
  "type": "response.web_search_call.completed",
  "output_index": 0,
  "item_id": "ws_123",
  "sequence_number": 0
}
```

## 

## **response.reasoning_summary_part.added**

Emitted when a new reasoning summary part is added.

**item_id**

string

The ID of the item this summary part is associated with.

**output_index**

integer

The index of the output item this summary part is associated with.

**part**

object

The summary part that was added.

Show properties

**sequence_number**

integer

The sequence number of this event.

**summary_index**

integer

The index of the summary part within the reasoning summary.

**type**

string

The type of the event. Always `response.reasoning_summary_part.added`.

OBJECT response.reasoning_summary_part.added

```JSON
{
  "type": "response.reasoning_summary_part.added",
  "item_id": "rs_6806bfca0b2481918a5748308061a2600d3ce51bdffd5476",
  "output_index": 0,
  "summary_index": 0,
  "part": {
    "type": "summary_text",
    "text": ""
  },
  "sequence_number": 1
}
```

## **response.reasoning_summary_part.done**

Emitted when a reasoning summary part is completed.

**item_id**

string

The ID of the item this summary part is associated with.

**output_index**

integer

The index of the output item this summary part is associated with.

**part**

object

The completed summary part.

Show properties

**sequence_number**

integer

The sequence number of this event.

**summary_index**

integer

The index of the summary part within the reasoning summary.

**type**

string

The type of the event. Always `response.reasoning_summary_part.done`.

OBJECT response.reasoning_summary_part.done

```JSON
{
  "type": "response.reasoning_summary_part.done",
  "item_id": "rs_6806bfca0b2481918a5748308061a2600d3ce51bdffd5476",
  "output_index": 0,
  "summary_index": 0,
  "part": {
    "type": "summary_text",
    "text": "**Responding to a greeting**

The user just said, \"Hello!\" So, it seems I need to engage. I'll greet them back and offer help since they're looking to chat. I could say something like, \"Hello! How can I assist you today?\" That feels friendly and open. They didn't ask a specific question, so this approach will work well for starting a conversation. Let's see where it goes from there!"
  },
  "sequence_number": 1
}
```

## 

## **response.reasoning_summary_text.delta**

Emitted when a delta is added to a reasoning summary text.

**delta**

string

The text delta that was added to the summary.

**item_id**

string

The ID of the item this summary text delta is associated with.

**output_index**

integer

The index of the output item this summary text delta is associated with.

**sequence_number**

integer

The sequence number of this event.

**summary_index**

integer

The index of the summary part within the reasoning summary.

**type**

string

The type of the event. Always `response.reasoning_summary_text.delta`.

OBJECT response.reasoning_summary_text.delta

```JSON
{
  "type": "response.reasoning_summary_text.delta",
  "item_id": "rs_6806bfca0b2481918a5748308061a2600d3ce51bdffd5476",
  "output_index": 0,
  "summary_index": 0,
  "delta": "**Responding to a greeting**

The user just said, \"Hello!\" So, it seems I need to engage. I'll greet them back and offer help since they're looking to chat. I could say something like, \"Hello! How can I assist you today?\" That feels friendly and open. They didn't ask a specific question, so this approach will work well for starting a conversation. Let's see where it goes from there!",
  "sequence_number": 1
}
```

## **response.reasoning_summary_text.done**

Emitted when a reasoning summary text is completed.

**item_id**

string

The ID of the item this summary text is associated with.

**output_index**

integer

The index of the output item this summary text is associated with.

**sequence_number**

integer

The sequence number of this event.

**summary_index**

integer

The index of the summary part within the reasoning summary.

**text**

string

The full text of the completed reasoning summary.

**type**

string

The type of the event. Always `response.reasoning_summary_text.done`.

OBJECT response.reasoning_summary_text.done

```JSON
{
  "type": "response.reasoning_summary_text.done",
  "item_id": "rs_6806bfca0b2481918a5748308061a2600d3ce51bdffd5476",
  "output_index": 0,
  "summary_index": 0,
  "text": "**Responding to a greeting**

The user just said, \"Hello!\" So, it seems I need to engage. I'll greet them back and offer help since they're looking to chat. I could say something like, \"Hello! How can I assist you today?\" That feels friendly and open. They didn't ask a specific question, so this approach will work well for starting a conversation. Let's see where it goes from there!",
  "sequence_number": 1
}
```

## 

## **response.image_generation_call.completed**

Emitted when an image generation tool call has completed and the final image is available.

**item_id**

string

The unique identifier of the image generation item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.image_generation_call.completed'.

OBJECT response.image_generation_call.completed

```JSON
{
  "type": "response.image_generation_call.completed",
  "output_index": 0,
  "item_id": "item-123",
  "sequence_number": 1
}
```

## **response.image_generation_call.generating**

Emitted when an image generation tool call is actively generating an image (intermediate state).

**item_id**

string

The unique identifier of the image generation item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of the image generation item being processed.

**type**

string

The type of the event. Always 'response.image_generation_call.generating'.

OBJECT response.image_generation_call.generating

```JSON
{
  "type": "response.image_generation_call.generating",
  "output_index": 0,
  "item_id": "item-123",
  "sequence_number": 0
}
```

## **response.image_generation_call.in_progress**

Emitted when an image generation tool call is in progress.

**item_id**

string

The unique identifier of the image generation item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of the image generation item being processed.

**type**

string

The type of the event. Always 'response.image_generation_call.in_progress'.

OBJECT response.image_generation_call.in_progress

```JSON
{
  "type": "response.image_generation_call.in_progress",
  "output_index": 0,
  "item_id": "item-123",
  "sequence_number": 0
}
```

## **response.image_generation_call.partial_image**

Emitted when a partial image is available during image generation streaming.

**item_id**

string

The unique identifier of the image generation item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**partial_image_b64**

string

Base64-encoded partial image data, suitable for rendering as an image.

**partial_image_index**

integer

0-based index for the partial image (backend is 1-based, but this is 0-based for the user).

**sequence_number**

integer

The sequence number of the image generation item being processed.

**type**

string

The type of the event. Always 'response.image_generation_call.partial_image'.

OBJECT response.image_generation_call.partial_image

```JSON
{
  "type": "response.image_generation_call.partial_image",
  "output_index": 0,
  "item_id": "item-123",
  "sequence_number": 0,
  "partial_image_index": 0,
  "partial_image_b64": "..."
}
```

## 

## 

## **response.mcp_call.arguments.delta**

Emitted when there is a delta (partial update) to the arguments of an MCP tool call.

**delta**

object

The partial update to the arguments for the MCP tool call.

**item_id**

string

The unique identifier of the MCP tool call item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_call.arguments_delta'.

OBJECT response.mcp_call.arguments.delta

```JSON
{
  "type": "response.mcp_call.arguments.delta",
  "output_index": 0,
  "item_id": "item-abc",
  "delta": {
    "arg1": "new_value1",
    "arg2": "new_value2"
  },
  "sequence_number": 1
}
```

## **response.mcp_call.arguments.done**

Emitted when the arguments for an MCP tool call are finalized.

**arguments**

object

The finalized arguments for the MCP tool call.

**item_id**

string

The unique identifier of the MCP tool call item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_call.arguments_done'.

OBJECT response.mcp_call.arguments.done

```JSON
{
  "type": "response.mcp_call.arguments.done",
  "output_index": 0,
  "item_id": "item-abc",
  "arguments": {
    "arg1": "value1",
    "arg2": "value2"
  },
  "sequence_number": 1
}
```

## **response.mcp_call.completed**

Emitted when an MCP tool call has completed successfully.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_call.completed'.

OBJECT response.mcp_call.completed

```JSON
{
  "type": "response.mcp_call.completed",
  "sequence_number": 1
}
```

## **response.mcp_call.failed**

Emitted when an MCP tool call has failed.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_call.failed'.

OBJECT response.mcp_call.failed

```JSON
{
  "type": "response.mcp_call.failed",
  "sequence_number": 1
}
```

## **response.mcp_call.in_progress**

Emitted when an MCP tool call is in progress.

**item_id**

string

The unique identifier of the MCP tool call item being processed.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_call.in_progress'.

OBJECT response.mcp_call.in_progress

```JSON
{
  "type": "response.mcp_call.in_progress",
  "output_index": 0,
  "item_id": "item-abc",
  "sequence_number": 1
}
```

## 

## **response.mcp_list_tools.completed**

Emitted when the list of available MCP tools has been successfully retrieved.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_list_tools.completed'.

OBJECT response.mcp_list_tools.completed

```JSON
{
  "type": "response.mcp_list_tools.completed",
  "sequence_number": 1
}
```

## **response.mcp_list_tools.failed**

Emitted when the attempt to list available MCP tools has failed.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_list_tools.failed'.

OBJECT response.mcp_list_tools.failed

```JSON
{
  "type": "response.mcp_list_tools.failed",
  "sequence_number": 1
}
```

## **response.mcp_list_tools.in_progress**

Emitted when the system is in the process of retrieving the list of available MCP tools.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.mcp_list_tools.in_progress'.

OBJECT response.mcp_list_tools.in_progress

```JSON
{
  "type": "response.mcp_list_tools.in_progress",
  "sequence_number": 1
}
```

## 

## **response.code_interpreter_call.in_progress**

Emitted when a code interpreter call is in progress.

**item_id**

string

The unique identifier of the code interpreter tool call item.

**output_index**

integer

The index of the output item in the response for which the code interpreter call is in progress.

**sequence_number**

integer

The sequence number of this event, used to order streaming events.

**type**

string

The type of the event. Always `response.code_interpreter_call.in_progress`.

OBJECT response.code_interpreter_call.in_progress

```JSON
{
  "type": "response.code_interpreter_call.in_progress",
  "output_index": 0,
  "item_id": "ci_12345",
  "sequence_number": 1
}
```

## **response.code_interpreter_call.interpreting**

Emitted when the code interpreter is actively interpreting the code snippet.

**item_id**

string

The unique identifier of the code interpreter tool call item.

**output_index**

integer

The index of the output item in the response for which the code interpreter is interpreting code.

**sequence_number**

integer

The sequence number of this event, used to order streaming events.

**type**

string

The type of the event. Always `response.code_interpreter_call.interpreting`.

OBJECT response.code_interpreter_call.interpreting

```JSON
{
  "type": "response.code_interpreter_call.interpreting",
  "output_index": 4,
  "item_id": "ci_12345",
  "sequence_number": 1
}
```

## **response.code_interpreter_call.completed**

Emitted when the code interpreter call is completed.

**item_id**

string

The unique identifier of the code interpreter tool call item.

**output_index**

integer

The index of the output item in the response for which the code interpreter call is completed.

**sequence_number**

integer

The sequence number of this event, used to order streaming events.

**type**

string

The type of the event. Always `response.code_interpreter_call.completed`.

OBJECT response.code_interpreter_call.completed

```JSON
{
  "type": "response.code_interpreter_call.completed",
  "output_index": 5,
  "item_id": "ci_12345",
  "sequence_number": 1
}
```

## 

## **response.code_interpreter_call_code.delta**

Emitted when a partial code snippet is streamed by the code interpreter.

**delta**

string

The partial code snippet being streamed by the code interpreter.

**item_id**

string

The unique identifier of the code interpreter tool call item.

**output_index**

integer

The index of the output item in the response for which the code is being streamed.

**sequence_number**

integer

The sequence number of this event, used to order streaming events.

**type**

string

The type of the event. Always `response.code_interpreter_call_code.delta`.

OBJECT response.code_interpreter_call_code.delta

```JSON
{
  "type": "response.code_interpreter_call_code.delta",
  "output_index": 0,
  "item_id": "ci_12345",
  "delta": "print('Hello, world')",
  "sequence_number": 1
}
```

## **response.code_interpreter_call_code.done**

Emitted when the code snippet is finalized by the code interpreter.

**code**

string

The final code snippet output by the code interpreter.

**item_id**

string

The unique identifier of the code interpreter tool call item.

**output_index**

integer

The index of the output item in the response for which the code is finalized.

**sequence_number**

integer

The sequence number of this event, used to order streaming events.

**type**

string

The type of the event. Always `response.code_interpreter_call_code.done`.

OBJECT response.code_interpreter_call_code.done

```JSON
{
  "type": "response.code_interpreter_call_code.done",
  "output_index": 3,
  "item_id": "ci_12345",
  "code": "print('done')",
  "sequence_number": 1
}
```

## 

## **response.output_text_annotation.added**

Emitted when an annotation is added to output text content.

**annotation**

object

The annotation object being added. (See annotation schema for details.)

**annotation_index**

integer

The index of the annotation within the content part.

**content_index**

integer

The index of the content part within the output item.

**item_id**

string

The unique identifier of the item to which the annotation is being added.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.output_text_annotation.added'.

OBJECT response.output_text_annotation.added

```JSON
{
  "type": "response.output_text_annotation.added",
  "item_id": "item-abc",
  "output_index": 0,
  "content_index": 0,
  "annotation_index": 0,
  "annotation": {
    "type": "text_annotation",
    "text": "This is a test annotation",
    "start": 0,
    "end": 10
  },
  "sequence_number": 1
}
```

## **response.queued**

Emitted when a response is queued and waiting to be processed.

**response**

object

The full response object that is queued.

Show properties

**sequence_number**

integer

The sequence number for this event.

**type**

string

The type of the event. Always 'response.queued'.

OBJECT response.queued

```JSON
{
  "type": "response.queued",
  "response": {
    "id": "res_123",
    "status": "queued",
    "created_at": "2021-01-01T00:00:00Z",
    "updated_at": "2021-01-01T00:00:00Z"
  },
  "sequence_number": 1
}
```

## 

## **response.reasoning.delta**

Emitted when there is a delta (partial update) to the reasoning content.

**content_index**

integer

The index of the reasoning content part within the output item.

**delta**

object

The partial update to the reasoning content.

**item_id**

string

The unique identifier of the item for which reasoning is being updated.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always 'response.reasoning.delta'.

OBJECT response.reasoning.delta

```JSON
{
  "type": "response.reasoning.delta",
  "item_id": "item-abc",
  "output_index": 0,
  "content_index": 0,
  "delta": {
    "text": "This is a test delta"
  },
  "sequence_number": 1
}
```

## **response.reasoning.done**

Emitted when the reasoning content is finalized for an item.

**content_index**

integer

The index of the reasoning content part within the output item.

**item_id**

string

The unique identifier of the item for which reasoning is finalized.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**text**

string

The finalized reasoning text.

**type**

string

The type of the event. Always 'response.reasoning.done'.

OBJECT response.reasoning.done

```JSON
{
  "type": "response.reasoning.done",
  "item_id": "item-abc",
  "output_index": 0,
  "content_index": 0,
  "text": "This is a test reasoning",
  "sequence_number": 1
}
```

## 

## **response.reasoning_summary.delta**

Emitted when there is a delta (partial update) to the reasoning summary content.

**delta**

object

The partial update to the reasoning summary content.

**item_id**

string

The unique identifier of the item for which the reasoning summary is being updated.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**summary_index**

integer

The index of the summary part within the output item.

**type**

string

The type of the event. Always 'response.reasoning_summary.delta'.

OBJECT response.reasoning_summary.delta

```JSON
{
  "type": "response.reasoning_summary.delta",
  "item_id": "item-abc",
  "output_index": 0,
  "summary_index": 0,
  "delta": {
    "text": "delta text"
  },
  "sequence_number": 1
}
```

## **response.reasoning_summary.done**

Emitted when the reasoning summary content is finalized for an item.

**item_id**

string

The unique identifier of the item for which the reasoning summary is finalized.

**output_index**

integer

The index of the output item in the response's output array.

**sequence_number**

integer

The sequence number of this event.

**summary_index**

integer

The index of the summary part within the output item.

**text**

string

The finalized reasoning summary text.

**type**

string

The type of the event. Always 'response.reasoning_summary.done'.

OBJECT response.reasoning_summary.done

```JSON
{
  "type": "response.reasoning_summary.done",
  "item_id": "item-abc",
  "output_index": 0,
  "summary_index": 0,
  "text": "This is a test reasoning summary",
  "sequence_number": 1
}
```

## **error**

Emitted when an error occurs.

**code**

string or null

The error code.

**message**

string

The error message.

**param**

string or null

The error parameter.

**sequence_number**

integer

The sequence number of this event.

**type**

string

The type of the event. Always `error`.
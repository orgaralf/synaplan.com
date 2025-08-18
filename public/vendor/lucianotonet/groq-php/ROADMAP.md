## ROADMAP

Funcionalidades planejadas para implementação:

### Groq API REST endpoints:

#### ✅ Models Endpoint 

- GET /openai/v1/models
- Retorna uma lista de todos os modelos ativos
- Exemplo: curl https://api.groq.com/openai/v1/models

#### ✅ Chat Completion Endpoint 

- POST /openai/v1/chat/completions
- Realiza uma conclusão de chat
- Corpo da Requisição: Objeto JSON com os campos prompt e model
- Exemplo: curl -X POST -H "Content-Type: application/json" -d '{"prompt": "Hello", "model": "llama3-8b-8192"}' https://api.groq.com/openai/v1/chat/completions

#### ✅ Transcriptions Endpoint 

- POST /openai/v1/audio/transcriptions
- Transcreve arquivos de áudio para texto
- Corpo da Requisição: multipart/form-data com os campos file e model
- Exemplo: curl -X POST -F "file=@sample_audio.m4a" -F "model=whisper-large-v3" https://api.groq.com/openai/v1/audio/transcriptions

#### ✅ Translations Endpoint 

- POST /openai/v1/audio/translations
- Traduz o conteúdo falado em um arquivo de áudio para o inglês
- Corpo da Requisição: multipart/form-data com os campos file e model
- Exemplo: curl -X POST -F "file=@sample_audio.m4a" -F "model=whisper-large-v3" https://api.groq.com/openai/v1/audio/translations

#### ✅ Tratamento de Erros 

- A API utiliza códigos de status de resposta HTTP personalisados para indicar falhas de solicitações à API (https://console.groq.com/docs/errors).
- Em casos de erros, o corpo da resposta conterá um objeto JSON com detalhes sobre o erro.
- Em casos de resposta tipo json_object com erros, poderá ser retornado um campo chamado "failed_generation" com o JSON inválido que causou o erro - isto não está referenciado na documentação oficial.
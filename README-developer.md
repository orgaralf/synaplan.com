# synaplan meta AI framework

This project is a layer above various AI models to offer users a different kind to
interact with AI models and handle the data.

Synaplan is created with the following core technologies:

* PHP 8.2 or higher
* various PHP modules and libraries with `composer install XY` - see composer.json
* MariaDB 11.7 (or higher, to support vector tables)
* NodeJS for some testing and data handling
* Javascript modules in node_modules, see package.json
* Apache 2.4 with php-fpm
* Ollama to run local AI models
* Various AI model API accounts like OpenAI, Anthropic, Gemini, Groq, etc.

## Database model
The model of the database is in file synaplan_structure.sql in the main directory.
Current backups of the database are always in synaplan.sql, also in the main directory.

## Talking to synaplan

The application has 4 ways to interact with:

1. WhatsApp webhook: You need a business WhatsApp number and register your number with synaplan to send messages to /webhookwa.php
2. GMail mail pickup: We offer the registration of a code word for your configuration on `smart+yourcode@synaplan.com` where we look for mails every 30 seconds.
3. API Gateway: we have a simple API and MCP server for your development pleasure. Part of the API is a divider for MCP calls, which are based on JSONRPC methods.
4. Web interface: When you login via /index.php, you will find a Chat option and configuration settings.

## Code structure

```
# web/
[Jun 13 15:26]  web/
├── .env holds all keys and passwords
├── .env.example shows you how
├── [May 31 11:47]  aiprocessor.php
├── [Jun 13 11:40]  api.php
├── [Jun  3 09:22]  composer.json
├── [Jun  3 09:22]  composer.lock
├── [Apr 24 10:21]  confirm.php
├── [Jun 10 15:35]  css
├── [Jun 12 12:36]  favicon.ico
├── [Jun 13 11:01]  gmail_callback2oauth.php
├── [Apr 24 10:21]  gmail_cron.sh
├── [May 31 11:47]  gmailrefresh.php
├── [Jun 13 11:01]  gmail_start.php
├── [Jun  6 10:37]  img
├── [Jun 13 11:00]  inc
    ├── [May 31 11:25]  _aianthropic.php
    ├── [Jun 13 10:17]  _aigoogle.php
    ├── [Jun 13 11:45]  _aigroq.php
    ├── [Jun 11 10:39]  _aiollama.php
    ├── [Jun 13 14:35]  _aiopenai.php
    ├── [Jun 13 10:17]  _aithehive.php
    ├── [Jun 11 12:03]  _basicai.php
    ├── [Jun 11 10:55]  _central.php
    ├── [Jun 13 15:36]  _confdb.php
    ├── [Jun  5 16:37]  _confdefaults.php
    ├── [Jun 13 14:36]  _confkeys.php
    ├── [Jun 13 11:38]  _confsys.php
    ├── [Apr 24 10:21]  _curler.php
    ├── [Jun 11 08:48]  _frontend.php
    ├── [Apr 24 10:21]  _inboundconf.php
    ├── [Apr 24 10:21]  _jsontools.php
    ├── [Apr 24 10:21]  _listtools.php
    ├── [Apr 24 10:21]  _mail.php
    ├── [Jun 13 11:01]  _myGMail.php
    ├── [Jun 13 11:00]  _oauth.php
    ├── [Jun 11 12:02]  _processmethods.php
    ├── [Jun 13 10:17]  _tools.php
    ├── [Apr 24 10:21]  _wasender.php
    └── [Apr 24 10:21]  _xscontrol.php
├── [May 31 11:48]  index.php
├── [Jun 10 15:35]  js
├── [May 23 08:44]  mcp.php
├── [Jun 13 15:08]  node_modules
├── [Apr 24 10:21]  outprocessor.php
├── [Jun 13 15:08]  package.json
├── [Jun 13 15:08]  package-lock.json
├── [May 31 11:47]  preprocessor.php
├── [Jun 13 15:26]  research
├── [Jun 11 08:48]  snippets
    ├── [Jun 10 11:32]  c_aimodels.php
    ├── [Jun  6 14:09]  c_ais.php
    ├── [Jun 13 11:53]  c_chat.php
    ├── [Jun 11 08:48]  c_filemanager.php
    ├── [May 24 12:25]  c_inbound.php
    ├── [Jun  1 16:24]  c_login.php
    ├── [Jun 10 10:35]  c_menu.php
    ├── [Apr 24 10:21]  c_outprocessor.php
    ├── [Jun 11 10:17]  c_preprocessor.php
    ├── [Jun  5 17:51]  c_prompts.php
    ├── [Jun 11 08:48]  c_settings.php
    ├── [Apr 24 10:21]  c_tools.php
    ├── [Apr 24 10:21]  c_unknown.php
    ├── [Jun  1 16:32]  c_welcome.php
    ├── [Jun 13 14:41]  director.php
    └── [Apr 24 10:21]  _fileform.php
├── [Apr 24 10:21]  sysmon.php
├── [Jun 12 15:00]  up
    ├── ALL USER UPLOADS LAND HERE
├── [Jun  3 09:22]  vendor
└── [May 31 11:47]  webhookwa.php
```



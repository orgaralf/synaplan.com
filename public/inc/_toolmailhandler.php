<?php
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message; // alias class exists under different namespaces depending on version; we'll type-hint loosely
use Webklex\PHPIMAP\Folder;
use Carbon\Carbon;

// Mail handler tools and prompt builder
class mailHandler {
	// ----------------------------------------------------------------------------------
	// Find all users who configured the mail handler (basic active criteria)
	// ----------------------------------------------------------------------------------
	public static function getUsersWithMailhandler(): array {
		$users = [];
		$sql = "
			SELECT b1.BOWNERID
			FROM BCONFIG b1
			WHERE b1.BGROUP = 'mailhandler'
			  AND b1.BSETTING IN ('server','username')
			  AND b1.BVALUE <> ''
			GROUP BY b1.BOWNERID
			HAVING COUNT(DISTINCT b1.BSETTING) >= 2
			   AND EXISTS (
				   SELECT 1 FROM BCONFIG b2
				   WHERE b2.BOWNERID = b1.BOWNERID
				     AND b2.BGROUP = 'mailhandler_dept'
			   )
		";
		$res = DB::Query($sql);
		while ($row = DB::FetchArr($res)) {
			$users[] = intval($row['BOWNERID']);
		}
		return $users;
	}

	// ----------------------------------------------------------------------------------
	// Build the target list JSON for a specific user
	// ----------------------------------------------------------------------------------
	public static function getTargetlistForUser(int $userId): string {
		$list = [];
		if ($userId > 0) {
			$sql = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = 'mailhandler_dept' ORDER BY CAST(BSETTING AS UNSIGNED) ASC";
			$res = DB::Query($sql);
			while ($row = DB::FetchArr($res)) {
				$parts = explode('|', $row['BVALUE']);
				$email = trim($parts[0] ?? '');
				$description = trim($parts[1] ?? '');
				$isDefault = (($parts[2] ?? '0') === '1');
				if ($email !== '') {
					$list[] = [
						'email' => $email,
						'description' => $description,
						'default' => $isDefault
					];
				}
			}
		}
		return json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	// ----------------------------------------------------------------------------------
	// Build the target list JSON from the user's mail handler departments in BCONFIG
	// ----------------------------------------------------------------------------------
	public static function getTargetlist(): string {
		$userId = 0;
		if (isset($_SESSION["is_widget"]) && $_SESSION["is_widget"] === true && isset($_SESSION["widget_owner_id"])) {
			$userId = intval($_SESSION["widget_owner_id"]);
		} elseif (isset($_SESSION["USERPROFILE"]["BID"])) {
			$userId = intval($_SESSION["USERPROFILE"]["BID"]);
		}

		$list = [];
		if ($userId > 0) {
			$sql = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $userId . " AND BGROUP = 'mailhandler_dept' ORDER BY CAST(BSETTING AS UNSIGNED) ASC";
			$res = DB::Query($sql);
			while ($row = DB::FetchArr($res)) {
				$parts = explode('|', $row['BVALUE']);
				$email = trim($parts[0] ?? '');
				$description = trim($parts[1] ?? '');
				$isDefault = (($parts[2] ?? '0') === '1');
				if ($email !== '') {
					$list[] = [
						'email' => $email,
						'description' => $description,
						'default' => $isDefault
					];
				}
			}
		}

		return json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// ----------------------------------------------------------------------------------
	// Build the full mail handler prompt by fetching the template and injecting targets
	// ----------------------------------------------------------------------------------
	public static function getMailprompt(): string {
		$promptKey = 'tools:mailhandler';
		$promptArr = BasicAI::getPromptDetails($promptKey);
		$prompt = '';

		if (isset($promptArr['BPROMPT']) && strlen($promptArr['BPROMPT']) > 0) {
			$prompt = $promptArr['BPROMPT'];
		}

		$targetJson = self::getTargetlist();
		if ($prompt !== '' && strpos($prompt, '[TARGETLIST]') !== false) {
			$prompt = str_replace('[TARGETLIST]', $targetJson, $prompt);
		}

		return $prompt;
	}

	// ----------------------------------------------------------------------------------
	// Build the mail handler prompt for a specific user (respects per-user prompts)
	// ----------------------------------------------------------------------------------
	public static function getMailpromptForUser(int $userId): string {
		// Backup session
		$backupUser = $_SESSION['USERPROFILE']['BID'] ?? null;
		if (!isset($_SESSION['USERPROFILE'])) { $_SESSION['USERPROFILE'] = []; }
		$_SESSION['USERPROFILE']['BID'] = $userId;

		$promptKey = 'tools:mailhandler';
		$promptArr = BasicAI::getPromptDetails($promptKey);
		$prompt = isset($promptArr['BPROMPT']) ? $promptArr['BPROMPT'] : '';

		$targetJson = self::getTargetlistForUser($userId);
		if ($prompt !== '' && strpos($prompt, '[TARGETLIST]') !== false) {
			$prompt = str_replace('[TARGETLIST]', $targetJson, $prompt);
		}

		// Restore session
		if ($backupUser === null) {
			unset($_SESSION['USERPROFILE']['BID']);
		} else {
			$_SESSION['USERPROFILE']['BID'] = $backupUser;
		}

		return $prompt;
	}

	// ----------------------------------------------------------------------------------
	// Run routing for a specific user against the standard sorting AI
	// ----------------------------------------------------------------------------------
	public static function runRoutingForUser(int $userId, string $subject, string $body): string {
		$systemPrompt = self::getMailpromptForUser($userId);
		$userPrompt = "Subject: " . trim($subject) . "\n\nBody: " . trim($body);

		$aiService = $GLOBALS["AI_SORT"]["SERVICE"] ?? 'AIGroq';
		if (!class_exists($aiService)) {
			return "Error: AI service not available: " . $aiService;
		}
		$response = $aiService::simplePrompt($systemPrompt, $userPrompt);
		if (is_array($response)) {
			if (isset($response['summary'])) { return trim($response['summary']); }
			if (isset($response['success']) && !$response['success']) { return "Error: AI call failed"; }
			return trim(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}
		return trim((string)$response);
	}

	// ==================================================================================================
	// IMAP FUNCTIONS (webklex/php-imap)
	// ==================================================================================================

	/**
	 * Retrieve IMAP configuration for a user from BCONFIG.
	 *
	 * Expected keys under `BGROUP = 'mailhandler'`:
	 * - server, port, protocol (imap|pop3), security (ssl|tls|none), username, password
	 *
	 * @param int $userId
	 * @return array{server:string,port:int,protocol:string,security:string,username:string,password:string}
	 */
	private static function getImapConfigForUser(int $userId): array {
		$cfg = [
			'server' => '',
			'port' => 993,
			'protocol' => 'imap',
			'security' => 'ssl',
			'username' => '',
			'password' => ''
		];
		$userId = max(0, (int)$userId);
		if ($userId <= 0) { return $cfg; }
		$sql = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = ".$userId." AND BGROUP = 'mailhandler'";
		$res = DB::Query($sql);
		while ($row = DB::FetchArr($res)) {
			switch ($row['BSETTING']) {
				case 'server': $cfg['server'] = $row['BVALUE']; break;
				case 'port': $cfg['port'] = (int)$row['BVALUE']; break;
				case 'protocol': $cfg['protocol'] = $row['BVALUE']; break;
				case 'security': $cfg['security'] = $row['BVALUE']; break;
				case 'username': $cfg['username'] = $row['BVALUE']; break;
				case 'password': $cfg['password'] = $row['BVALUE']; break;
			}
		}
		return $cfg;
	}

	/**
	 * Establish an IMAP/POP3 connection for a specific user.
	 * Uses webklex/php-imap ClientManager. Returns an array with success flag and client or error.
	 *
	 * @param int $userId
	 * @return array{success:bool, client: ?\Webklex\PHPIMAP\Client, error:?string}
	 */
	public static function imapConnectForUser(int $userId): array {
		try {
			Tools::debugCronLog("[IMAP] Begin connect for userId=".$userId."\n");
			$cfg = self::getImapConfigForUser($userId);
			$maskedUser = $cfg['username'];

			if ($cfg['server'] === '' || $cfg['username'] === '') {
				Tools::debugCronLog("[IMAP] Missing server or username\n");
				return ['success' => false, 'client' => null, 'error' => 'Missing server or username'];
			}
			$encryption = null;
			if ($cfg['security'] === 'ssl') { $encryption = 'ssl'; }
			elseif ($cfg['security'] === 'tls') { $encryption = 'tls'; }
			else { $encryption = false; }
			Tools::debugCronLog("[IMAP] Derived encryption=".($encryption === false ? 'none' : $encryption)."\n");

			Tools::debugCronLog("[IMAP] Creating ClientManager and client instance...\n");
			$cm = new ClientManager();
			$clientConfig = [
				'host'          => $cfg['server'],
				'port'          => $cfg['port'] ?: 993,
				'protocol'      => strtolower($cfg['protocol']) === 'pop3' ? 'pop3' : 'imap',
				'encryption'    => $encryption,
				'validate_cert' => true,
				'username'      => $cfg['username'],
				'password'      => $cfg['password'],
				'authentication' => null,
				'proxy'         => null,
			];
			// Remove null-only entries to avoid vendor merge issues and reduce noise
			$clientConfig = array_filter($clientConfig, function($v) { return $v !== null; });
			Tools::debugCronLog("[IMAP] Client config prepared (sanitized)\n".print_r($clientConfig, true));
			$client = $cm->make($clientConfig);
			Tools::debugCronLog("[IMAP] Connecting...\n");
			$client->connect();
			Tools::debugCronLog("[IMAP] Connected successfully\n");
			return ['success' => true, 'client' => $client, 'error' => null];
		} catch (\Throwable $e) {
			Tools::debugCronLog("[IMAP] Connection failed: ".$e->getMessage()."\n");
			return ['success' => false, 'client' => null, 'error' => $e->getMessage()];
		}
	}

	/**
	 * List messages from INBOX since the last successful fetch for the given user.
	 * Persists `last_seen` timestamp under BGROUP `mailhandler_state` for the user.
	 *
	 * @param int $userId
	 * @param int $fallbackWindowSeconds If no state exists, how far back to fetch (default 1 day)
	 * @return array{success:bool, error:?string, client:?\Webklex\PHPIMAP\Client, folder:?Folder, messages:?\Webklex\PHPIMAP\Support\MessageCollection, last_seen:int}
	 */
	public static function imapListSinceLast(int $userId, int $fallbackWindowSeconds = 86400): array {
		$ret = ['success' => false, 'error' => null, 'client' => null, 'folder' => null, 'messages' => null, 'last_seen' => 0];
		$login = self::imapConnectForUser($userId);
		if (!$login['success']) { $ret['error'] = $login['error']; return $ret; }
		$client = $login['client'];
		$ret['client'] = $client;
		try {
			$folder = $client->getFolder('INBOX');
			$ret['folder'] = $folder;
			// Load last_seen
			$stateSQL = "SELECT BVALUE FROM BCONFIG WHERE BOWNERID = ".$userId." AND BGROUP='mailhandler_state' AND BSETTING='last_seen' LIMIT 1";
			$stateRes = DB::Query($stateSQL);
			$stateRow = DB::FetchArr($stateRes);
			$lastSeenTs = 0;
			if ($stateRow && is_numeric($stateRow['BVALUE'])) { $lastSeenTs = (int)$stateRow['BVALUE']; }
			if ($lastSeenTs <= 0) { $lastSeenTs = time() - max(60, $fallbackWindowSeconds); }
			$ret['last_seen'] = $lastSeenTs;
			$since = Carbon::createFromTimestamp($lastSeenTs);
			$messages = $folder->messages()->since($since)->leaveUnread()->get();
			$ret['messages'] = $messages;
			$ret['success'] = true;
			return $ret;
		} catch (\Throwable $e) {
			$ret['error'] = $e->getMessage();
			return $ret;
		}
	}

	/**
	 * Mark and expunge-delete a message from the server.
	 *
	 * @param \Webklex\PHPIMAP\Message $message
	 * @return bool True on success
	 */
	public static function imapDeleteMessage($message): bool {
		try {
			if ($message === null) { return false; }
			$message->delete();
			$folder = method_exists($message, 'getFolder') ? $message->getFolder() : null;
			if ($folder && method_exists($folder, 'expunge')) { $folder->expunge(); }
			return true;
		} catch (\Throwable $e) {
			if ($GLOBALS['debug'] ?? false) { error_log('imapDeleteMessage error: '.$e->getMessage()); }
			return false;
		}
	}

	/**
	 * Forward a message to a target address via internal mailer.
	 * Uses `_mymail()` and sets Reply-To to the original sender. If the mail has attachments,
	 * the first attachment is forwarded as a file attachment (subsequent ones are ignored for now).
	 *
	 * @param \Webklex\PHPIMAP\Message $message The source message
	 * @param string $targetEmail Target recipient email
	 * @param string $targetName Optional recipient name
	 * @return bool True if sent
	 */
	public static function imapForwardMessage($message, string $targetEmail, string $targetName = ''): bool {
		try {
			if ($message === null || trim($targetEmail) === '') { return false; }
			$origSubject = trim((string)$message->getSubject());
			$subject = (strlen($origSubject) > 0 ? 'Fwd: '.$origSubject : 'Fwd: (no subject)');
			$html = (string)$message->getHTMLBody(true);
			$plain = (string)$message->getTextBody();
			if ($html === '' && $plain !== '') { $html = nl2br($plain); }
			if ($plain === '' && $html !== '') { $plain = strip_tags($html); }
			$fromAddresses = $message->getFrom();
			$replyTo = '';
			if (is_array($fromAddresses) && count($fromAddresses) > 0) {
				$first = $fromAddresses[0];
				$replyTo = trim(($first->mail ?? ''));
			}
			$attachPath = '';
			$attachments = $message->getAttachments();
			if ($attachments && $attachments->count() > 0) {
				// Save only the first attachment to a temp file for forwarding
				$att = $attachments->first();
				$tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
				$fname = $att->getName() ?: ('attach_'.time());
				$target = $tmpDir.$fname;
				try { $att->save($tmpDir, $fname); $attachPath = $target; } catch (\Throwable $e) { $attachPath = ''; }
			}
			// Build recipient with optional name
			$to = $targetEmail.(strlen($targetName)>0 ? ';'.$targetName : '');
			$from = 'noreply@synaplan.com;Synaplan Mailhandler';
			$ok = _mymail($from, $to, $subject, $html, $plain, $replyTo, $attachPath);
			if ($attachPath !== '' && file_exists($attachPath)) { @unlink($attachPath); }
			return (bool)$ok;
		} catch (\Throwable $e) {
			if ($GLOBALS['debug'] ?? false) { error_log('imapForwardMessage error: '.$e->getMessage()); }
			return false;
		}
	}

	/**
	 * Update the user's mailhandler state `last_seen` to now.
	 *
	 * @param int $userId
	 * @return void
	 */
	public static function imapUpdateLastSeen(int $userId): void {
		$userId = max(0, (int)$userId);
		if ($userId <= 0) { return; }
		$now = time();
		$check = "SELECT BID FROM BCONFIG WHERE BOWNERID = ".$userId." AND BGROUP='mailhandler_state' AND BSETTING='last_seen'";
		$res = DB::Query($check);
		if (DB::CountRows($res) > 0) {
			DB::Query("UPDATE BCONFIG SET BVALUE='".$now."' WHERE BOWNERID= ".$userId." AND BGROUP='mailhandler_state' AND BSETTING='last_seen'");
		} else {
			DB::Query("INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) VALUES (".$userId.", 'mailhandler_state', 'last_seen', '".$now."')");
		}
	}
}



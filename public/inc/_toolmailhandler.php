<?php

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
}



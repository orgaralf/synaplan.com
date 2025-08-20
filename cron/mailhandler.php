<?php
// Simple CLI cron script to test mail handler routing per user
// Usage (CLI): php cron/mailhandler.php

// Bootstrap
$root = __DIR__ . '/../public/';
require_once($root . 'inc/_coreincludes.php');

// Ensure this runs only via CLI for now
if (php_sapi_name() !== 'cli') {
	echo "This script must be run from the command line.\n";
	exit(1);
}

// CLI args: optional user id and optional debug flag
// Supports: php cron/mailhandler.php 2 DEBUG
//           php cron/mailhandler.php --user=2 --debug
//           php cron/mailhandler.php -u 2 -d
$DEBUG_CRON = false;
$targetUserId = null;

if (isset($argv) && is_array($argv)) {
	for ($i = 1; $i < count($argv); $i++) {
		$arg = (string)$argv[$i];
		$lower = strtolower($arg);
		if (is_numeric($arg)) {
			$targetUserId = (int)$arg;
			continue;
		}
		if ($lower === 'debug' || $lower === '--debug' || $lower === '-d') {
			$DEBUG_CRON = true;
			continue;
		}
		if (substr($lower, 0, 7) === '--user=') {
			$targetUserId = (int)substr($arg, 7);
			continue;
		}
		if ($lower === '--user' || $lower === '-u') {
			if (($i + 1) < count($argv) && is_numeric($argv[$i + 1])) {
				$targetUserId = (int)$argv[++$i];
			}
			continue;
		}
		if ($lower === '--help' || $lower === '-h' || $lower === 'help') {
			echo "Usage: php cron/mailhandler.php [--user=ID|-u ID|ID] [--debug|-d|DEBUG]\n";
			echo "If --debug is not provided, the script runs silently.\n";
			exit(0);
		}
	}
}

// helper to print only in debug mode
if (!function_exists('dlog')) {
	function dlog(string $msg): void {
		if (!empty($GLOBALS['DEBUG_CRON'])) { echo $msg; }
	}
}

// Prevent concurrent runs across machines using BCONFIG (BOWNERID=0, BGROUP='CRON', BSETTING='MAILHANDLER')
if (Tools::cronRunCheck('MAILHANDLER')) {
	dlog("MAILHANDLER cron is already running (" . Tools::cronTime('MAILHANDLER') . ").\n");
	exit(0);
}

// Ensure cleanup on normal exit
register_shutdown_function(function() {
	Tools::deleteCron('MAILHANDLER');
});

dlog("Starting mailhandler cron (dev mode)\n");

// 1) Get all users with active mail handler settings
$users = mailHandler::getUsersWithMailhandler();
// Filter to specific user if requested
if ($targetUserId !== null) {
	$users = array_values(array_filter($users, function($uid) use ($targetUserId) { return (int)$uid === (int)$targetUserId; }));
}
// if no users with mail handler configuration found, exit
if (count($users) === 0) {
	dlog("No users with mail handler configuration found.\n");
	exit(0);
}


dlog("Found ".count($users)." user(s) with mail handler configured.\n");

// Example input (development): replace with real fetched emails later
$exampleSubject = 'Nachfrage';
$exampleBody = 'Hello orga.zone, wie ist das wetter im cyberspace?';

foreach ($users as $uid) {
	dlog("\n---\nUser ID: $uid\n");
	// 2) Build prompt for user (fetch prompt details and inject [TARGETLIST])
	$prompt = mailHandler::getMailpromptForUser($uid);
	if (strlen($prompt) < 10) {
		dlog("Prompt missing or too short; skipping user $uid.\n");
		continue;
	}
	// 3) Select the standard sorting AI and send the prompt + example content
	$answer = mailHandler::runRoutingForUser($uid, $exampleSubject, $exampleBody);
	// 4) Print the AI answer to stdout
	dlog("Prompt length: ".strlen($prompt)." bytes\n");
	dlog("AI selected target: ".$answer."\n");
	// Touch heartbeat so other runners can see it's active
	Tools::updateCron('MAILHANDLER');
}


dlog("\nMailhandler cron finished.\n");

// Remove cron lock so the next run can start
Tools::deleteCron('MAILHANDLER');



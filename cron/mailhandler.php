<?php
// Simple CLI cron script to test mail handler routing per user
// Usage (CLI): php cron/mailhandler.php

// Bootstrap
chdir(__DIR__ . '/..');
$root = __DIR__ . '/../public/';
require_once($root . 'inc/_coreincludes.php');

// Ensure this runs only via CLI for now
if (php_sapi_name() !== 'cli') {
	echo "This script must be run from the command line.\n";
	exit(1);
}

echo "Starting mailhandler cron (dev mode)\n";

// 1) Get all users with active mail handler settings
$users = mailHandler::getUsersWithMailhandler();
if (count($users) === 0) {
	echo "No users with mail handler configuration found.\n";
	exit(0);
}

echo "Found ".count($users)." user(s) with mail handler configured.\n";

// Example input (development): replace with real fetched emails later
$exampleSubject = 'Order inquiry for wine delivery';
$exampleBody = 'Hello, we would like to know the availability of your red wines in large bottles and whether overnight delivery is possible.';

foreach ($users as $uid) {
	echo "\n---\nUser ID: $uid\n";
	// 2) Build prompt for user (fetch prompt details and inject [TARGETLIST])
	$prompt = mailHandler::getMailpromptForUser($uid);
	if (strlen($prompt) < 10) {
		echo "Prompt missing or too short; skipping user $uid.\n";
		continue;
	}
	// 3) Select the standard sorting AI and send the prompt + example content
	$answer = mailHandler::runRoutingForUser($uid, $exampleSubject, $exampleBody);
	// 4) Print the AI answer to stdout
	echo "Prompt length: ".strlen($prompt)." bytes\n";
	echo "AI selected target: ".$answer."\n";
}

echo "\nMailhandler cron finished.\n";



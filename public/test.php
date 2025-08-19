<?php
set_time_limit(0);
//==================================================================================
/*
 WebHook for WhatsApp 1.0
 written by puzzler - Ralf Schwoebel, rs(at)metadist.de

 Tasks of this file: 
 . include the whatsapp cloud api
 . retrieve all the messages, files, cards and possible WhatsApp messages
 . decide, if the user is new, continuing or changing the conversation
 . save the conversation in the database
 . hand over to sending
*/
//==================================================================================
require_once(__DIR__ . '/../vendor/autoload.php');

// ------------------------------------------------------ base config
require_once(__DIR__ . '/inc/_confkeys.php');
require_once(__DIR__ . '/inc/_confsys.php');
require_once(__DIR__ . '/inc/_confdb.php');
require_once(__DIR__ . '/inc/_mail.php');
// central tool
require_once(__DIR__ . '/inc/_central.php');

require_once(__DIR__ . '/inc/_curler.php');
// Initialize the API
use Web\Inc\Curler;
use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;

echo "Testing...\n";
echo _mymail("rs@metadist.de", "rs@metadist.de", "Test", "Test", "sdfg sdfg dsf");

exit;

$testSQL = "SELECT * FROM BMESSAGES limit 1";
$res = db::Query($testSQL);
$one = db::FetchArr($res);
print json_encode($one, JSON_PRETTY_PRINT);
// ****************************************************************************************************** 

print "You are here: " . $GLOBALS["appPath"] . "\n";
// ****************************************************************************************************** 
$groqkey = file_get_contents('./.keys/.groqkey.txt');
$client = new Groq($groqkey);

$arrMessages = [
    ['role' => 'system', 'content' => "You are a helpful assistant that can answer questions and help with tasks."],
];

$arrMessages[] = ['role' => 'user', 'content' => "Tell me a joke. Could you answer in German? And make the result a valid JSON object with 'JOKETEXT' and 'JOKETYPE' as the key."];
try {
    $chat = $client->chat()->completions()->create([
        'model' => 'deepseek-r1-distill-llama-70b',
        'reasoning_format' => 'parsed',
        'messages' => $arrMessages
    ]);
} catch (GroqException $err) {
    print "*APItopic Error - Ralf made a bubu - please mail that to him: * " . $err->getMessage();
}
//return $chat->message->content;
// the prompt asks for a JSON object, so we need to decode it
//print_r(json_decode($chat['choices'][0]['message']['content'], true));
$myTextArr = explode("</think>\n", $chat['choices'][0]['message']['content']);
print trim($myTextArr[1]);

/* theHive


$arrRes = Curler::callJson('https://api.thehive.ai/api/v3/hive/flux-schnell-enhanced', 
    ['authorization: Bearer ' . $GLOBALS['theHiveKey'], 'Content-Type: application/json'], 
    ['input' => [
        'prompt' => 'Ein Influencer mit einer teueren Uhr auf der Kö in Düsseldorf. Er deutet auf ein teures Geschäft.',
        'image_size' => ['width' => 1024, 'height' => 1024],
        'num_inference_steps' => 15,
        'num_images' => 1,
    ]]);
*/

/* brave Test */
$arrRes = Curler::callJson('https://api.search.brave.com/res/v1/web/search?q=news&search_lang=de&country='.strtoupper("de").'&count=4', 
    ['Accept: application/json', 'Accept-Encoding: gzip', 'X-Subscription-Token: '.$GLOBALS['braveKey']]);

// echo json_encode($arrRes);

print_r($arrRes);


/* 
chromium-browser --headless 
--no-sandbox 
--user-data-dir=/root/ 
--force-device-scale-factor=1 
--window-size=1200,1600 
--screenshot=filename.png
--screenshot https://www.google.com/


// with default base URL
$client = \ArdaGnsrn\Ollama\Ollama::client('http://localhost:11434');


$completions = $client->completions()->create([
    'model' => 'llama3.2-vision:11b',
    'prompt' => 'Describe image! Answer with list of keywords, like "animal, cat, dog, bird".',
    'images' => [$myimage],
]);

$arrRes = $completions->toArray();

echo $arrRes['response'];


curl -s --compressed "https://api.search.brave.com/res/v1/web/search?q=brave+search" \
  -H "Accept: application/json" \
  -H "Accept-Encoding: gzip" \
  -H "X-Subscription-Token: <YOUR_API_KEY>"

curl --location --request POST 'https://api.thehive.ai/api/v3/hive/flux-schnell-enhanced' \
--header 'authorization: Bearer <API_KEY>' \
--header 'Content-Type: application/json' \
--data '{
  "input": {
    "prompt": "Nestled within the depths of an enchanted forest, a wooden bridge serves as a pathway lined with glowing lanterns, casting a warm, golden light across its surface. Surrounded by dense trees, the bridge is carpeted with fallen leaves, adding to the mystical ambiance of the setting. The air seems filled with a magical mist, enhancing the dreamlike quality of the forest, inviting onlookers into a tranquil, otherworldly journey.",
    "image_size": { "width": 1024, "height": 1024},
    "num_inference_steps": 15,
    "num_images": 2,
    "seed": 67,
    "output_format": "jpeg",
    "output_quality": 90
  }
}'

*/
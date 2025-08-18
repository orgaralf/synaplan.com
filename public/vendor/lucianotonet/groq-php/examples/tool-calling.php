<div>
<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    echo "<strong>user:</strong><br>";
    echo "$message<br>";

    function getNbaScore($teamName)
    {
        // Example dummy function hard coded to return the score of an NBA game
        if (strpos(strtolower($teamName), 'warriors') !== false) {
            return json_encode([
                "game_id" => "401585601",
                "status" => 'Final',
                "home_team" => "Los Angeles Lakers",
                "home_team_score" => 121,
                "away_team" => "Golden State Warriors",
                "away_team_score" => 128
            ]);
        } elseif (strpos(strtolower($teamName), 'lakers') !== false) {
            return json_encode([
                "game_id" => "401585601",
                "status" => 'Final',
                "home_team" => "Los Angeles Lakers",
                "home_team_score" => 121,
                "away_team" => "Golden State Warriors",
                "away_team_score" => 128
            ]);
        } elseif (strpos(strtolower($teamName), 'nuggets') !== false) {
            return json_encode([
                "game_id" => "401585577",
                "status" => 'Final',
                "home_team" => "Miami Heat",
                "home_team_score" => 88,
                "away_team" => "Denver Nuggets",
                "away_team_score" => 100
            ]);
        } elseif (strpos(strtolower($teamName), 'heat') !== false) {
            return json_encode([
                "game_id" => "401585577",
                "status" => 'Final',
                "home_team" => "Miami Heat",
                "home_team_score" => 88,
                "away_team" => "Denver Nuggets",
                "away_team_score" => 100
            ]);
        } else {
            return json_encode([
                "team_name" => $teamName,
                "score" => "unknown"
            ]);
        }
    }

    $messages = [
        [
            'role' => 'system',
            'content' => "You shall call the function 'getNbaScore' to answer questions around NBA game scores. Include the team and their opponent in your response."
        ],
        [
            'role' => 'user',
            'content' => $message
        ]
    ];

    $tools = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'getNbaScore',
                'description' => 'Get the score for a given NBA game',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        "team_name" => [
                            "type" => "string",
                            "description" => "The name of the NBA team (e.g. 'Golden State Warriors')",
                        ]
                    ],
                    "required" => ["team_name"],
                ],
            ],
        ]
    ];

    try {
        $response = $groq->chat()->completions()->create([
            'model' => 'llama3-groq-70b-8192-tool-use-preview',
            // 'model' => 'mixtral-8x7b-32768', // mixtral-8x7b-32768, gemma-7b-it
            'messages' => $messages,
            "tool_choice" => "auto",
            "tools" => $tools
        ]);
    } catch (\LucianoTonet\GroqPHP\GroqException $err) {
        echo $err->getCode() . "<br>" . $err->getMessage() . "<br>" . $err->getType() . "<br>";
        print_r($err->getHeaders());
        echo "<strong>assistant:</strong><br>Desculpe, não consegui entender sua solicitação. Tente novamente.<br>";
        exit;
    }

    echo "<strong>assistant:</strong> " . "<br>";

    if (isset($response['choices'][0]['message']['tool_calls'])) {
        $tool_calls = $response['choices'][0]['message']['tool_calls'];
        foreach ($tool_calls as $tool_call) {
            if ($tool_call['function']['name'] == 'getNbaScore') {
                $function_args = json_decode($tool_call['function']['arguments'], true);

                echo "<i>> Calling tool...</i>" . "<br>";

                $function_response = getNbaScore($function_args['team_name']);

                echo "<i>> Building response...</i>" . "<br>";

                $messages[] = [
                    'tool_call_id' => $tool_call['id'],
                    'role' => 'tool',
                    'name' => 'getNbaScore',
                    'content' => $function_response,
                ];
            }
        }

        try {
            $response = $groq->chat()->completions()->create([
                'model' => 'llama3-groq-70b-8192-tool-use-preview',
                // 'model' => 'mixtral-8x7b-32768',
                'messages' => $messages
            ]);
        } catch (\LucianoTonet\GroqPHP\GroqException $err) {
            echo $err->getCode() . "<br>" . $err->getMessage() . "<br>" . $err->getType() . "<br>";
            print_r($err->getHeaders());
            echo "<strong>assistant:</strong><br>Desculpe, não consegui entender sua solicitação. Tente novamente.<br>";
            exit;
        }
    }

    echo "<strong>" . $response['choices'][0]['message']['role'] . ":</strong> " . "<br>";
    echo $response['choices'][0]['message']['content'] . "<br>";
} else {
    echo "<small>Ask about NBA game scores.<br/>Results will be mocked for demo purposes.</small><br><br>";
}
?>
</div>
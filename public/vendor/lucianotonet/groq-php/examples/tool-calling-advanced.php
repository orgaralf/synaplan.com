<div>
<?php
require __DIR__ . '/_input.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    echo "<strong>user:</strong><br>$message<br>";

    function getCurrentDateTimeTool(array $parameters = []): string
    {
        $time_zone = $parameters['time_zone'] ?? 'UTC';
        date_default_timezone_set($time_zone);
        return date('Y-m-d H:i:s') . " ($time_zone)";
    }

    function getCurrentWeatherTool(array $parameters = []): string
    {
        $location = $parameters['location'] ?? 'New York';
        return '30°C, sunny in ' . $location; // Simulation of weather data
    }

    $messages = [
        [
            'role' => 'system',
            'content' => 'You are a helpful AI assistant. Always answer in a concise manner. Execute the functions "getCurrentDateTimeTool" or "getCurrentWeatherTool" to answer the user correctly, if needed.'
        ],
        [
            'role' => 'user',
            'content' => $message
        ]
    ];

    $tools = [
        [
            "type" => "function",
            "function" => [
                "name" => "getCurrentDateTimeTool",
                "description" => "Get the current time in any format supported by PHP's date() function, considering the time zone.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "time_zone" => [
                            "type" => "string",
                            "description" => "Time zone for which to get the time."
                        ],
                    ],
                    "required" => ["time_zone"],
                    "default" => ["time_zone" => "UTC"]
                ],
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "getCurrentWeatherTool",
                "description" => "Get the current weather in a specific location.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "location" => [
                            "type" => "string",
                            "description" => "Location for which to get the weather."
                        ],
                    ],
                    "required" => ["location"],
                    "default" => ["location" => "New York"]
                ],
            ]
        ]
    ];

    try {
        $response = $groq->chat()->completions()->create([
            'model' => 'llama3-groq-70b-8192-tool-use-preview',
            'messages' => $messages,
            "temperature" => 0,
            "tool_choice" => "auto",
            "tools" => $tools,
            "parallel_tool_calls" => false
        ]);
    } catch (\LucianoTonet\GroqPHP\GroqException $err) {
        echo $err->getCode() . "<br>" . $err->getMessage() . "<br>" . $err->getType() . "<br>";
        print_r($err->getHeaders());
        echo "<strong>assistant:</strong><br>Sorry, I couldn't understand your request. Please try again.<br>";
        exit;
    }

    echo "<strong>" . $response['choices'][0]['message']['role'] . ":</strong><br>";

    if (!empty($response['choices'][0]['message']['tool_calls'])) {
        foreach ($response['choices'][0]['message']['tool_calls'] as $tool_call) {
            if ($tool_call['function']['name']) {
                $function_args = json_decode($tool_call['function']['arguments'], true);
                echo "<i>> Calling tool...</i><br>";
                $function_response = $tool_call['function']['name']($function_args);
                echo "<i>> Building response...</i><br>";

                $messages[] = [
                    'tool_call_id' => $tool_call['id'],
                    'role' => 'tool',
                    'name' => $tool_call['function']['name'],
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
            echo "<strong>assistant:</strong><br>Sorry, I couldn't understand your request. Please try again.<br>";
            exit;
        }
    }

    echo $response['choices'][0]['message']['content'] . "<br>";
} else {
    echo "<small>Ask questions like \"What time is it in UTC-3?\" or \"How is the weather in London?\".<br/>Results will be simulated for demonstration purposes.</small><br><br>";
}
?>
</div>
<?php
error_reporting(0);
header('Content-Type: application/json');
include_once 'sync.php';

// --- 1. CONFIGURATION ---
$db_host   = 'localhost';
$db_user   = '';
$db_pass   = '';
$db_name   = '';
$apiKey    = '';
$cacheFile = 'knowledge.json';

// --- 2. MATCHING SETTINGS ---
$min_match_percent = 75;
$stop_words = ['what','is','are','the','tell','me','about','a','an','of','in','please','explain'];
$greetings  = ['hi', 'hello', 'hallo', 'hey', 'p', 'halo', 'assalamualaikum', 'good morning', 'good afternoon'];

// --- 3. DATABASE & CACHE INIT ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) die(json_encode(["reply" => "Database Connection Error"]));

if (!file_exists($cacheFile)) refreshKnowledgeCache($conn);
$knowledge = json_decode(file_get_contents($cacheFile), true);

// --- 4. INPUT PROCESSING ---
$question = isset($_GET['question']) ? strtolower(trim($_GET['question'])) : "";
if (empty($question)) die(json_encode(["reply" => "I'm listening..."]));

// Clean punctuation and split into words
$cleanInput = preg_replace('/[^\w\s]/', '', $question);
$inputWords = explode(" ", $cleanInput);
$filteredInput = array_diff($inputWords, $stop_words);

// --- 5. THE GREETING SHIELD (Anti-Loop) ---
$isGreeting = false;
foreach ($inputWords as $word) {
    if (in_array($word, $greetings)) {
        $isGreeting = true;
        break;
    }
}

$bestMatch = ["id" => null, "score" => 0, "percent" => 0];

if ($isGreeting) {
    // Pick a random greeting from existing ones in the JSON to save API calls
    $existingGreetings = array_filter($knowledge, function($e) {
        return $e['token'] === 'greeting';
    });

    if (!empty($existingGreetings)) {
        $randomEntry = $existingGreetings[array_rand($existingGreetings)];
        $reply = $randomEntry['fact'];
        $source = "Local Cache (Greeting Shield)";
        $winnerId = $randomEntry['id'];
        goto output; // Skip further processing
    } else {
        // Fallback if no greetings are in DB yet
        $reply = "Hello! How can I help you today?";
        $source = "Static Default Greeting";
        $winnerId = "static";
        goto output;
    }
}

// --- 6. SCORING LOGIC (DeepSeek-Lite Distillation) ---
foreach ($knowledge as $entry) {
    $currentScore = 0;
    $tokenHit = false;

    foreach ($filteredInput as $word) {
        if (strlen($word) < 2) continue;

        // Priority 1: Direct Token Match
        if (str_contains($word, $entry['token']) || str_contains($entry['token'], $word)) {
            $currentScore += 60;
            $tokenHit = true;
        }

        // Priority 2: Fuzzy Context Match
        foreach ($entry['context'] as $ctx) {
            similar_text($word, $ctx, $p);
            if ($p >= $min_match_percent) {
                $currentScore += ($p * 0.5);
            }
        }
    }

    if ($tokenHit && $currentScore > $bestMatch['score']) {
        $bestMatch = [
            "id" => $entry['id'],
            "score" => $currentScore,
            "percent" => 100 // We found the token
        ];
    }
}

// --- 7. ROUTING DECISION ---
if ($bestMatch['id'] === null) {
    // CACHE MISS: Trigger Gemini 3 (Teacher)
    $aiData = callGemini3($question, $apiKey);

    if ($aiData && isset($aiData['fact'])) {
        $id = "AI_" . time();
        $t = $conn->real_escape_string($aiData['token']);
        $c = $conn->real_escape_string($aiData['context']);
        $f = $conn->real_escape_string($aiData['fact']);

        // Save new distilled knowledge
        if ($conn->query("INSERT INTO pengetahuan (id, token, context, fact) VALUES ('$id', '$t', '$c', '$f')")) {
            refreshKnowledgeCache($conn); // Silent Sync
        }

        $reply = $aiData['fact'];
        $source = "Gemini 3 (New Knowledge)";
        $winnerId = $id;
    } else {
        $reply = "I'm still learning about that.";
        $source = "System Guardrail";
        $winnerId = "null";
    }
} else {
    // CACHE HIT: Fast Response
    foreach ($knowledge as $e) {
        if ($e['id'] === $bestMatch['id']) { $reply = $e['fact']; break; }
    }
    $source = "Local Cache (Distilled)";
    $winnerId = $bestMatch['id'];
}

// --- 8. FINAL OUTPUT ---
output:
echo json_encode([
    "reply" => $reply,
    "debug" => [
        "source" => $source,
        "id" => $winnerId
    ]
]);

// --- 9. GEMINI 3 FLASH THINKING ENGINE ---
function callGemini3($query, $key) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:streamGenerateContent?key=" . $key;
    $payload = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => "Distillation mode: Return JSON only."]]],
            ["role" => "model", "parts" => [["text" => "Ready."]]],
            ["role" => "user", "parts" => [["text" => $query . ". Format JSON: {'token': 'short_topic', 'context': 'key1, key2', 'fact': 'answer_max_25_words'}"]] ]
        ],
        "generationConfig" => ["thinkingConfig" => ["thinkingLevel" => "HIGH"]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $chunks = json_decode($res, true);
    curl_close($ch);

    $fullText = "";
    if (is_array($chunks)) {
        foreach ($chunks as $chunk) {
            if (isset($chunk['candidates'][0]['content']['parts'])) {
                foreach ($chunk['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) $fullText .= $part['text'];
                }
            }
        }
    }
    if (preg_match('/\{.*\}/s', $fullText, $m)) return json_decode($m[0], true);
    return null;
}

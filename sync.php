<?php
// sync.php - SILENT VERSION
function refreshKnowledgeCache($conn = null) {
    $cachePath = 'knowledge.json';

    if (!$conn) {
        $conn = new mysqli('localhost', '', '', '');
        $conn->set_charset("utf8mb4");
    }

    $result = $conn->query("SELECT * FROM pengetahuan");

    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "id"      => (string)$row['id'],
                "token"   => strtolower(trim($row['token'])),
                "context" => array_map('trim', explode(',', strtolower($row['context']))),
                "fact"    => $row['fact']
            ];
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($cachePath, $json, LOCK_EX) !== false) {
            chmod($cachePath, 0644);
            return true;
        }
    }
    return false;
}

// ONLY ECHO IF ACCESSED DIRECTLY VIA BROWSER WITH ?debug=1
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) && isset($_GET['debug'])) {
    if (refreshKnowledgeCache()) echo "Sync Successful.";
    else echo "Sync Failed.";
}
?>

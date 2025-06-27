<?php
//$confFile = __DIR__ . '/projects.conf';
require('projecttree.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ta emot inkommande JSON
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ogiltig JSON']);
        exit;
    }

    // Rensa "exists" om det finns med, så vi inte sparar det
    foreach ($data as &$project) {
        foreach ($project['files'] as &$file) {
            if (is_array($file) && isset($file['path'])) {
                $file = $file['path'];
            }
        }
    }
    unset($file, $project); // skydda mot referensläckor

    file_put_contents(ProjectTree::$confFile, serialize($data));
    echo json_encode(['status' => 'success']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists(ProjectTree::$confFile)) {
        $data = unserialize(file_get_contents(ProjectTree::$confFile));
    } else {
        $data = []; // tom lista om ingen fil finns
    }

    $basePath = realpath(__DIR__ . '/../'); // Rotmapp för projekten

    foreach ($data as &$project) {
        foreach ($project['files'] as $i => $file) {
            // Hämta filvägen om det är ett objekt
            $path = is_array($file) ? $file['path'] : $file;

            $fullPath = realpath($basePath . '/' . ltrim($path, './'));
            $exists = $fullPath && file_exists($fullPath);

            $project['files'][$i] = [
                'path' => $path,
                'exists' => $exists,
            ];
        }
    }
    unset($project);

    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(405); // Fel metod
echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
?>

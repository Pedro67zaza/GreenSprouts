<?php
// Include config file from outside web root
require_once '../../config/plantbook_config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get plant name from POST data
$input = json_decode(file_get_contents('php://input'), true);
$plantName = isset($input['plantName']) ? trim($input['plantName']) : '';

if (empty($plantName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Plant name is required']);
    exit;
}

try {
    // Step 1: Get scientific name from alias
    $scientificName = getScientificName($plantName);
    
    // Step 2: Get plant details
    $plantDetails = getPlantDetails($scientificName);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $plantDetails
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get scientific name from plant alias
 */
function getScientificName($alias) {
    $url = PLANTBOOK_API_BASE . '/plant/search?alias=' . urlencode($alias);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . PLANTBOOK_API_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Plant not found. Please try another name.');
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['results']) || count($data['results']) === 0) {
        throw new Exception('No plants found matching that name.');
    }
    
    // Return the scientific name (pid) of the first result
    return $data['results'][0]['pid'];
}

/**
 * Get plant details by scientific name
 */
function getPlantDetails($scientificName) {
    $url = PLANTBOOK_API_BASE . '/plant/detail/' . urlencode($scientificName) . '/';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Token ' . PLANTBOOK_API_TOKEN
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Unable to fetch plant details.');
    }
    
    return json_decode($response, true);
}
?>
<?php
// Script rapide pour vérifier les coordonnées des événements

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiUrl = env('API_URL');
$token = env('CLIENT_DEFAULT_ACCOUNT_TOKEN');

echo "=== VÉRIFICATION DES COORDONNÉES ===\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl . '/admin/app/events?noLimit=true');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

if (isset($data['data']['list'])) {
    $events = $data['data']['list'];
    echo "Nombre d'événements: " . count($events) . "\n\n";
    
    echo "Premières coordonnées (pour vérification):\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-30s | %-12s | %-12s | Position\n", "Nom", "Latitude", "Longitude");
    echo str_repeat('-', 80) . "\n";
    
    foreach (array_slice($events, 0, 10) as $event) {
        $name = substr($event['name'] ?? 'N/A', 0, 28);
        $lat = $event['map']['latitude'] ?? 'N/A';
        $lon = $event['map']['longitude'] ?? 'N/A';
        
        // Détection si les coordonnées sont dans l'océan
        $latNum = floatval($lat);
        $lonNum = floatval($lon);
        
        $position = "OK";
        // Bénin est environ entre 6-12° latitude et 0-4° longitude
        if ($latNum < 6 || $latNum > 13 || $lonNum < 0 || $lonNum > 4) {
            $position = "⚠️ HORS BÉNIN";
        }
        
        printf("%-30s | %12s | %12s | %s\n", $name, $lat, $lon, $position);
    }
    
    echo str_repeat('-', 80) . "\n\n";
    echo "Note: Le Bénin se situe approximativement entre:\n";
    echo "  Latitude: 6° - 12° Nord\n";
    echo "  Longitude: 0° - 4° Est\n";
    
} else {
    echo "❌ Erreur: " . ($data['message'] ?? 'Réponse invalide') . "\n";
}

<?php

$ch = curl_init('https://api.yopasshub.com/admin/app/events?noLimit=true');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjoiYmFzZS1hZG1pbiIsImlhdCI6MTc2NjA4NTIxOH0.T0-vqSb0fiovaB3Wi98mTfWth72g8nnH48fIy4ntuMg',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$data = json_decode($response, true);

echo "=== ÉVÉNEMENTS PROBLÉMATIQUES ===\n\n";

foreach($data['data']['list'] as $event) {
    $name = $event['name'];
    $map = $event['map'];
    
    // Chercher les événements avec coordonnées suspectes
    if (stripos($name, 'Bleu') !== false || stripos($name, 'Accra') !== false) {
        echo "Événement: {$name}\n";
        echo "Données map brutes:\n";
        echo json_encode($map, JSON_PRETTY_PRINT);
        echo "\n\n";
        echo "Adresse: {$event['adress']}\n";
        echo "--------------------\n\n";
    }
}

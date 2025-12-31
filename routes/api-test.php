<?php

/**
 * Script de test de l'API YoPassHub
 * 
 * Ce fichier permet de tester directement l'API pour voir ce qui est retourné
 * Accédez à ce fichier via: http://localhost:8000/test-api
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/test-api', function () {
    $apiUrl = env('API_URL');
    $token = env('CLIENT_DEFAULT_ACCOUNT_TOKEN');
    
    echo "<h1>🔍 Test de l'API YoPassHub</h1>";
    echo "<hr>";
    
    echo "<h2>1. Configuration</h2>";
    echo "<p><strong>API_URL:</strong> " . ($apiUrl ?? '❌ NON DÉFINIE') . "</p>";
    echo "<p><strong>Token présent:</strong> " . (!empty($token) ? '✅ OUI' : '❌ NON') . "</p>";
    echo "<hr>";
    
    if (empty($apiUrl) || empty($token)) {
        echo "<p style='color: red;'>❌ Configuration manquante dans le fichier .env</p>";
        return;
    }
    
    echo "<h2>2. Test de l'endpoint /admin/app/events</h2>";
    
    try {
        $url = $apiUrl . '/admin/app/events';
        echo "<p><strong>URL:</strong> {$url}</p>";
        echo "<p><strong>Paramètres:</strong> noLimit=true, append=description</p>";
        echo "<p>⏳ Envoi de la requête...</p>";
        
        $response = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])
            ->get($url, [
                'noLimit' => 'true',
                'append' => 'description'
            ]);
        
        echo "<p><strong>Code HTTP:</strong> " . $response->status() . "</p>";
        
        if ($response->successful()) {
            echo "<p style='color: green;'>✅ Requête réussie</p>";
            
            $data = $response->json();
            
            echo "<h3>Structure de la réponse:</h3>";
            echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
            echo "Clés principales: " . implode(', ', array_keys($data)) . "\n\n";
            
            if (isset($data['statut'])) {
                echo "statut: " . ($data['statut'] ? 'true ✅' : 'false ❌') . "\n";
            } else {
                echo "❌ Clé 'statut' manquante\n";
            }
            
            if (isset($data['message'])) {
                echo "message: " . $data['message'] . "\n";
            }
            
            if (isset($data['data'])) {
                echo "\nClés dans 'data': " . implode(', ', array_keys($data['data'])) . "\n";
                
                if (isset($data['data']['list'])) {
                    $count = count($data['data']['list']);
                    echo "\nNombre d'événements: {$count}\n";
                    
                    if ($count > 0) {
                        echo "\n--- Premier événement (exemple) ---\n";
                        $firstEvent = $data['data']['list'][0];
                        echo "ID: " . ($firstEvent['id'] ?? 'N/A') . "\n";
                        echo "Nom: " . ($firstEvent['name'] ?? 'N/A') . "\n";
                        echo "Description: " . substr($firstEvent['description'] ?? 'N/A', 0, 100) . "...\n";
                        echo "Adresse: " . ($firstEvent['adress'] ?? 'N/A') . "\n";
                        echo "Date début: " . ($firstEvent['date_from'] ?? 'N/A') . "\n";
                        echo "Date fin: " . ($firstEvent['date_to'] ?? 'N/A') . "\n";
                        
                        if (isset($firstEvent['map'])) {
                            echo "Coordonnées: lat=" . ($firstEvent['map']['latitude'] ?? 'N/A') 
                                . ", lon=" . ($firstEvent['map']['longitude'] ?? 'N/A') . "\n";
                        }
                        
                        if (isset($firstEvent['category_detail'])) {
                            echo "Catégorie: " . ($firstEvent['category_detail']['label'] ?? 'N/A') . "\n";
                        }
                        
                        if (isset($firstEvent['photos'])) {
                            echo "Photos: " . count($firstEvent['photos']) . " photo(s)\n";
                        }
                    }
                } else {
                    echo "❌ Clé 'data.list' manquante\n";
                    echo "Contenu de 'data': " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "❌ Clé 'data' manquante\n";
            }
            
            echo "</pre>";
            
            echo "<h3>Réponse JSON complète:</h3>";
            echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 500px; overflow: auto;'>";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "</pre>";
            
        } else {
            echo "<p style='color: red;'>❌ Erreur HTTP</p>";
            echo "<pre style='background: #fff0f0; padding: 15px; border-radius: 5px;'>";
            echo $response->body();
            echo "</pre>";
        }
        
    } catch (\Exception $e) {
        echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<hr>";
    echo "<h2>3. Test de l'endpoint /utils/events-categories</h2>";
    
    try {
        $url = $apiUrl . '/utils/events-categories';
        echo "<p><strong>URL:</strong> {$url}</p>";
        
        $response = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->get($url);
        
        echo "<p><strong>Code HTTP:</strong> " . $response->status() . "</p>";
        
        if ($response->successful()) {
            $data = $response->json();
            echo "<p style='color: green;'>✅ Requête réussie</p>";
            echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Erreur</p>";
            echo "<pre>" . $response->body() . "</pre>";
        }
        
    } catch (\Exception $e) {
        echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<p><em>Timestamp: " . date('Y-m-d H:i:s') . "</em></p>";
});

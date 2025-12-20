<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VodunDaysController extends Controller
{

    public function index(Request $request)
    {
        // Récupérer les paramètres de filtre et de recherche
        $category = $request->query('category');
        $search = $request->query('search');
        
        // Récupérer les événements depuis l'API avec les filtres
        $events = collect($this->fetchEventsFromApi($category, $search));
        
        // Récupérer les catégories pour les filtres
        $categories = collect($this->fetchCategoriesFromApi());

        return view('vodun-days', compact('events', 'categories'));
    }

    /**
     * Récupérer les événements depuis l'API YoPassHub (avec cache)
     */
    private function fetchEventsFromApi($category = null, $search = null)
    {
        // Créer une clé de cache unique selon les filtres
        $cacheKey = 'events_' . md5(($category ?? 'all') . '_' . ($search ?? 'none'));
        
        // Si pas de filtres spécifiques, utiliser le cache
        if (!$category && !$search) {
            return \Cache::remember('events_all', 900, function () {
                return $this->callEventsApi(null, null);
            });
        }
        
        // Avec filtres, cache plus court (5 minutes)
        return \Cache::remember($cacheKey, 300, function () use ($category, $search) {
            return $this->callEventsApi($category, $search);
        });
    }
    
    /**
     * Appel direct à l'API événements
     */
    private function callEventsApi($category = null, $search = null)
    {
        try {
            $apiUrl = env('API_URL');
            $token = env('CLIENT_DEFAULT_ACCOUNT_TOKEN');
            
            // Préparer les paramètres de la requête
            $params = [
                'noLimit' => 'true',
                'append' => 'description'
            ];
            
            // Ajouter le filtre de catégorie si présent
            if ($category) {
                $params['category'] = $category;
            }
            
            // Ajouter le filtre de recherche si présent
            if ($search) {
                $params['search'] = $search;
            }
            
            $fullUrl = $apiUrl . '/admin/app/events';
            
            // Appel API pour récupérer les événements selon la documentation
            // Note: L'API YoPassHub n'utilise pas le préfixe "Bearer"
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => $token,
                    'Accept' => 'application/json',
                ])
                ->get($fullUrl, $params);

            if ($response->successful()) {
                $apiData = $response->json();
                
                // Vérifier le statut de la réponse
                if (!isset($apiData['statut']) || !$apiData['statut']) {
                    Log::warning('API returned unsuccessful status');
                    return [];
                }
                
                // Vérifier la structure data.list
                if (!isset($apiData['data']['list'])) {
                    Log::error('Invalid API response structure');
                    return [];
                }
                
                // Mapper les événements de l'API vers le format de la carte
                return $this->mapApiEventsToMapFormat($apiData['data']['list']);
            } else {
                Log::error('API Events request failed: ' . $response->status());
                return [];
            }
        } catch (\Exception $e) {
            Log::error('API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapper les événements de l'API vers le format attendu par la carte
     */
    private function mapApiEventsToMapFormat($apiEvents)
    {
        $mappedEvents = [];
        
        foreach ($apiEvents as $event) {
            // Extraire et nettoyer les coordonnées depuis l'objet map
            $latRaw = $event['map']['latitude'] ?? '6.3611';
            $lonRaw = $event['map']['longitude'] ?? '2.0850';
            
            // Nettoyer: enlever caractères invalides, garder seulement chiffres, point, virgule, tiret
            $latClean = preg_replace('/[^0-9.,-]/', '', trim($latRaw));
            $lonClean = preg_replace('/[^0-9.,-]/', '', trim($lonRaw));
            
            // Remplacer virgules par points (format français → format standard)
            $latClean = str_replace(',', '.', $latClean);
            $lonClean = str_replace(',', '.', $lonClean);
            
            // Vérifier s'il y a plusieurs points (format corrompu comme "6,3578.")
            if (substr_count($latClean, '.') > 1 || substr_count($lonClean, '.') > 1) {
                Log::warning("⚠️ Format de coordonnées corrompu pour: {$event['name']}, lat={$latRaw}, lon={$lonRaw}");
                $latitude = 6.3611;
                $longitude = 2.0850;
            } else {
                // Convertir en float
                $latitude = floatval($latClean);
                $longitude = floatval($lonClean);
                
                // Valider les coordonnées (Bénin: lat 6-12°N, lon 0.7-3.9°E)
                // Coordonnées hors de ces limites = événement à l'étranger ou erreur
                if ($latitude < 6 || $latitude > 12.5 || $longitude < 0.5 || $longitude > 4) {
                    Log::warning("⚠️ Coordonnées hors Bénin pour: {$event['name']} ({$event['adress']}), lat={$latitude}, lon={$longitude}");
                    // Utiliser coordonnées d'Ouidah par défaut
                    $latitude = 6.3611;
                    $longitude = 2.0850;
                }
            }
            
            // Déterminer le type d'événement
            $type = $this->determineEventType($event);
            $icon = $this->getEventIcon($type);
            $date = $this->formatEventDate($event['date_from'] ?? null);
            $time = $this->formatEventTime($event);
            $image = !empty($event['photos']) ? $event['photos'][0] : $this->getDefaultImage($type);
            
            // Déterminer le statut de l'événement (en cours, démarre bientôt, passé, futur)
            $status = $this->determineEventStatus($event['date_from'] ?? null, $event['date_to'] ?? null);
            
            // Vérifier si c'est un événement VodunDays
            $isVodunDays = str_contains(strtolower($event['category_detail']['tag'] ?? ''), 'vodundays') 
                        || str_contains(strtolower($event['name'] ?? ''), 'vodundays');
            
            $mappedEvents[] = [
                'id' => $event['id'] ?? uniqid('evt_'),
                'name' => $event['name'] ?? 'Événement',
                'location' => $event['adress'] ?? 'Ouidah',
                'coordinates' => [$longitude, $latitude], // Mapbox: [longitude, latitude]
                'type' => $type,
                'category' => $event['category_detail']['label'] ?? 'Général',
                'description' => $event['description'] ?? 'Découvrez cet événement exceptionnel.',
                'time' => $time,
                'date' => $date,
                'icon' => $icon,
                'image' => $image,
                'status' => $status,
                'isVodunDays' => $isVodunDays,
                'date_from' => $event['date_from'] ?? null,
                'date_to' => $event['date_to'] ?? null,
            ];
        }
        
        // Ajouter des événements fictifs pour les tests
        $mappedEvents = array_merge($mappedEvents, $this->getFakeTestEvents());
        
        return $mappedEvents;
    }
    
    /**
     * Déterminer le statut d'un événement
     */
    private function determineEventStatus($dateFrom, $dateTo)
    {
        if (!$dateFrom) return 'upcoming';
        
        try {
            $now = new \DateTime();
            $start = new \DateTime($dateFrom);
            $end = $dateTo ? new \DateTime($dateTo) : clone $start;
            
            // En cours
            if ($now >= $start && $now <= $end) {
                return 'ongoing';
            }
            
            // Démarre bientôt (dans les 2 heures)
            $diffInHours = ($start->getTimestamp() - $now->getTimestamp()) / 3600;
            if ($diffInHours > 0 && $diffInHours <= 2) {
                return 'starting-soon';
            }
            
            // Passé
            if ($now > $end) {
                return 'past';
            }
            
            // Futur
            return 'upcoming';
        } catch (\Exception $e) {
            return 'upcoming';
        }
    }
    
    /**
     * Événements fictifs pour tester les fonctionnalités
     */
    private function getFakeTestEvents()
    {
        $now = new \DateTime();
        $soon = clone $now;
        $soon->modify('+1 hour');
        $ongoing = clone $now;
        $ongoing->modify('-30 minutes');
        
        return [
            [
                'id' => 'fake-vodundays-1',
                'name' => '🎭 Festival VodunDays 2025',
                'location' => 'Temple des Pythons, Ouidah',
                'coordinates' => [2.0895, 6.3625],
                'type' => 'vodur',
                'category' => 'VodunDays',
                'description' => 'Grand festival culturel célébrant les traditions Vodun. Cérémonie spéciale avec danse traditionnelle et bénédiction.',
                'time' => $ongoing->format('H:i') . ' - 22:00',
                'date' => $ongoing->format('d F Y'),
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=400&h=400&fit=crop',
                'status' => 'ongoing',
                'isVodunDays' => true,
                'date_from' => $ongoing->format('Y-m-d H:i:s'),
                'date_to' => $now->modify('+3 hours')->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 'fake-vodundays-2',
                'name' => '🌟 Cérémonie VodunDays Spéciale',
                'location' => 'Porte du Non-Retour, Ouidah',
                'coordinates' => [2.0845, 6.3590],
                'type' => 'vodur',
                'category' => 'VodunDays',
                'description' => 'Cérémonie commémorative à la Porte du Non-Retour.',
                'time' => $soon->format('H:i') . ' - 19:00',
                'date' => $soon->format('d F Y'),
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?w=400&h=400&fit=crop',
                'status' => 'starting-soon',
                'isVodunDays' => true,
                'date_from' => $soon->format('Y-m-d H:i:s'),
                'date_to' => $soon->modify('+2 hours')->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 'fake-concert-1',
                'name' => '🎵 Concert Live - Angelique Kidjo',
                'location' => 'Stade de l\'Amitié, Cotonou',
                'coordinates' => [2.4285, 6.3650],
                'type' => 'concert',
                'category' => 'Divertissement',
                'description' => 'Concert exceptionnel de la star internationale Angelique Kidjo.',
                'time' => $soon->modify('+30 minutes')->format('H:i') . ' - 23:00',
                'date' => $soon->format('d F Y'),
                'icon' => '🎵',
                'image' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=400&h=400&fit=crop',
                'status' => 'starting-soon',
                'isVodunDays' => false,
                'date_from' => $soon->format('Y-m-d H:i:s'),
                'date_to' => $soon->modify('+4 hours')->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Déterminer le type d'événement pour le filtrage
     */
    private function determineEventType($event)
    {
        // Utiliser category_detail.label ou category_detail.tag
        $categoryLabel = strtolower($event['category_detail']['label'] ?? '');
        $categoryTag = strtolower($event['category_detail']['tag'] ?? '');
        $searchString = $categoryLabel . ' ' . $categoryTag;
        
        if (str_contains($searchString, 'vodun') || str_contains($searchString, 'temple') || str_contains($searchString, 'spirituel')) {
            return 'vodur';
        } elseif (str_contains($searchString, 'concert') || str_contains($searchString, 'musique') || str_contains($searchString, 'music')) {
            return 'concert';
        } elseif (str_contains($searchString, 'stand') || str_contains($searchString, 'marché') || str_contains($searchString, 'boutique')) {
            return 'stand';
        } else {
            return 'activite';
        }
    }

    /**
     * Obtenir l'icône selon le type
     */
    private function getEventIcon($type)
    {
        return match($type) {
            'vodur' => '🏛️',
            'concert' => '🎵',
            'stand' => '🛍️',
            'activite' => '🎨',
            default => '📍',
        };
    }

    /**
     * Formater la date de l'événement
     */
    private function formatEventDate($dateString)
    {
        if (!$dateString) {
            return date('d F Y');
        }
        
        try {
            $date = new \DateTime($dateString);
            
            // Noms des mois en français
            $mois = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];
            
            $jour = $date->format('d');
            $numeroMois = (int)$date->format('m');
            $annee = $date->format('Y');
            
            return $jour . ' ' . $mois[$numeroMois] . ' ' . $annee;
        } catch (\Exception $e) {
            return date('d F Y', strtotime($dateString));
        }
    }

    /**
     * Formater l'horaire de l'événement
     */
    private function formatEventTime($event)
    {
        // Extraire l'heure de date_from et date_to
        $startTime = '10:00';
        $endTime = '18:00';
        
        if (!empty($event['date_from'])) {
            try {
                $startDate = new \DateTime($event['date_from']);
                $startTime = $startDate->format('H:i');
            } catch (\Exception $e) {
                // Garder la valeur par défaut
            }
        }
        
        if (!empty($event['date_to'])) {
            try {
                $endDate = new \DateTime($event['date_to']);
                $endTime = $endDate->format('H:i');
            } catch (\Exception $e) {
                // Garder la valeur par défaut
            }
        }
        
        return $startTime . ' - ' . $endTime;
    }

    /**
     * Obtenir une image par défaut selon le type
     */
    private function getDefaultImage($type)
    {
        return match($type) {
            'vodur' => 'https://images.unsplash.com/photo-1548013146-72479768bada?w=400&h=400&fit=crop',
            'concert' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=400&h=400&fit=crop',
            'stand' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=400&fit=crop',
            'activite' => 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?w=400&h=400&fit=crop',
            default => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&h=400&fit=crop',
        };
    }

    /**
     * Récupérer les catégories d'événements depuis l'API (avec cache)
     */
    private function fetchCategoriesFromApi()
    {
        // Cache des catégories pendant 1 heure (3600 secondes)
        return \Cache::remember('event_categories', 3600, function () {
            return $this->callCategoriesApi();
        });
    }
    
    /**
     * Appel direct à l'API catégories
     */
    private function callCategoriesApi()
    {
        try {
            $apiUrl = env('API_URL');
            
            // Appel API pour récupérer les catégories
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl . '/utils/events-categories');

            if ($response->successful()) {
                $apiData = $response->json();
                
                // Vérifier le statut de la réponse
                if (!isset($apiData['statut']) || !$apiData['statut']) {
                    Log::warning('API returned unsuccessful status for categories', ['response' => $apiData]);
                    return $this->getDefaultCategories();
                }
                
                // Retourner les catégories de l'API
                return $apiData['data'] ?? $this->getDefaultCategories();
            } else {
                Log::warning('API Categories request failed', ['status' => $response->status()]);
                return $this->getDefaultCategories();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching categories from API', ['error' => $e->getMessage()]);
            return $this->getDefaultCategories();
        }
    }

    /**
     * Catégories par défaut en cas d'erreur API
     */
    private function getDefaultCategories()
    {
        return [
            [
                'tag' => 'vodur',
                'label' => 'Vodur',
                'illustration' => '🏛️'
            ],
            [
                'tag' => 'concert',
                'label' => 'Concert',
                'illustration' => '🎵'
            ],
            [
                'tag' => 'stand',
                'label' => 'Stand',
                'illustration' => '🛍️'
            ],
            [
                'tag' => 'activite',
                'label' => 'Activité',
                'illustration' => '🎨'
            ],
        ];
    }
}


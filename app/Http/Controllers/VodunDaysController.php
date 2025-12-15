<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VodunDaysController extends Controller
{

    public function index()
    {
        // Récupérer les événements depuis l'API
        $events = $this->fetchEventsFromApi();
        
        return view('vodun-days', compact('events'));
    }

    /**
     * Afficher la carte avec le composant réutilisable
     */
    public function simple()
    {
        // Récupérer les événements depuis l'API
        $events = $this->fetchEventsFromApi();

        return view('vodun-days-simple', compact('events'));
    }

    /**
     * Récupérer les événements depuis l'API YoPassHub
     */
    private function fetchEventsFromApi()
    {
        try {
            $apiUrl = env('API_URL');
            $token = env('CLIENT_DEFAULT_ACCOUNT_TOKEN');
            
            // Appel API pour récupérer les événements selon la documentation
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl . '/clients/app/events', [
                    'noLimit' => 'true',
                    'append' => 'description'
                ]);

            if ($response->successful()) {
                $apiData = $response->json();
                
                // Vérifier le statut de la réponse
                if (!isset($apiData['statut']) || !$apiData['statut']) {
                    Log::warning('API returned unsuccessful status', ['response' => $apiData]);
                    return $this->getFallbackEvents();
                }
                
                // Mapper les événements de l'API vers le format de la carte
                // Structure de l'API: data.list contient la liste des événements
                return $this->mapApiEventsToMapFormat($apiData['data']['list'] ?? []);
            } else {
                Log::warning('API Events request failed', ['status' => $response->status()]);
                return $this->getFallbackEvents();
            }
        } catch (\Exception $e) {
            Log::error('Error fetching events from API', ['error' => $e->getMessage()]);
            return $this->getFallbackEvents();
        }
    }

    /**
     * Mapper les événements de l'API vers le format attendu par la carte
     */
    private function mapApiEventsToMapFormat($apiEvents)
    {
        $mappedEvents = [];
        
        foreach ($apiEvents as $event) {
            // Extraire les coordonnées depuis l'objet map
            $latitude = $event['map']['latitude'] ?? 6.3611;
            $longitude = $event['map']['longitude'] ?? 2.0850;
            
            // Déterminer le type d'événement à partir de category_detail
            $type = $this->determineEventType($event);
            
            // Déterminer l'icône
            $icon = $this->getEventIcon($type);
            
            // Formater la date (date_from au lieu de date_event)
            $date = $this->formatEventDate($event['date_from'] ?? null);
            
            // Formater l'horaire (extraire de date_from et date_to)
            $time = $this->formatEventTime($event);
            
            // Image de l'événement - prendre la première photo
            $image = !empty($event['photos']) ? $event['photos'][0] : $this->getDefaultImage($type);
            
            $mappedEvents[] = [
                'id' => $event['id'] ?? uniqid('evt_'),
                'name' => $event['name'] ?? 'Événement',
                'location' => $event['adress'] ?? 'Ouidah',
                'coordinates' => [(float)$longitude, (float)$latitude], // [longitude, latitude]
                'type' => $type,
                'category' => $event['category_detail']['label'] ?? 'Général',
                'description' => $event['description'] ?? 'Découvrez cet événement exceptionnel.',
                'time' => $time,
                'date' => $date,
                'icon' => $icon,
                'image' => $image,
            ];
        }
        
        return $mappedEvents;
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
            $formatter = new \IntlDateFormatter(
                'fr_FR',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::NONE
            );
            return $formatter->format($date);
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
     * Événements de secours en cas d'erreur API
     */
    private function getFallbackEvents()
    {
        $now = new \DateTime();
        $currentTime = $now->format('H:i');
        $endTime = $now->modify('+2 hours')->format('H:i');
        $today = (new \DateTime())->format('d F Y');
        
        // Données des événements de secours
        $events = [
            // Événement en cours (DEMO) - Clignotant
            [
                'id' => 0,
                'name' => '🔴 Concert LIVE - EN COURS',
                'location' => 'Scène Principale Vodun Days',
                'coordinates' => [2.0850, 6.3611],
                'type' => 'concert',
                'category' => 'music',
                'description' => 'Concert exceptionnel en direct ! Rejoignez-nous maintenant pour une expérience musicale unique.',
                'time' => $currentTime . ' - ' . $endTime,
                'date' => $today,
                'icon' => '🎵',
                'image' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=400&h=400&fit=crop'
            ],
            // Temples Vodur à Ouidah
            [
                'id' => 1,
                'name' => 'Temple Vodur',
                'location' => 'Ouidah Centre',
                'coordinates' => [2.0850, 6.3611],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Temple traditionnel Vodur - Visite guidée et découverte des pratiques ancestrales avec un guide expérimenté. Découvrez l\'histoire fascinante et les traditions séculaires.',
                'time' => '10:00 - 12:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?w=400&h=400&fit=crop'
            ],
            [
                'id' => 2,
                'name' => 'Temple Vodur',
                'location' => 'Route des Esclaves',
                'coordinates' => [2.0900, 6.3550],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Centre spirituel Vodur - Cérémonie d\'accueil et rituels traditionnels. Participez à une expérience spirituelle authentique et enrichissante.',
                'time' => '09:00 - 11:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=400&h=400&fit=crop'
            ],
            [
                'id' => 3,
                'name' => 'Sanctuaire Vodur',
                'location' => 'Quartier Zounguè',
                'coordinates' => [2.0800, 6.3650],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Sanctuaire sacré avec cérémonies rituelles quotidiennes. Un lieu de recueillement et de connexion avec les traditions ancestrales.',
                'time' => '13:00 - 16:00',
                'date' => '10 Janvier 2026',
                'icon' => '⛩️',
                'image' => 'https://images.unsplash.com/photo-1555400038-63f5ba517a47?w=400&h=400&fit=crop'
            ],
            [
                'id' => 4,
                'name' => 'Temple Vodur',
                'location' => 'Djègbadji',
                'coordinates' => [2.0950, 6.3500],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Temple historique - Architecture traditionnelle et artisanat',
                'time' => '08:00 - 14:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?w=400&h=400&fit=crop'
            ],
            [
                'id' => 5,
                'name' => 'Temple Vodur',
                'location' => 'Pahou',
                'coordinates' => [2.0750, 6.3700],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Centre culturel Vodur - Expositions et conférences',
                'time' => '10:00 - 17:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=400&h=400&fit=crop'
            ],
            
            // Concerts et Spectacles à Ouidah
            [
                'id' => 6,
                'name' => 'Concert Traditionnel',
                'location' => 'Place du Marché',
                'coordinates' => [2.0850, 6.3580],
                'type' => 'concert',
                'category' => 'event',
                'description' => 'Concert de musique traditionnelle béninoise avec percussions et danses',
                'time' => '19:00 - 23:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎵',
                'image' => 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=400&h=400&fit=crop'
            ],
            [
                'id' => 7,
                'name' => 'Spectacle de Danse',
                'location' => 'Esplanade du Fort',
                'coordinates' => [2.0880, 6.3620],
                'type' => 'concert',
                'category' => 'event',
                'description' => 'Performance de danse Vodur avec costumes traditionnels',
                'time' => '20:00 - 22:00',
                'date' => '10 Janvier 2026',
                'icon' => '💃',
                'image' => 'https://images.unsplash.com/photo-1504609773096-104ff2c73ba4?w=400&h=400&fit=crop'
            ],
            [
                'id' => 8,
                'name' => 'Festival Musical',
                'location' => 'Place des Martyrs',
                'coordinates' => [2.0820, 6.3640],
                'type' => 'concert',
                'category' => 'event',
                'description' => 'Grand festival musical avec plusieurs artistes locaux',
                'time' => '18:00 - 00:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎸',
                'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=400&fit=crop'
            ],
            
            // Stands et Marchés à Ouidah
            [
                'id' => 9,
                'name' => 'Stand Artisanat',
                'location' => 'Marché Central',
                'coordinates' => [2.0830, 6.3600],
                'type' => 'stand',
                'category' => 'market',
                'description' => 'Stand d\'artisanat local - Sculptures, tissus et bijoux traditionnels',
                'time' => '08:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎨',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=400&fit=crop'
            ],
            [
                'id' => 10,
                'name' => 'Stand Gastronomie',
                'location' => 'Avenue Guezo',
                'coordinates' => [2.0870, 6.3590],
                'type' => 'stand',
                'category' => 'market',
                'description' => 'Découverte de la cuisine béninoise traditionnelle',
                'time' => '09:00 - 17:00',
                'date' => '10 Janvier 2026',
                'icon' => '🍲',
                'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=400&fit=crop'
            ],
            [
                'id' => 11,
                'name' => 'Marché Traditionnel',
                'location' => 'Quartier Ahouandji',
                'coordinates' => [2.0920, 6.3630],
                'type' => 'stand',
                'category' => 'market',
                'description' => 'Grand marché avec produits locaux et artisanat',
                'time' => '07:00 - 19:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏪',
                'image' => 'https://images.unsplash.com/photo-1533900298318-6b8da08a523e?w=400&h=400&fit=crop'
            ],
            [
                'id' => 12,
                'name' => 'Stand Textile',
                'location' => 'Rue Tokplonou',
                'coordinates' => [2.0790, 6.3570],
                'type' => 'stand',
                'category' => 'market',
                'description' => 'Tissus traditionnels béninois et confection sur mesure',
                'time' => '08:00 - 17:00',
                'date' => '10 Janvier 2026',
                'icon' => '👗',
                'image' => 'https://images.unsplash.com/photo-1582639510494-c80b5de9f148?w=400&h=400&fit=crop'
            ],
            
            // Activités et Ateliers à Ouidah
            [
                'id' => 13,
                'name' => 'Atelier Percussion',
                'location' => 'Centre Culturel',
                'coordinates' => [2.0860, 6.3670],
                'type' => 'activite',
                'category' => 'workshop',
                'description' => 'Initiation aux percussions traditionnelles - Djembé et tam-tam',
                'time' => '14:00 - 17:00',
                'date' => '10 Janvier 2026',
                'icon' => '🥁',
                'image' => 'https://images.unsplash.com/photo-1519892300165-cb5542fb47c7?w=400&h=400&fit=crop'
            ],
            [
                'id' => 14,
                'name' => 'Atelier Danse',
                'location' => 'Salle Polyvalente',
                'coordinates' => [2.0910, 6.3560],
                'type' => 'activite',
                'category' => 'workshop',
                'description' => 'Cours de danse traditionnelle pour tous les âges',
                'time' => '11:00 - 13:00',
                'date' => '10 Janvier 2026',
                'icon' => '💃',
                'image' => 'https://images.unsplash.com/photo-1508700929628-666bc8bd84ea?w=400&h=400&fit=crop'
            ],
            [
                'id' => 15,
                'name' => 'Atelier Sculpture',
                'location' => 'Maison des Arts',
                'coordinates' => [2.0770, 6.3610],
                'type' => 'activite',
                'category' => 'workshop',
                'description' => 'Création de sculptures traditionnelles avec un artisan local',
                'time' => '10:00 - 16:00',
                'date' => '10 Janvier 2026',
                'icon' => '🗿',
                'image' => 'https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?w=400&h=400&fit=crop'
            ],
            [
                'id' => 16,
                'name' => 'Activité Enfants',
                'location' => 'Jardin Public',
                'coordinates' => [2.0840, 6.3530],
                'type' => 'activite',
                'category' => 'workshop',
                'description' => 'Activités ludiques et éducatives sur la culture Vodur pour enfants',
                'time' => '09:00 - 12:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎨',
                'image' => 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?w=400&h=400&fit=crop'
            ],
            [
                'id' => 17,
                'name' => 'Atelier Cuisine',
                'location' => 'Centre Formation',
                'coordinates' => [2.0800, 6.3680],
                'type' => 'activite',
                'category' => 'workshop',
                'description' => 'Initiation à la cuisine béninoise traditionnelle',
                'time' => '15:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '👨‍🍳',
                'image' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=400&h=400&fit=crop'
            ],
            
            // Événements spéciaux à Ouidah
            [
                'id' => 18,
                'name' => 'Exposition Photo',
                'location' => 'Galerie d\'Art',
                'coordinates' => [2.0930, 6.3580],
                'type' => 'stand',
                'category' => 'event',
                'description' => 'Exposition photographique sur le patrimoine Vodur',
                'time' => '10:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '📸',
                'image' => 'https://images.unsplash.com/photo-1531058020387-3be344556be6?w=400&h=400&fit=crop'
            ],
            [
                'id' => 19,
                'name' => 'Conférence Culturelle',
                'location' => 'Musée d\'Histoire',
                'coordinates' => [2.0890, 6.3540],
                'type' => 'activite',
                'category' => 'event',
                'description' => 'Conférence sur l\'histoire et l\'importance du Vodur',
                'time' => '16:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎓',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=400&h=400&fit=crop'
            ],
        ];

        return [
            // Temples Vodur à Ouidah
            [
                'id' => 1,
                'name' => 'Temple Vodur',
                'location' => 'Ouidah Centre',
                'coordinates' => [2.0850, 6.3611],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Temple traditionnel Vodur - Visite guidée et découverte des pratiques ancestrales avec un guide expérimenté. Découvrez l\'histoire fascinante et les traditions séculaires.',
                'time' => '10:00 - 12:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?w=400&h=400&fit=crop'
            ],
            [
                'id' => 2,
                'name' => 'Temple Vodur',
                'location' => 'Route des Esclaves',
                'coordinates' => [2.0900, 6.3550],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Centre spirituel Vodur - Cérémonie d\'accueil et rituels traditionnels. Participez à une expérience spirituelle authentique et enrichissante.',
                'time' => '09:00 - 11:00',
                'date' => '10 Janvier 2026',
                'icon' => '🏛️',
                'image' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=400&h=400&fit=crop'
            ],
            [
                'id' => 3,
                'name' => 'Sanctuaire Vodur',
                'location' => 'Quartier Zounguè',
                'coordinates' => [2.0800, 6.3650],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Sanctuaire sacré avec cérémonies rituelles quotidiennes. Un lieu de recueillement et de connexion avec les traditions ancestrales.',
                'time' => '13:00 - 16:00',
                'date' => '10 Janvier 2026',
                'icon' => '⛩️',
                'image' => 'https://images.unsplash.com/photo-1590736969955-71cc94901144?w=400&h=400&fit=crop'
            ],
            [
                'id' => 4,
                'name' => 'Place des Cérémonies',
                'location' => 'Place Chacha',
                'coordinates' => [2.0875, 6.3600],
                'type' => 'vodur',
                'category' => 'temple',
                'description' => 'Lieu de rassemblement pour les grandes cérémonies Vodur avec danses traditionnelles et musique. Assistez à un spectacle culturel unique.',
                'time' => '14:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎭',
                'image' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=400&h=400&fit=crop'
            ],

            // Concerts et événements musicaux
            [
                'id' => 5,
                'name' => 'Concert Afrobeat',
                'location' => 'Scène Principale',
                'coordinates' => [2.0825, 6.3625],
                'type' => 'concert',
                'category' => 'music',
                'description' => 'Concert exceptionnel d\'afrobeat avec des artistes renommés. Vivez une soirée musicale inoubliable sous les étoiles.',
                'time' => '20:00 - 23:30',
                'date' => '10 Janvier 2026',
                'icon' => '🎵',
                'image' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=400&h=400&fit=crop'
            ],
            [
                'id' => 6,
                'name' => 'Jazz Fusion',
                'location' => 'Jardin Musical',
                'coordinates' => [2.0890, 6.3575],
                'type' => 'concert',
                'category' => 'music',
                'description' => 'Soirée jazz fusion avec des musiciens internationaux dans un cadre intime. Découvrez des sonorités innovantes.',
                'time' => '19:00 - 22:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎷',
                'image' => 'https://images.unsplash.com/photo-1415201364774-f6f0bb35f28f?w=400&h=400&fit=crop'
            ],
            [
                'id' => 7,
                'name' => 'Percussions Traditionnelles',
                'location' => 'Esplanade',
                'coordinates' => [2.0810, 6.3590],
                'type' => 'concert',
                'category' => 'music',
                'description' => 'Spectacle de percussions traditionnelles avec les meilleurs batteurs de la région. Une performance énergique et captivante.',
                'time' => '17:00 - 19:00',
                'date' => '10 Janvier 2026',
                'icon' => '🥁',
                'image' => 'https://images.unsplash.com/photo-1519683384663-cef9e43ea4f8?w=400&h=400&fit=crop'
            ],
            [
                'id' => 8,
                'name' => 'DJ Set Électronique',
                'location' => 'Club Nocturne',
                'coordinates' => [2.0860, 6.3640],
                'type' => 'concert',
                'category' => 'music',
                'description' => 'Soirée électronique avec les meilleurs DJs africains. Dansez jusqu\'au bout de la nuit sur des rythmes modernes.',
                'time' => '22:00 - 04:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎧',
                'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400&h=400&fit=crop'
            ],

            // Stands et marchés
            [
                'id' => 9,
                'name' => 'Marché Artisanal',
                'location' => 'Place du Marché',
                'coordinates' => [2.0840, 6.3605],
                'type' => 'stand',
                'category' => 'market',
                'description' => 'Découvrez l\'artisanat local : sculptures, tissus, bijoux traditionnels. Soutenez les artisans locaux.',
                'time' => '08:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '🛍️',
                'image' => 'https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?w=400&h=400&fit=crop'
            ],
            [
                'id' => 10,
                'name' => 'Stand Gastronomique',
                'location' => 'Avenue Principale',
                'coordinates' => [2.0870, 6.3620],
                'type' => 'stand',
                'category' => 'food',
                'description' => 'Savourez les spécialités culinaires béninoises préparées par des chefs locaux. Un voyage gustatif authentique.',
                'time' => '11:00 - 22:00',
                'date' => '10 Janvier 2026',
                'icon' => '🍲',
                'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=400&fit=crop'
            ],
            [
                'id' => 11,
                'name' => 'Boutique Souvenir',
                'location' => 'Centre Commercial',
                'coordinates' => [2.0820, 6.3630],
                'type' => 'stand',
                'category' => 'shop',
                'description' => 'Achetez des souvenirs uniques du Vodun Days : t-shirts, posters, livres. Ramenez un morceau de culture.',
                'time' => '09:00 - 20:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎁',
                'image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=400&fit=crop'
            ],
            [
                'id' => 12,
                'name' => 'Bar à Cocktails',
                'location' => 'Terrasse Panoramique',
                'coordinates' => [2.0880, 6.3595],
                'type' => 'stand',
                'category' => 'bar',
                'description' => 'Détendez-vous avec des cocktails exotiques sur une terrasse avec vue panoramique. L\'endroit parfait pour se rafraîchir.',
                'time' => '15:00 - 01:00',
                'date' => '10 Janvier 2026',
                'icon' => '🍹',
                'image' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=400&h=400&fit=crop'
            ],

            // Activités et animations
            [
                'id' => 13,
                'name' => 'Atelier de Danse',
                'location' => 'Studio de Danse',
                'coordinates' => [2.0835, 6.3615],
                'type' => 'activite',
                'category' => 'activity',
                'description' => 'Apprenez les danses traditionnelles béninoises avec des instructeurs professionnels. Pour tous les niveaux.',
                'time' => '10:00 - 12:00',
                'date' => '10 Janvier 2026',
                'icon' => '💃',
                'image' => 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?w=400&h=400&fit=crop'
            ],
            [
                'id' => 14,
                'name' => 'Exposition d\'Art',
                'location' => 'Galerie Culturelle',
                'coordinates' => [2.0855, 6.3585],
                'type' => 'activite',
                'category' => 'art',
                'description' => 'Exposition d\'art contemporain africain avec des œuvres d\'artistes locaux et internationaux. Laissez-vous inspirer.',
                'time' => '09:00 - 19:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎨',
                'image' => 'https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?w=400&h=400&fit=crop'
            ],
            [
                'id' => 15,
                'name' => 'Cinéma en Plein Air',
                'location' => 'Parc Central',
                'coordinates' => [2.0815, 6.3635],
                'type' => 'activite',
                'category' => 'cinema',
                'description' => 'Projection de films africains sous les étoiles. Apportez votre couverture et profitez du spectacle.',
                'time' => '20:00 - 23:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎬',
                'image' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=400&h=400&fit=crop'
            ],
            [
                'id' => 16,
                'name' => 'Yoga au Lever du Soleil',
                'location' => 'Plage de Ouidah',
                'coordinates' => [2.0845, 6.3560],
                'type' => 'activite',
                'category' => 'wellness',
                'description' => 'Session de yoga matinale face à l\'océan pour bien commencer la journée. Tous niveaux bienvenus.',
                'time' => '06:00 - 07:30',
                'date' => '10 Janvier 2026',
                'icon' => '🧘',
                'image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=400&h=400&fit=crop'
            ],
            [
                'id' => 17,
                'name' => 'Atelier de Batik',
                'location' => 'Maison des Artisans',
                'coordinates' => [2.0865, 6.3610],
                'type' => 'activite',
                'category' => 'craft',
                'description' => 'Apprenez la technique traditionnelle du batik et créez votre propre tissu. Repartez avec votre création.',
                'time' => '14:00 - 17:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎨',
                'image' => 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b?w=400&h=400&fit=crop'
            ],
            [
                'id' => 18,
                'name' => 'Conte pour Enfants',
                'location' => 'Bibliothèque Municipale',
                'coordinates' => [2.0830, 6.3645],
                'type' => 'activite',
                'category' => 'kids',
                'description' => 'Histoires traditionnelles racontées par des conteurs passionnés. Parfait pour les familles.',
                'time' => '15:00 - 16:30',
                'date' => '10 Janvier 2026',
                'icon' => '📚',
                'image' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=400&fit=crop'
            ],
            [
                'id' => 19,
                'name' => 'Conférence Culturelle',
                'location' => 'Auditorium',
                'coordinates' => [2.0850, 6.3570],
                'type' => 'activite',
                'category' => 'conference',
                'description' => 'Conférence sur l\'histoire et l\'importance du Vodun dans la culture béninoise avec des experts.',
                'time' => '16:00 - 18:00',
                'date' => '10 Janvier 2026',
                'icon' => '🎓',
                'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=400&h=400&fit=crop'
            ],
        ];
    }
}


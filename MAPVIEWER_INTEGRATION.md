# 🗺️ MapViewer Component - Guide d'Intégration

## 📋 Table des Matières
- [Vue d'ensemble](#vue-densemble)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation Basique](#utilisation-basique)
- [API du Composant](#api-du-composant)
- [Exemples d'Utilisation](#exemples-dutilisation)
- [Structure des Données](#structure-des-données)
- [Personnalisation](#personnalisation)
- [Déploiement en Production](#déploiement-en-production)
- [Dépannage](#dépannage)

---

## Vue d'ensemble

Le composant **MapViewer** est un composant Blade réutilisable qui affiche une carte interactive Mapbox GL JS avec marqueurs personnalisés, recherche, filtres, tooltips et navigation GPS.

### Fonctionnalités incluses :
- ✅ Carte interactive Mapbox avec zoom/pan
- ✅ Marqueurs personnalisés avec images circulaires
- ✅ Tooltip au survol prolongé (800ms)
- ✅ Panneau de détails avec informations complètes
- ✅ Navigation GPS avec calcul d'itinéraire
- ✅ Filtres par catégorie
- ✅ Barre de recherche en temps réel
- ✅ Adaptation automatique au zoom
- ✅ Responsive (mobile, tablette, desktop)
- ✅ Bâtiments 3D au zoom élevé

---

## Prérequis

### Environnement Technique
- **Laravel** : ≥ 11.x
- **PHP** : ≥ 8.2
- **Mapbox GL JS** : v3.0.1 (chargé via CDN)
- **Navigateur** : Moderne avec support ES6+

### Compte Mapbox
Vous devez avoir un compte Mapbox et un token d'accès :
1. Créez un compte sur [mapbox.com](https://www.mapbox.com/)
2. Générez un token d'accès dans votre dashboard
3. Le token doit avoir les permissions : `styles:tiles`, `styles:read`, `fonts:read`, `datasets:read`

---

## Installation

### Étape 1 : Copier les fichiers du composant

Copiez les fichiers suivants dans votre projet Laravel :

```bash
# Structure des fichiers
app/View/Components/MapViewer.php
resources/views/components/map-viewer.blade.php
```

#### Fichier 1 : `app/View/Components/MapViewer.php`
```php
<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class MapViewer extends Component
{
    public array $events;
    public array $center;
    public int $zoom;
    public string $style;
    public bool $showInfo;
    public string $infoTitle;
    public string $infoDescription;
    public bool $showFilters;
    public array $filters;
    public bool $showSearch;
    public string $mapId;

    public function __construct(
        array $events = [],
        array $center = [2.0850, 6.3611],
        int $zoom = 14,
        string $style = 'mapbox://styles/mapbox/streets-v12',
        bool $showInfo = true,
        string $infoTitle = 'Vodun Days',
        string $infoDescription = 'Découvrez les événements et lieux spirituels',
        bool $showFilters = true,
        array $filters = [],
        bool $showSearch = true,
        string $mapId = 'map'
    ) {
        $this->events = $events;
        $this->center = $center;
        $this->zoom = $zoom;
        $this->style = $style;
        $this->showInfo = $showInfo;
        $this->infoTitle = $infoTitle;
        $this->infoDescription = $infoDescription;
        $this->showFilters = $showFilters;
        $this->showSearch = $showSearch;
        $this->mapId = $mapId;
        
        $this->filters = empty($filters) ? [
            ['label' => 'Tout', 'value' => 'all', 'icon' => '⭐'],
            ['label' => 'Vodundays', 'value' => 'vodur', 'icon' => '🏛️'],
            ['label' => 'Événements', 'value' => 'concert', 'icon' => '🎵'],
            ['label' => 'Bon plans', 'value' => 'stand', 'icon' => '🏪'],
        ] : $filters;
    }

    public function render(): View
    {
        return view('components.map-viewer');
    }
}
```

#### Fichier 2 : `resources/views/components/map-viewer.blade.php`
> ⚠️ **Note** : Le fichier complet est trop long pour être inclus ici. Copiez-le depuis le projet source.

### Étape 2 : Configuration du Token Mapbox

Ajoutez votre token Mapbox dans le fichier `.env` :

```env
MAPBOX_TOKEN=pk.eyJ1IjoieW91cnVzZXJuYW1lIiwiYSI6InlvdXJfdG9rZW4ifQ.xxxxxxxx
```

⚠️ **Sécurité** : Ne commitez JAMAIS votre token dans Git !

```gitignore
# .gitignore
.env
.env.production
```

---

## Configuration

### Variables d'Environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `MAPBOX_TOKEN` | Token d'accès API Mapbox | `pk.eyJ1IjoiZXhhbXBsZSJ9...` |

### Styles Mapbox Disponibles

```php
// Styles pré-configurés Mapbox
'mapbox://styles/mapbox/streets-v12'        // Rues (recommandé)
'mapbox://styles/mapbox/outdoors-v12'       // Plein air
'mapbox://styles/mapbox/light-v11'          // Clair
'mapbox://styles/mapbox/dark-v11'           // Sombre
'mapbox://styles/mapbox/satellite-v9'       // Satellite
'mapbox://styles/mapbox/satellite-streets-v12' // Satellite + rues
'mapbox://styles/mapbox/navigation-day-v1'  // Navigation jour
'mapbox://styles/mapbox/navigation-night-v1' // Navigation nuit
```

Vous pouvez également créer un style personnalisé dans Mapbox Studio.

---

## Utilisation Basique

### Exemple Minimal

```blade
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ma Carte Interactive</title>
</head>
<body>
    <x-map-viewer :events="$events" />
</body>
</html>
```

### Dans un Contrôleur Laravel

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = [
            [
                'id' => 1,
                'name' => 'Mon Événement',
                'location' => 'Paris, France',
                'coordinates' => [2.3522, 48.8566], // [longitude, latitude]
                'type' => 'concert',
                'description' => 'Description de l\'événement',
                'time' => '20:00 - 23:00',
                'date' => '15 Janvier 2026',
                'icon' => '🎵',
                'image' => 'https://example.com/image.jpg'
            ],
            // ... autres événements
        ];

        return view('events.map', compact('events'));
    }
}
```

### Fichier de Vue `resources/views/events/map.blade.php`

```blade
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carte des Événements</title>
</head>
<body>
    <x-map-viewer 
        :events="$events"
        :center="[2.3522, 48.8566]"
        :zoom="12"
        infoTitle="Mes Événements"
        infoDescription="Découvrez tous nos événements sur la carte"
    />
</body>
</html>
```

---

## API du Composant

### Props Disponibles

| Prop | Type | Défaut | Description |
|------|------|--------|-------------|
| `events` | `array` | `[]` | **Requis**. Liste des événements à afficher |
| `center` | `array` | `[2.0850, 6.3611]` | Coordonnées du centre `[longitude, latitude]` |
| `zoom` | `int` | `14` | Niveau de zoom initial (0-22) |
| `style` | `string` | `'mapbox://styles/mapbox/streets-v12'` | Style de carte Mapbox |
| `showInfo` | `bool` | `true` | Afficher le panneau d'informations |
| `infoTitle` | `string` | `'Vodun Days'` | Titre du panneau d'info |
| `infoDescription` | `string` | `'Découvrez...'` | Description du panneau |
| `showFilters` | `bool` | `true` | Afficher les boutons de filtre |
| `filters` | `array` | Filtres par défaut | Liste des filtres personnalisés |
| `showSearch` | `bool` | `true` | Afficher la barre de recherche |
| `mapId` | `string` | `'map'` | ID unique de la carte (si plusieurs cartes) |

---

## Exemples d'Utilisation

### 1. Configuration Simple

```blade
<x-map-viewer :events="$events" />
```

### 2. Carte Personnalisée

```blade
<x-map-viewer 
    :events="$events"
    :center="[-73.935242, 40.730610]"  {{-- New York --}}
    :zoom="13"
    style="mapbox://styles/mapbox/dark-v11"
    infoTitle="Événements NYC"
    infoDescription="Explorez les événements à New York"
/>
```

### 3. Sans Panneau d'Informations

```blade
<x-map-viewer 
    :events="$events"
    :showInfo="false"
    :showSearch="true"
    :showFilters="true"
/>
```

### 4. Filtres Personnalisés

```blade
@php
$customFilters = [
    ['label' => 'Tous', 'value' => 'all', 'icon' => '🌍'],
    ['label' => 'Restaurants', 'value' => 'restaurant', 'icon' => '🍴'],
    ['label' => 'Hôtels', 'value' => 'hotel', 'icon' => '🏨'],
    ['label' => 'Attractions', 'value' => 'attraction', 'icon' => '🎡'],
];
@endphp

<x-map-viewer 
    :events="$events"
    :filters="$customFilters"
/>
```

### 5. Plusieurs Cartes sur la Même Page

```blade
{{-- Carte 1 --}}
<div style="height: 400px; margin-bottom: 20px;">
    <x-map-viewer 
        :events="$restaurantEvents"
        mapId="map-restaurants"
        infoTitle="Restaurants"
    />
</div>

{{-- Carte 2 --}}
<div style="height: 400px;">
    <x-map-viewer 
        :events="$hotelEvents"
        mapId="map-hotels"
        infoTitle="Hôtels"
    />
</div>
```

---

## Structure des Données

### Format d'un Événement

Chaque événement dans le tableau `$events` doit avoir la structure suivante :

```php
[
    'id' => 1,                          // Identifiant unique (int|string)
    'name' => 'Nom de l\'événement',    // Nom affiché (string, requis)
    'location' => 'Lieu',               // Adresse ou lieu (string, requis)
    'coordinates' => [lng, lat],        // [longitude, latitude] (array, requis)
    'type' => 'concert',                // Type pour filtrage (string, requis)
    'category' => 'music',              // Catégorie optionnelle (string)
    'description' => 'Description...',  // Description complète (string)
    'time' => '20:00 - 23:00',         // Horaire (string)
    'date' => '15 Janvier 2026',       // Date (string)
    'icon' => '🎵',                     // Emoji ou icône (string)
    'image' => 'https://...',           // URL de l'image (string, requis)
]
```

### Champs Obligatoires

- `name` : Nom de l'événement
- `coordinates` : `[longitude, latitude]` (attention à l'ordre !)
- `type` : Type pour le système de filtres
- `image` : URL de l'image du marqueur

### Types de Marqueurs

Le composant colore automatiquement les marqueurs selon leur `type` :

| Type | Couleur | Usage |
|------|---------|-------|
| `vodur` | Violet (`#9333ea`) | Temples, lieux spirituels |
| `concert` | Orange (`#ff6b35`) | Concerts, musique |
| `stand` | Vert (`#10b981`) | Stands, marchés |
| `activite` | Bleu (`#3b82f6`) | Activités, animations |

Pour ajouter un nouveau type, modifiez le CSS dans `map-viewer.blade.php` :

```css
.custom-marker.votre-type {
    border-color: #votre-couleur;
}
```

### Exemple Complet avec Données BD

```php
// Dans votre contrôleur
public function index()
{
    $events = Event::where('status', 'published')
        ->get()
        ->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->title,
                'location' => $event->address,
                'coordinates' => [
                    $event->longitude,  // Attention : longitude en premier !
                    $event->latitude
                ],
                'type' => $event->category,
                'description' => $event->description,
                'time' => $event->start_time . ' - ' . $event->end_time,
                'date' => $event->event_date->format('d F Y'),
                'icon' => $event->emoji ?? '📍',
                'image' => $event->cover_image_url,
            ];
        })
        ->toArray();

    return view('events.map', compact('events'));
}
```

---

## Personnalisation

### Modifier les Styles CSS

Le composant utilise du CSS inline. Pour personnaliser l'apparence :

1. **Option A** : Modifier directement `map-viewer.blade.php`
2. **Option B** : Ajouter des styles externes

```blade
{{-- Dans votre vue --}}
<style>
    /* Surcharger les styles */
    .info-panel {
        background: rgba(0, 0, 0, 0.8) !important;
        color: white !important;
    }
    
    .custom-marker.concert {
        border-color: #ff0000 !important;
    }
</style>

<x-map-viewer :events="$events" />
```

### Personnaliser le Panneau de Détails

Modifiez la section HTML dans `map-viewer.blade.php` :

```html
<!-- Ligne ~560 -->
<div class="event-details-content">
    <h2 id="panelTitle"></h2>
    
    {{-- Ajoutez vos champs personnalisés --}}
    <div class="custom-field">
        <span id="customField"></span>
    </div>
    
    <!-- ... reste du code -->
</div>
```

Puis ajoutez le JavaScript pour remplir le champ :

```javascript
// Ligne ~700 dans la fonction openEventDetails
document.getElementById('customField').textContent = event.customField || '';
```

### Changer les Icônes de Filtres

```blade
@php
$filters = [
    ['label' => 'Tout', 'value' => 'all', 'icon' => '🌟'],
    ['label' => 'Nouveautés', 'value' => 'new', 'icon' => '✨'],
    // Utilisez des emojis ou des classes d'icônes
];
@endphp

<x-map-viewer :events="$events" :filters="$filters" />
```

---

## Déploiement en Production

### Checklist Avant Déploiement

- [ ] Token Mapbox configuré dans `.env.production`
- [ ] Token Mapbox ajouté à `.gitignore`
- [ ] Images des événements optimisées (WebP, <200KB)
- [ ] Données de test supprimées
- [ ] Cache Laravel vidé (`php artisan cache:clear`)
- [ ] Tests effectués sur mobile
- [ ] Géolocalisation testée (HTTPS requis)

### Configuration Nginx (Recommandé)

```nginx
# nginx.conf
server {
    listen 443 ssl http2;
    server_name votredomaine.com;

    # Géolocalisation nécessite HTTPS
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Cache des assets Mapbox
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # ... reste de la config Laravel
}
```

### Variables d'Environnement Production

```env
# .env.production
APP_ENV=production
APP_DEBUG=false
MAPBOX_TOKEN=pk.votre_token_production

# Limiter l'URL du token Mapbox
MAPBOX_URL_RESTRICTIONS=https://votredomaine.com/*
```

### Optimisations Performance

1. **Images des Événements**
   ```php
   // Utilisez un CDN pour les images
   'image' => 'https://cdn.votredomaine.com/events/' . $event->id . '.webp'
   ```

2. **Lazy Loading des Marqueurs**
   Si vous avez >100 événements, implémentez le clustering (voir doc Mapbox).

3. **Cache des Données**
   ```php
   // Dans le contrôleur
   $events = Cache::remember('map_events', 3600, function () {
       return Event::published()->get()->map(...)->toArray();
   });
   ```

### Monitoring

Ajoutez Google Analytics ou Matomo pour tracker :
- Utilisation de la carte
- Clics sur les marqueurs
- Utilisation de la navigation GPS

```javascript
// Dans map-viewer.blade.php, fonction openEventDetails
function openEventDetails(event) {
    // Analytics
    gtag('event', 'marker_click', {
        'event_name': event.name,
        'event_type': event.type
    });
    
    // ... reste du code
}
```

---

## Dépannage

### Problème : La carte ne s'affiche pas

**Causes possibles :**
1. Token Mapbox manquant ou invalide
2. Clé `.env` non chargée
3. JavaScript bloqué par un adblocker

**Solutions :**
```bash
# Vérifier le token
php artisan tinker
>>> env('MAPBOX_TOKEN')

# Vider le cache
php artisan config:clear
php artisan cache:clear

# Tester en console navigateur
console.log(mapboxgl.accessToken);
```

### Problème : Marqueurs mal positionnés

**Cause :** Inversion longitude/latitude

**Solution :** 
```php
// ❌ Incorrect
'coordinates' => [$latitude, $longitude]

// ✅ Correct
'coordinates' => [$longitude, $latitude]
```

### Problème : Géolocalisation ne fonctionne pas

**Causes :**
- Site non en HTTPS
- Permission refusée par l'utilisateur
- Navigateur incompatible

**Solution :**
```javascript
// Test dans la console
navigator.geolocation.getCurrentPosition(
    pos => console.log('OK', pos),
    err => console.error('Erreur', err)
);
```

### Problème : Erreur CORS avec les images

**Cause :** Images hébergées sur un domaine sans CORS

**Solution :**
```php
// Utiliser des images du même domaine ou avec CORS activé
'image' => asset('storage/events/' . $event->id . '.jpg')
```

### Problème : Performances lentes avec beaucoup d'événements

**Solutions :**
1. Implémenter le clustering Mapbox
2. Charger les événements par région visible
3. Utiliser la pagination côté serveur

```javascript
// Clustering (à ajouter dans map-viewer.blade.php)
map.addSource('events', {
    type: 'geojson',
    data: { type: 'FeatureCollection', features: events },
    cluster: true,
    clusterMaxZoom: 14,
    clusterRadius: 50
});
```

---

## Support et Ressources

### Documentation Officielle
- [Mapbox GL JS](https://docs.mapbox.com/mapbox-gl-js/)
- [Laravel Components](https://laravel.com/docs/blade#components)
- [Mapbox Directions API](https://docs.mapbox.com/api/navigation/directions/)

### Exemples de Code
Consultez le projet source pour voir des exemples complets :
- [VodunDaysController.php](app/Http/Controllers/VodunDaysController.php)
- [vodun-days-simple.blade.php](resources/views/vodun-days-simple.blade.php)

### Communauté
- [Stack Overflow - Mapbox](https://stackoverflow.com/questions/tagged/mapbox-gl-js)
- [Laravel Forums](https://laracasts.com/discuss)

---

## Changelog

### Version 1.0.0 (Décembre 2025)
- ✨ Version initiale
- ✅ Marqueurs personnalisés avec images
- ✅ Tooltip au survol prolongé
- ✅ Panneau de détails événement
- ✅ Navigation GPS avec itinéraire
- ✅ Filtres et recherche
- ✅ Responsive design
- ✅ Bâtiments 3D

---

## Licence

Ce composant est fourni tel quel. Libre d'utilisation et de modification selon vos besoins.

---

## Contributeurs

Développé pour le projet Vodun Days 2026.

Pour toute question ou amélioration, contactez l'équipe de développement.

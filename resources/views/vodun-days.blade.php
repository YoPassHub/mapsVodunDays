<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vodun Days - Carte Interactive des Événements</title>
    
    <!-- Mapbox GL JS -->
    <link href='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js'></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', sans-serif;
        }
        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
        }
        
        .info-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #1b1b18;
            padding: 20px;
            border-radius: 12px;
            max-width: 320px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .info-panel.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        
        .info-panel h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #1b1b18;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-panel p {
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 12px 0;
            color: rgba(27, 27, 24, 0.85);
        }
        
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.05);
            padding: 4px 10px;
            border-radius: 16px;
            color: #1b1b18;
        }
        
        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .bottom-nav {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            color: #1b1b18;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.3);
        }
        
        .nav-btn.active {
            background: #1b1b18;
            color: white;
            border-color: #1b1b18;
        }
        
        .nav-btn .icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mapboxgl-popup-content {
            background: rgba(255, 255, 255, 0.98);
            color: #1b1b18;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(0, 0, 0, 0.1);
            min-width: 250px;
        }
        
        .mapboxgl-popup-content h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1b1b18;
        }
        
        .mapboxgl-popup-content p {
            margin: 5px 0;
            font-size: 14px;
            color: rgba(27, 27, 24, 0.85);
        }
        
        .mapboxgl-popup-content .time {
            display: inline-block;
            background: rgba(255, 102, 0, 0.2);
            color: #ff6600;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
        }
        
        .mapboxgl-popup-close-button {
            color: #1b1b18;
            font-size: 24px;
            padding: 5px 10px;
        }
        
        .mapboxgl-popup-close-button:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        
        /* Panneau de détails de l'événement */
        .event-details-panel {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 400px;
            max-height: 80vh;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transform: translateY(calc(100% + 50px));
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 1000;
        }
        
        .event-details-panel.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .event-details-header {
            position: relative;
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        
        .event-details-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        }
        
        .event-details-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .event-details-close:hover {
            background: white;
            transform: rotate(90deg);
        }
        
        .event-details-content {
            padding: 25px;
            max-height: calc(80vh - 200px);
            overflow-y: auto;
        }
        
        .event-details-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #1b1b18;
        }
        
        .event-details-location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .event-details-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .event-meta-item strong {
            color: #1b1b18;
        }
        
        .event-details-description {
            font-size: 15px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 25px;
        }
        
        .event-details-button {
            width: 100%;
            padding: 15px;
            background: #ff6600;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .event-details-button:hover {
            background: #ff5500;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 102, 0, 0.4);
        }
        
        .event-details-button:active {
            transform: translateY(0);
        }
        
        .search-box {
            position: absolute;
            top: 30px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        .search-box input {
            width: 180px;
            padding: 10px 16px;
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.15);
            color: #1b1b18;
            padding: 10px 15px;
            border-radius: 8px;
            width: 250px;
            font-size: 14px;
        }
        
        .search-box input::placeholder {
            color: rgba(27, 27, 24, 0.5);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: rgba(0, 0, 0, 0.3);
            background: white;
        }
        
        .marker {
            background-size: cover;
            background-position: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .marker:hover {
            transform: scale(1.15);
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.6);
        }
        
        .marker::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--marker-color-1), var(--marker-color-2));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }
        
        .marker-vodur {
            --marker-color-1: #ff3333;
            --marker-color-2: #ff0000;
            border: 3px solid #ff3333;
        }
        
        .marker-concert {
            --marker-color-1: #ff8800;
            --marker-color-2: #ff6600;
            border: 3px solid #ff8800;
        }
        
        .marker-stand {
            --marker-color-1: #ffbb00;
            --marker-color-2: #ff9900;
            border: 3px solid #ffbb00;
        }
        
        .marker-activite {
            --marker-color-1: #ffdd00;
            --marker-color-2: #ffbb00;
            border: 3px solid #ffdd00;
        }
        
        .marker-label {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(27, 27, 24, 0.95);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .marker:hover .marker-label {
            opacity: 1;
        }
        
        .marker-tooltip {
            position: fixed;
            background: rgba(27, 27, 24, 0.98);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            max-width: 300px;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .marker-tooltip.show {
            opacity: 1;
        }
        
        .marker-tooltip h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        
        .marker-tooltip p {
            margin: 5px 0;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
        }
        
        .marker-tooltip .time {
            display: inline-block;
            background: rgba(255, 102, 0, 0.2);
            color: #ff6600;
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .info-panel {
                max-width: calc(100% - 40px);
                padding: 20px;
            }
            
            .info-panel h1 {
                font-size: 24px;
            }
        
        .info-panel h1 img.logo {
            width: 22px;
            height: 22px;
        }
                bottom: 20px;
                padding: 10px;
            }
            
            .search-box {
                top: 30px;
                right: 10px;
            }
            
            .search-box input {
                width: 150px;
                font-size: 12px;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <!-- Tooltip pour afficher les infos au survol prolongé -->
    <div id="markerTooltip" class="marker-tooltip"></div>

    <!-- Event Details Panel -->
    <div id="eventDetailsPanel" class="event-details-panel">
        <div class="event-details-header" id="panelHeader">
            <button class="event-details-close" id="closePanel">×</button>
        </div>
        <div class="event-details-content">
            <h2 id="panelTitle"></h2>
            <div class="event-details-meta">
                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <span id="panelLocation"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🗓️</span>
                    <span id="panelDate"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🕐</span>
                    <span id="panelTime"></span>
                </div>
            </div>
            <div class="event-details-description" id="panelDescription"></div>
            <button class="event-details-button" id="directionsBtn">
                <span>🧭</span> S'y rendre
            </button>
        </div>
    </div>
    
    <div class="info-panel">
        <h1>
            <img src="{{ asset('vodun-days.png') }}" alt="Logo Vodun Days" class="logo">
            Vodun Days
        </h1>
        <p>Découvrez les événements culturels et célébrations du patrimoine Vodun à travers la carte interactive. Cliquez sur les marqueurs pour plus d'informations.</p>
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #ff3333;"></div>
                <span>Vodur</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ff8800;"></div>
                <span>Concert</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffbb00;"></div>
                <span>Stand</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffdd00;"></div>
                <span>Activité</span>
            </div>
        </div>
    </div>
    
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Rechercher un événement..." />
    </div>
    
    <div class="bottom-nav">
        <button class="nav-btn active" data-filter="all">
            <span class="icon">⭐</span>
            <span>Tout</span>
        </button>
        <button class="nav-btn" data-filter="vodur">
            <span class="icon">🏛️</span>
            <span>Vodundays</span>
        </button>
        <button class="nav-btn" data-filter="concert">
            <span class="icon">🎵</span>
            <span>Événements</span>
        </button>
        <button class="nav-btn" data-filter="stand">
            <span class="icon">🏪</span>
            <span>Bon plans</span>
        </button>
    </div>

    <script>
        // Configuration Mapbox - Token depuis .env
        mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';
        
        // Données des événements depuis PHP
        const events = @json($events);
        
        // Initialisation de la carte centrée sur Ouidah avec un style plus lisible
        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12', // Style clair avec toutes les informations visibles
            center: [2.0850, 6.3611], // Ouidah
            zoom: 14,
            pitch: 0, // Vue plane pour mieux voir les détails
            bearing: 0
        });
        
        // Couleurs par type d'événement
        const colors = {
            vodur: '#ff3333',
            concert: '#ff8800',
            stand: '#ffbb00',
            activite: '#ffdd00'
        };
        
        // Stocker les marqueurs
        let markers = [];
        
        // Tooltip pour affichage au survol
        const tooltip = document.getElementById('markerTooltip');
        let tooltipTimeout = null;
        
        // Ajouter les marqueurs sur la carte
        events.forEach(event => {
            const el = document.createElement('div');
            el.className = `marker marker-${event.type}`;
            
            // Ajouter l'image de fond
            if (event.image) {
                el.style.backgroundImage = `url('${event.image}')`;
            }
            
            // Empêcher les événements de déplacement
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
            
            // Ajouter le label au survol
            const label = document.createElement('div');
            label.className = 'marker-label';
            label.textContent = event.name;
            el.appendChild(label);
            
            const marker = new mapboxgl.Marker({
                element: el,
                anchor: 'center',
                draggable: false
            })
                .setLngLat(event.coordinates)
                .addTo(map);
            
            // Au clic, ouvrir le panneau de détails
            el.addEventListener('click', function() {
                openEventDetails(event);
            });
            
            // Gestion du tooltip au survol prolongé
            el.addEventListener('mouseenter', function(e) {
                // Délai de 800ms avant d'afficher le tooltip
                tooltipTimeout = setTimeout(() => {
                    tooltip.innerHTML = `
                        <h4>${event.icon} ${event.name}</h4>
                        <p><strong>📍 ${event.location}</strong></p>
                        <p>${event.description}</p>
                        <span class="time">⏰ ${event.time}</span>
                    `;
                    
                    // Positionner le tooltip près du curseur
                    const rect = el.getBoundingClientRect();
                    tooltip.style.left = (rect.right + 10) + 'px';
                    tooltip.style.top = rect.top + 'px';
                    
                    // Afficher le tooltip
                    tooltip.classList.add('show');
                }, 800); // 800ms de délai
            });
            
            el.addEventListener('mouseleave', function() {
                // Annuler le timeout si on quitte avant le délai
                if (tooltipTimeout) {
                    clearTimeout(tooltipTimeout);
                    tooltipTimeout = null;
                }
                // Masquer le tooltip
                tooltip.classList.remove('show');
            });
            
            el.addEventListener('mousemove', function(e) {
                // Mettre à jour la position du tooltip pendant le mouvement
                if (tooltip.classList.contains('show')) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY - tooltip.offsetHeight / 2) + 'px';
                }
            });
            
            // Stocker la taille d'origine
            marker._originalSize = 60;
            
            markers.push({ marker, event });
        });
        
        // Fonction pour ajuster la taille des marqueurs selon le zoom
        function updateMarkerSizes() {
            const zoom = map.getZoom();
            const baseSize = 60;
            
            // Calculer la taille en fonction du niveau de zoom
            let scale;
            if (zoom < 12) {
                scale = 0.5; // 50% de la taille
            } else if (zoom < 14) {
                scale = 0.5 + (zoom - 12) * 0.25; // Transition de 50% à 100%
            } else if (zoom > 16) {
                scale = 1 + (zoom - 16) * 0.2; // Augmente au-delà de 100%
            } else {
                scale = 1; // Taille normale entre 14 et 16
            }
            
            const size = Math.max(30, Math.min(80, baseSize * scale));
            
            markers.forEach(({ marker }) => {
                const el = marker.getElement();
                if (el) {
                    // Utiliser transform au lieu de modifier width/height pour éviter le repositionnement
                    el.style.transform = `scale(${scale})`;
                    el.style.transformOrigin = 'center center';
                    
                    // Ajuster l'opacité pour les petits zooms
                    if (zoom < 11) {
                        el.style.opacity = '0.7';
                    } else {
                        el.style.opacity = '1';
                    }
                }
            });
        }
        
        // Écouter les changements de zoom
        map.on('zoom', updateMarkerSizes);
        
        // Appliquer la taille initiale après chargement
        map.on('load', updateMarkerSizes);
        
        // Contrôles de navigation
        map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');
        
        // Filtrage des événements et gestion du panneau d'info
        const infoPanel = document.querySelector('.info-panel');
        
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Retirer la classe active de tous les boutons
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                // Ajouter la classe active au bouton cliqué
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                
                // Afficher le panneau uniquement pour le filtre 'vodur'
                if (filter === 'vodur' && infoPanel) {
                    infoPanel.classList.add('show');
                } else if (infoPanel) {
                    infoPanel.classList.remove('show');
                }
                
                markers.forEach(({ marker, event }) => {
                    if (filter === 'all' || event.type === filter) {
                        marker.getElement().style.display = 'block';
                    } else {
                        marker.getElement().style.display = 'none';
                    }
                });
            });
        });
        
        // Recherche d'événements
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            markers.forEach(({ marker, event }) => {
                const matchesSearch = 
                    event.name.toLowerCase().includes(searchTerm) ||
                    event.location.toLowerCase().includes(searchTerm) ||
                    event.description.toLowerCase().includes(searchTerm);
                
                marker.getElement().style.display = matchesSearch ? 'block' : 'none';
            });
        });
        
        // Animation de rotation douce de la carte
        let rotating = false;
        
        function rotateCamera(timestamp) {
            if (rotating) {
                map.rotateTo((timestamp / 200) % 360, { duration: 0 });
                requestAnimationFrame(rotateCamera);
            }
        }
        
        // Démarrer la rotation au chargement (optionnel)
        // rotating = true;
        // rotateCamera(0);
        
        // Effet 3D sur les bâtiments
        map.on('load', () => {
            const layers = map.getStyle().layers;
            const labelLayerId = layers.find(
                (layer) => layer.type === 'symbol' && layer.layout['text-field']
            ).id;
            
            map.addLayer(
                {
                    'id': '3d-buildings',
                    'source': 'composite',
                    'source-layer': 'building',
                    'filter': ['==', 'extrude', 'true'],
                    'type': 'fill-extrusion',
                    'minzoom': 15,
                    'paint': {
                        'fill-extrusion-color': '#444',
                        'fill-extrusion-height': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            15,
                            0,
                            15.05,
                            ['get', 'height']
                        ],
                        'fill-extrusion-base': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            15,
                            0,
                            15.05,
                            ['get', 'min_height']
                        ],
                        'fill-extrusion-opacity': 0.6
                    }
                },
                labelLayerId
            );
        });

        // Gestion du panneau de détails
        const eventPanel = document.getElementById('eventDetailsPanel');
        const panelHeader = document.getElementById('panelHeader');
        const panelTitle = document.getElementById('panelTitle');
        const panelLocation = document.getElementById('panelLocation');
        const panelDate = document.getElementById('panelDate');
        const panelTime = document.getElementById('panelTime');
        const panelDescription = document.getElementById('panelDescription');
        const closePanel = document.getElementById('closePanel');
        const directionsBtn = document.getElementById('directionsBtn');
        
        let currentEvent = null;
        let directionsLayer = null;
        
        function openEventDetails(event) {
            currentEvent = event;
            
            // Remplir les informations
            panelHeader.style.backgroundImage = `url(${event.image})`;
            panelTitle.textContent = `${event.icon} ${event.name}`;
            panelLocation.textContent = event.location;
            panelDate.textContent = event.date || 'Date à confirmer';
            panelTime.textContent = event.time;
            panelDescription.textContent = event.description;
            
            // Afficher le panneau
            eventPanel.classList.add('show');
            
            // Centrer la carte sur l'événement
            map.flyTo({
                center: event.coordinates,
                zoom: 16,
                duration: 1000
            });
        }
        
        function closeEventDetails() {
            eventPanel.classList.remove('show');
            currentEvent = null;
            
            // Supprimer l'itinéraire s'il existe
            if (directionsLayer && map.getLayer('route')) {
                map.removeLayer('route');
                map.removeSource('route');
                directionsLayer = null;
            }
        }
        
        closePanel.addEventListener('click', closeEventDetails);
        
        // Fonction pour afficher l'itinéraire
        directionsBtn.addEventListener('click', function() {
            if (!currentEvent) return;
            
            // Obtenir la position de l'utilisateur
            if (navigator.geolocation) {
                directionsBtn.innerHTML = '<span>⏳</span> Calcul en cours...';
                directionsBtn.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLocation = [position.coords.longitude, position.coords.latitude];
                        const eventLocation = currentEvent.coordinates;
                        
                        // Utiliser l'API Directions de Mapbox
                        const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${userLocation[0]},${userLocation[1]};${eventLocation[0]},${eventLocation[1]}?geometries=geojson&access_token=${mapboxgl.accessToken}`;
                        
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                if (data.routes && data.routes.length > 0) {
                                    const route = data.routes[0].geometry;
                                    const duration = Math.round(data.routes[0].duration / 60);
                                    const distance = (data.routes[0].distance / 1000).toFixed(1);
                                    
                                    // Supprimer l'ancienne route si elle existe
                                    if (map.getLayer('route')) {
                                        map.removeLayer('route');
                                        map.removeSource('route');
                                    }
                                    
                                    // Ajouter la nouvelle route sur la carte
                                    map.addSource('route', {
                                        'type': 'geojson',
                                        'data': {
                                            'type': 'Feature',
                                            'properties': {},
                                            'geometry': route
                                        }
                                    });
                                    
                                    map.addLayer({
                                        'id': 'route',
                                        'type': 'line',
                                        'source': 'route',
                                        'layout': {
                                            'line-join': 'round',
                                            'line-cap': 'round'
                                        },
                                        'paint': {
                                            'line-color': '#FF6B35',
                                            'line-width': 5,
                                            'line-opacity': 0.8
                                        }
                                    });
                                    
                                    directionsLayer = 'route';
                                    
                                    // Ajuster la vue pour montrer tout l'itinéraire
                                    const coordinates = route.coordinates;
                                    const bounds = coordinates.reduce((bounds, coord) => {
                                        return bounds.extend(coord);
                                    }, new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]));
                                    
                                    map.fitBounds(bounds, {
                                        padding: { top: 100, bottom: 100, left: 100, right: 450 }
                                    });
                                    
                                    // Ajouter un marqueur pour la position de l'utilisateur
                                    new mapboxgl.Marker({ color: '#4CAF50' })
                                        .setLngLat(userLocation)
                                        .setPopup(new mapboxgl.Popup().setHTML('<p>Votre position</p>'))
                                        .addTo(map);
                                    
                                    directionsBtn.innerHTML = `<span>✅</span> ${distance} km • ${duration} min`;
                                    directionsBtn.disabled = false;
                                } else {
                                    directionsBtn.innerHTML = '<span>❌</span> Itinéraire introuvable';
                                    directionsBtn.disabled = false;
                                }
                            })
                            .catch(error => {
                                console.error('Erreur lors du calcul de l\'itinéraire:', error);
                                directionsBtn.innerHTML = '<span>❌</span> Erreur';
                                directionsBtn.disabled = false;
                            });
                    },
                    function(error) {
                        alert('Impossible d\'obtenir votre position. Veuillez autoriser la géolocalisation.');
                        directionsBtn.innerHTML = '<span>🧭</span> S\'y rendre';
                        directionsBtn.disabled = false;
                    }
                );
            } else {
                alert('La géolocalisation n\'est pas supportée par votre navigateur.');
            }
        });
    </script>
</body>
</html>

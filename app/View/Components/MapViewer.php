<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class MapViewer extends Component
{
    /**
     * Les événements à afficher sur la carte
     */
    public array $events;

    /**
     * Le centre de la carte [longitude, latitude]
     */
    public array $center;

    /**
     * Le niveau de zoom initial
     */
    public int $zoom;

    /**
     * Le style de la carte Mapbox
     */
    public string $style;

    /**
     * Afficher le panneau d'informations
     */
    public bool $showInfo;

    /**
     * Titre du panneau d'informations
     */
    public string $infoTitle;

    /**
     * Description du panneau d'informations
     */
    public string $infoDescription;

    /**
     * Afficher les filtres
     */
    public bool $showFilters;

    /**
     * Liste des filtres disponibles
     */
    public array $filters;

    /**
     * Afficher la barre de recherche
     */
    public bool $showSearch;

    /**
     * ID unique de la carte
     */
    public string $mapId;

    /**
     * Créer une nouvelle instance du composant
     */
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
        
        // Filtres par défaut si aucun n'est fourni
        $this->filters = empty($filters) ? [
            ['label' => 'Tout', 'value' => 'all', 'icon' => '⭐'],
            ['label' => 'Vodundays', 'value' => 'vodur', 'icon' => '🏛️'],
            ['label' => 'Événements', 'value' => 'concert', 'icon' => '🎵'],
            ['label' => 'Bon plans', 'value' => 'stand', 'icon' => '🏪'],
        ] : $filters;
    }

    /**
     * Obtenir la vue / le contenu représentant le composant
     */
    public function render(): View
    {
        return view('components.map-viewer');
    }
}

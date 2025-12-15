<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vodun Days - Carte Interactive des Événements</title>
</head>
<body>
    <x-map-viewer 
        :events="$events"
        :center="[2.0850, 6.3611]"
        :zoom="14"
        style="mapbox://styles/mapbox/streets-v12"
        :showInfo="true"
        infoTitle="Vodun Days"
        infoDescription="Découvrez les événements et lieux spirituels du Vodun Days à Ouidah"
        :showFilters="true"
        :showSearch="true"
        mapId="vodun-map"
    />
</body>
</html>

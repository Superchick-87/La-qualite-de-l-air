<?php

// --- FICHIER DE REQUÊTE API POUR LA QUALITÉ DE L'AIR EN NOUVELLE-AQUITAINE ---

// -------------------------------------------------------------------------
// --- CONFIGURATION ---
// -------------------------------------------------------------------------
// REMPLACER par le token que vous avez reçu d'Atmo Data.
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NTg1NDU3OTQsImV4cCI6MTc1ODYzMjE5NCwicm9sZXMiOlsiUk9MRV9BUEkiLCJST0xFX1VTRVIiXSwidXNlcm5hbWUiOiJuaWNvX3NvIn0.q3DqtcE10hdTetzXAajnshVRRPXyGjoaqwiiGaNXLGvbRzIwBpcYHR7TujFSpvZLVa8LY8ixEl3dkkxL53VBCStut00IlXdNeITWBzmw1iNq6RhZnXnlkcMkoatmeNrQp8Cbo-B3cYeuSp7hbCbAihiGBDDt6Q6LV90iCEmTburl5GbsdYB_W3UvtotWr73tPw1aLpyh_TWRB8BPDsCvXwbD3tu5Y4bzrDyd4YK8YZXwretXbbsIrj6cctjEcioL9z-XV0Hc3tE5AYmRtQ1514XheoXKO6svtIwiuwS-3hl4TBoNcRJW633u5cJ4IXZehUEz2a-r2__Bcz8kN9NvOw";

// Liste des départements et des villes correspondantes avec leurs codes INSEE
$departements = [
    '17' => [
        'nom' => 'Charente-Maritime',
        'villes' => [
            ['nom' => 'La Rochelle', 'insee' => '17300', 'type' => 'Préfecture'],
            ['nom' => 'Jonzac', 'insee' => '17197', 'type' => 'Sous-préfecture'],
            ['nom' => 'Rochefort', 'insee' => '17299', 'type' => 'Sous-préfecture'],
            ['nom' => 'Saintes', 'insee' => '17397', 'type' => 'Sous-préfecture'],
            ['nom' => 'Saint-Jean-d\'Angély', 'insee' => '17347', 'type' => 'Sous-préfecture']
        ]
    ],
    '24' => [
        'nom' => 'Dordogne',
        'villes' => [
            ['nom' => 'Périgueux', 'insee' => '24322', 'type' => 'Préfecture'],
            ['nom' => 'Bergerac', 'insee' => '24037', 'type' => 'Sous-préfecture'],
            ['nom' => 'Nontron', 'insee' => '24311', 'type' => 'Sous-préfecture'],
            ['nom' => 'Sarlat-la-Canéda', 'insee' => '24520', 'type' => 'Sous-préfecture']
        ]
    ],
    '33' => [
        'nom' => 'Gironde',
        'villes' => [
            ['nom' => 'Bordeaux', 'insee' => '33063', 'type' => 'Préfecture'],
            ['nom' => 'Arcachon', 'insee' => '33009', 'type' => 'Sous-préfecture'],
            ['nom' => 'Blaye', 'insee' => '33053', 'type' => 'Sous-préfecture'],
            ['nom' => 'Langon', 'insee' => '33227', 'type' => 'Sous-préfecture'],
            ['nom' => 'Lesparre-Médoc', 'insee' => '33240', 'type' => 'Sous-préfecture'],
            ['nom' => 'Libourne', 'insee' => '33243', 'type' => 'Sous-préfecture']
        ]
    ]
];

// Les départements pour lesquels nous voulons afficher les données
$departementsRecherches = ['17', '24', '33'];

// -------------------------------------------------------------------------
// --- VARIABLES GÉNÉRALES ET PRÉPARATION DES EN-TÊTES ---
// -------------------------------------------------------------------------
$today = date("Y-m-d"); 
$tomorrow = date("Y-m-d", strtotime("+1 day"));
$headers = ["Authorization: Bearer " . $token];

// -------------------------------------------------------------------------
// --- FONCTION UTILITAIRE POUR LES REQUÊTES ---
// -------------------------------------------------------------------------
function getAtmoData($url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    return ['error' => 'HTTP_ERROR', 'code' => $httpCode, 'response' => $response];
}

// -------------------------------------------------------------------------
// --- AFFICHAGE DE LA QUALITÉ DE L'AIR (SANS CHANGEMENT) ---
// -------------------------------------------------------------------------
echo "<h1>Prévisions de la qualité de l'air pour demain</h1>";
$airQualityBaseUrl = "https://admindata.atmo-france.org/api/data/112/";

foreach ($departementsRecherches as $numeroDepartement) {
    if (!isset($departements[$numeroDepartement])) continue;
    $departement = $departements[$numeroDepartement];
    echo "<h2>" . htmlspecialchars($numeroDepartement) . " - " . htmlspecialchars($departement['nom']) . "</h2>";
    
    foreach ($departement['villes'] as $ville) {
        $params = [
            "code_zone" => ["operator" => "=", "value" => $ville['insee']],
            "date_ech" => ["operator" => ">=", "value" => $today] 
        ];
        $jsonParams = urlencode(json_encode($params));
        $url = $airQualityBaseUrl . $jsonParams . "?withGeom=false";
        
        $data = getAtmoData($url, $headers);

        if (isset($data['error'])) {
            echo "<h3>" . htmlspecialchars($ville['type']) . " : " . htmlspecialchars($ville['nom']) . "</h3>";
            echo "<p>Erreur lors de la récupération des données de qualité de l'air.</p>";
        } else {
            echo "<h3>" . htmlspecialchars($ville['type']) . " : " . htmlspecialchars($ville['nom']) . "</h3>";
            $foundTomorrow = false;
            if (!empty($data) && isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (isset($feature['properties']['date_ech']) && $feature['properties']['date_ech'] === $tomorrow) {
                        $villeNom = $feature['properties']['lib_zone'];
                        $couleurCode = $feature['properties']['coul_qual'];
                        $qualiteLib = $feature['properties']['lib_qual'];
                        
                        echo "<div style='display:flex; align-items:center; gap: 10px; font-weight:bold;'>";
                        echo "  <div style='background-color: " . htmlspecialchars($couleurCode) . "; width: 20px; height: 20px; border-radius: 50%;'></div>";
                        echo "  <div>" . htmlspecialchars($qualiteLib) . "</div>";
                        echo "</div>";
                        $foundTomorrow = true;
                        break;
                    }
                }
            }
            if (!$foundTomorrow) {
                echo "<p>Aucune prévision disponible pour demain.</p>";
            }
        }
    }
    echo "<hr>";
}

// -------------------------------------------------------------------------
// --- AFFICHAGE DES PRÉVISIONS DE POLLENS ---
// -------------------------------------------------------------------------
echo "<h1>Prévisions de pollens pour demain</h1>";
$pollenBaseUrl = "https://admindata.atmo-france.org/api/data/122/";

// On utilise une boucle pour chaque ville, car le champ 'code_zone' est disponible
foreach ($departementsRecherches as $numeroDepartement) {
    if (!isset($departements[$numeroDepartement])) continue;
    $departement = $departements[$numeroDepartement];
    echo "<h2>" . htmlspecialchars($numeroDepartement) . " - " . htmlspecialchars($departement['nom']) . "</h2>";
    
    foreach ($departement['villes'] as $ville) {
        
        echo "<h3>" . htmlspecialchars($ville['type']) . " : " . htmlspecialchars($ville['nom']) . "</h3>";
        
        $params = [
            "code_zone" => ["operator" => "=", "value" => $ville['insee']],
            "date_ech" => ["operator" => "=", "value" => $tomorrow]
        ];
        $jsonParams = urlencode(json_encode($params));
        $url = $pollenBaseUrl . $jsonParams . "?withGeom=false";
        
        $data = getAtmoData($url, $headers);

        if (isset($data['error'])) {
            echo "<p>Erreur lors de la récupération des données de pollen. Détails :</p>";
            echo "<pre>";
            print_r($data);
            echo "</pre>";
        } else {
            $foundPollen = false;
            if (!empty($data) && isset($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (isset($feature['properties']['lib_qual'])) {
                        $pollenLib = $feature['properties']['lib_qual'];
                        
                        echo "<div style='font-weight:bold;'>";
                        echo "  Niveau de risque : " . htmlspecialchars($pollenLib);
                        echo "</div>";
                        $foundPollen = true;
                        break;
                    }
                }
            }
            if (!$foundPollen) {
                echo "<p>Aucune prévision de pollen disponible pour demain.</p>";
            }
        }
        echo "<br>";
    }
    echo "<hr>";
}
?>
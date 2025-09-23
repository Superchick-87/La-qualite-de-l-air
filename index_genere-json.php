<?php

// --- FICHIER POUR GÉNÉRER UN JSON CENTRALISÉ AVEC LES DONNÉES ATMO ---

// -------------------------------------------------------------------------
// --- CONFIGURATION ---
// -------------------------------------------------------------------------

// REMPLACER par le token que vous avez reçu d'Atmo Data.
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NTg2MjI1MTcsImV4cCI6MTc1ODcwODkxNywicm9sZXMiOlsiUk9MRV9BUEkiLCJST0xFX1VTRVIiXSwidXNlcm5hbWUiOiJuaWNvX3NvIn0.A7pZbtaGG_Ea_LgRsHJcYL0kvyoZlGSNib9xuxhiGrpg89ll1qK2KFKhFz1i6pozthoI7F76WQ8U9Mhsbhpuf9TUucrnpLhGHwW9We4QqRvhNhT7yW1j9V44B9bWbqt1saShfcWibnCEHfagBOmOIiJVHuKNSCLfxMre34BqrRzRXJDhLpC6AShLJCPEPFu3wd_UlBD1AI4jkmgB4TJQkM3DHtk7MYwDKsz3omNNPV_TWSECqYPDDvao3o7allB3hUSMn4C8IcXhnMVa9v83KOw5BdcsVDJNCfwL9PEAChAXZ1ABxKNnnmLYVK5zEpRceALVYnEbAXDFy5KiRzmW6w";

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
    ],
    '40' => [
        'nom' => 'Landes',
        'villes' => [
            ['nom' => 'Mont-de-Marsan', 'insee' => '40192', 'type' => 'Préfecture'],
            ['nom' => 'Dax', 'insee' => '40088', 'type' => 'Sous-préfecture']
        ]
    ],
    '47' => [
        'nom' => 'Lot-et-Garonne',
        'villes' => [
            ['nom' => 'Agen', 'insee' => '47001', 'type' => 'Préfecture'],
            ['nom' => 'Marmande', 'insee' => '47157', 'type' => 'Sous-préfecture'],
            ['nom' => 'Nérac', 'insee' => '47195', 'type' => 'Sous-préfecture'],
            ['nom' => 'Villeneuve-sur-Lot', 'insee' => '47323', 'type' => 'Sous-préfecture']
        ]
    ],
    '64' => [
        'nom' => 'Pyrénées-Atlantiques',
        'villes' => [
            ['nom' => 'Pau', 'insee' => '64445', 'type' => 'Préfecture'],
            ['nom' => 'Bayonne', 'insee' => '64102', 'type' => 'Sous-préfecture'],
            ['nom' => 'Oloron-Sainte-Marie', 'insee' => '64424', 'type' => 'Sous-préfecture']
        ]
    ]
];
$departementsRecherches = ['17', '24', '33', '40', '47', '64'];

// Définir le chemin du dossier de destination
$dataDir = 'datas/';

// -------------------------------------------------------------------------
// --- PRÉPARATION DES VARIABLES ET DE LA FONCTION UTILITAIRE ---
// -------------------------------------------------------------------------
$today = date("Y-m-d"); 
$tomorrow = date("Y-m-d", strtotime("+1 day"));
$headers = ["Authorization: Bearer " . $token];

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

// Vérifier si le dossier existe, sinon le créer
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true); 
}

// Tableau pour stocker toutes les données
$finalData = [];

// -------------------------------------------------------------------------
// --- BOUCLE PRINCIPALE POUR COLLECTER TOUTES LES DONNÉES ---
// -------------------------------------------------------------------------

echo "<h1>Collecte des données...</h1>";

foreach ($departementsRecherches as $numeroDepartement) {
    if (!isset($departements[$numeroDepartement])) continue;
    $departement = $departements[$numeroDepartement];

    foreach ($departement['villes'] as $ville) {
        $insee = $ville['insee'];
        
        // Initialise la structure de données pour la ville
        $finalData[$insee] = [
            'ville' => $ville['nom'],
            'departement' => $departement['nom'],
            'type' => $ville['type'],
            'qualite_air' => null,
            'pollen' => null,
        ];

        // --- Récupération de la qualité de l'air ---
        $airQualityBaseUrl = "https://admindata.atmo-france.org/api/data/112/";
        $airParams = [
            "code_zone" => ["operator" => "=", "value" => $insee],
            "date_ech" => ["operator" => ">=", "value" => $today] 
        ];
        $jsonAirParams = urlencode(json_encode($airParams));
        $airUrl = $airQualityBaseUrl . $jsonAirParams . "?withGeom=false";
        $airData = getAtmoData($airUrl, $headers);

        if (!isset($airData['error']) && !empty($airData) && isset($airData['features'])) {
            foreach ($airData['features'] as $feature) {
                if (isset($feature['properties']['date_ech']) && $feature['properties']['date_ech'] === $tomorrow) {
                    $finalData[$insee]['qualite_air'] = $feature['properties'];
                    break;
                }
            }
        }

        // --- Récupération des données de pollen ---
        $pollenBaseUrl = "https://admindata.atmo-france.org/api/data/122/";
        $pollenParams = [
            "code_zone" => ["operator" => "=", "value" => $insee],
            "date_ech" => ["operator" => "=", "value" => $tomorrow]
        ];
        $jsonPollenParams = urlencode(json_encode($pollenParams));
        $pollenUrl = $pollenBaseUrl . $jsonPollenParams . "?withGeom=false";
        $pollenData = getAtmoData($pollenUrl, $headers);

        if (!isset($pollenData['error']) && !empty($pollenData) && isset($pollenData['features'])) {
            foreach ($pollenData['features'] as $feature) {
                if (isset($feature['properties']['lib_qual'])) {
                    $finalData[$insee]['pollen'] = $feature['properties'];
                    break;
                }
            }
        }
        echo "<p>✔️ Données collectées pour " . htmlspecialchars($ville['nom']) . "</p>";
    }
}

// -------------------------------------------------------------------------
// --- ENREGISTREMENT DU FICHIER JSON FINAL ---
// -------------------------------------------------------------------------
$fileName = $dataDir . "nouvelle_aquitaine_demain_" . $tomorrow . ".json";
$jsonData = json_encode($finalData, JSON_PRETTY_PRINT);

if (file_put_contents($fileName, $jsonData) !== false) {
    echo "<br><h2>✔️ Succès !</h2>";
    echo "<p>Toutes les données ont été enregistrées dans le fichier : " . htmlspecialchars($fileName) . "</p>";
} else {
    echo "<br><h2>❌ Erreur !</h2>";
    echo "<p>Impossible d'enregistrer le fichier JSON. Vérifiez les permissions du dossier '" . htmlspecialchars($dataDir) . "'.</p>";
}

?>
<?php
// ============================================================================
// --- CONFIGURATION ---
// ============================================================================
set_time_limit(120); // éviter les timeouts sur serveur

// --- Identifiants Atmo ---
$username = "nico_so";
$password = "pey96;98";

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
            ['nom' => 'Lormont', 'insee' => '33249', 'type' => 'Bx-M'],
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

$dataDir = 'datas/';

// ============================================================================
// --- FONCTIONS ---
// ============================================================================
function getToken($username, $password)
{
    $loginUrl = "https://admindata.atmo-france.org/api/login";
    $payload  = json_encode([
        "username" => $username,
        "password" => $password
    ]);

    $ch = curl_init($loginUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Accept: */*"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (!empty($data['token'])) return $data['token'];
    }
    die("❌ Impossible d'obtenir un token. HTTP $httpCode Réponse : $response");
}

function decodeJwt($jwt)
{
    // Un JWT est de la forme header.payload.signature
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    $payload = $parts[1];
    $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    return $decoded;
}

function getAtmoData($url, $headers)
{
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

// ============================================================================
// --- SCRIPT PRINCIPAL ---
// ============================================================================
$today = date("Y-m-d");
$tomorrow = date("Y-m-d", strtotime("+1 day"));

if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);

// 1️⃣ Token
echo "<h1>Obtention du token...</h1>";
$token = getToken($username, $password);
$payload = decodeJwt($token);

if ($payload) {
    $issuedAt = date("Y-m-d H:i:s", $payload['iat']);
    $expiresAt = date("Y-m-d H:i:s", $payload['exp']);
    echo "<p><strong>Token :</strong> <code>" . htmlspecialchars($token) . "</code></p>";
    echo "<p>Date de création (iat) : <strong>$issuedAt</strong></p>";
    echo "<p>Date d'expiration (exp) : <strong>$expiresAt</strong></p>";
} else {
    echo "<p>⚠️ Impossible de décoder le token.</p>";
}

$headers = ["Authorization: Bearer $token"];

// 2️⃣ Collecte des données
echo "<h1>Collecte des données...</h1>";
$finalData = [];

foreach ($departementsRecherches as $numeroDepartement) {
    $departement = $departements[$numeroDepartement];
    foreach ($departement['villes'] as $ville) {
        $insee = $ville['insee'];
        $finalData[$insee] = [
            'ville'       => $ville['nom'],
            'departement' => $departement['nom'],
            'type'        => $ville['type'],
            'qualite_air' => null,
            'pollen'      => null
        ];

        // Qualité de l'air
        $airUrl = "https://admindata.atmo-france.org/api/data/112/"
            . urlencode(json_encode([
                "code_zone" => ["operator" => "=", "value" => $insee],
                "date_ech"  => ["operator" => "=", "value" => $tomorrow]
            ]))
            . "?withGeom=false";
        $airData = getAtmoData($airUrl, $headers);
        if (!isset($airData['error']) && isset($airData['features'][0]['properties'])) {
            $finalData[$insee]['qualite_air'] = $airData['features'][0]['properties'];
        }

        // Pollen
        $pollenUrl = "https://admindata.atmo-france.org/api/data/122/"
            . urlencode(json_encode([
                "code_zone" => ["operator" => "=", "value" => $insee],
                "date_ech"  => ["operator" => "=", "value" => $tomorrow]
            ]))
            . "?withGeom=false";
        $pollenData = getAtmoData($pollenUrl, $headers);
        if (!isset($pollenData['error']) && isset($pollenData['features'][0]['properties'])) {
            $finalData[$insee]['pollen'] = $pollenData['features'][0]['properties'];
        }

        echo "<p>✔️ Données collectées pour " . htmlspecialchars($ville['nom']) . "</p>";
        usleep(500000);
    }
}

// 3️⃣ Sauvegarde du fichier
$fileName = $dataDir . "nouvelle_aquitaine_demain_" . $tomorrow . ".json";
if (file_put_contents($fileName, json_encode($finalData, JSON_PRETTY_PRINT)) !== false) {
    echo "<br><h2>✅ Succès</h2>";
    echo "<p>Fichier enregistré : " . htmlspecialchars($fileName) . "</p>";
} else {
    echo "<br><h2>❌ Erreur</h2>";
    echo "<p>Impossible d'enregistrer le fichier JSON.</p>";
}
include __DIR__ . '/includes/send_mail.php';

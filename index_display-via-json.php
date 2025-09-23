<?php

// --- FICHIER POUR LIRE ET AFFICHER LES DONNÉES DU JSON CENTRALISÉ EN VIGNETTES INTERACTIVES ---

// -------------------------------------------------------------------------
// --- STYLE CSS DE LA PAGE (TEXTE ALIGNÉ À GAUCHE) ---
// -------------------------------------------------------------------------
echo "<style>
    body {
        font-family: Arial, sans-serif;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        padding: 20px;
        background-color: #f4f7f9;
    }
    .vignette-container {
        width: 300px;
        height: 200px;
        perspective: 1000px;
        cursor: pointer;
    }
    .vignette-inner {
        width: 100%;
        height: 100%;
        position: relative;
        text-align: left;
        transition: transform 0.6s;
        transform-style: preserve-3d;
    }
    .vignette-container:hover .vignette-inner {
        transform: rotateY(180deg);
    }
    .vignette-front, .vignette-back {
        position: absolute;
        width: 100%;
        height: 100%;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        padding: 15px;
        box-sizing: border-box;
        text-align: left;
        transition: background-color 0.6s ease;
    }
    .vignette-front {
        /* La couleur de fond sera définie par le PHP en RGBA */
    }
    .vignette-back {
        transform: rotateY(180deg);
        background-color: #fff;
        color: #333;
    }
    h1 {
        text-align: center;
        width: 100%;
        margin-bottom: 20px;
    }
    h2 { margin: 0; font-size: 24px; color: #333; }
    h3 { margin-top: 10px; margin-bottom: 5px; font-size: 16px; color: #555; }
    h4 { margin: 0px; }
    p { margin: 0; font-size: 14px; }
    .data-section {
        margin-bottom: 0px;
    }
    .pollutant-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        list-style-type: none;
        padding: 0;
        margin: 0;
        font-size: 12px;
    }
    .pollutant-list li {
        width: 48%;
        margin-bottom: 5px;
    }
</style>";

// -------------------------------------------------------------------------
// --- DÉFINITION DU CHEMIN DU FICHIER JSON ET DES CORRESPONDANCES ---
// -------------------------------------------------------------------------
$tomorrow = date("Y-m-d", strtotime("+1 day"));
$fileName = "datas/nouvelle_aquitaine_demain_" . $tomorrow . ".json";

$correspondanceQualite = [
    1 => 'Très bon',
    2 => 'Bon',
    3 => 'Moyen',
    4 => 'Médiocre',
    5 => 'Mauvais',
    6 => 'Très mauvais'
];

// Fonction utilitaire pour convertir un code hexadécimal en RGBA
function hex2rgba($color, $opacity = false) {
    $default = 'rgb(0,0,0)';
    if(empty($color)) return $default;

    if ($color[0] == '#') {
        $color = substr($color, 1);
    }

    if (strlen($color) == 6) {
        $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
    } elseif (strlen($color) == 3) {
        $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
    } else {
        return $default;
    }

    $rgb = array_map('hexdec', $hex);

    if ($opacity) {
        if(abs($opacity) > 1) $opacity = 1.0;
        $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
    } else {
        $output = 'rgb(' . implode(",", $rgb) . ')';
    }

    return $output;
}

echo "<h1>Prévisions pour le " . htmlspecialchars($tomorrow) . "</h1>";

// -------------------------------------------------------------------------
// --- LECTURE ET TRAITEMENT DU FICHIER ---
// -------------------------------------------------------------------------
if (!file_exists($fileName)) {
    echo "<p>Le fichier de données n'a pas été trouvé : " . htmlspecialchars($fileName) . "</p>";
} else {
    $jsonData = file_get_contents($fileName);
    $data = json_decode($jsonData, true);
    
    if (empty($data)) {
        echo "<p>Le fichier de données est vide ou invalide.</p>";
    } else {
        foreach ($data as $insee => $villeData) {
            $nomVille = $villeData['ville'];
            $nomDepartement = $villeData['departement'];
            
            $vignetteColor = 'rgba(255,255,255,0.8)';
            if ($villeData['qualite_air'] !== null && isset($villeData['qualite_air']['coul_qual'])) {
                $vignetteColor = hex2rgba($villeData['qualite_air']['coul_qual'], 0.8);
            }
            
            echo "<div class='vignette-container'>";
            echo "  <div class='vignette-inner'>";

            echo "    <div class='vignette-front' style='background-color: " . htmlspecialchars($vignetteColor) . ";'>";
            echo "        <h2>" . htmlspecialchars($nomVille) . "</h2>";
            echo "        <p style='font-size: 14px; color: #666;'>" . htmlspecialchars($nomDepartement) . "</p>";
            echo "    </div>";

            echo "    <div class='vignette-back'>";
            echo "        <p style='font-size: 14px; color: #666; font-weight: bold;'>" . htmlspecialchars($nomVille) . " (" . htmlspecialchars($nomDepartement) . ")</p>";
            
            echo "        <div class='data-section'>";
            // echo "          <h3>Qualité de l'air</h3>";
            if ($villeData['qualite_air'] !== null) {
                $airQualite = $villeData['qualite_air'];
                $couleurAir = $airQualite['coul_qual'];
                $libAir = $airQualite['lib_qual'];
                
                echo "          <div style='display:flex; align-items:center; gap: 10px; font-weight:bold; margin-bottom: 10px; margin-top: 10px;'>";
                echo "              <div style='background-color: " . htmlspecialchars($couleurAir) . "; width: 20px; height: 20px; border-radius: 50%;'></div>";
                echo "              <div>Qualité de l'air : " . htmlspecialchars($libAir) . "</div>";
                echo "          </div>";
                
                echo "          <ul class='pollutant-list'>";
                echo "            <li>NO2: " . (isset($correspondanceQualite[$airQualite['code_no2']]) ? $correspondanceQualite[$airQualite['code_no2']] : 'N/A') . "</li>";
                echo "            <li>O3: " . (isset($correspondanceQualite[$airQualite['code_o3']]) ? $correspondanceQualite[$airQualite['code_o3']] : 'N/A') . "</li>";
                echo "            <li>PM10: " . (isset($correspondanceQualite[$airQualite['code_pm10']]) ? $correspondanceQualite[$airQualite['code_pm10']] : 'N/A') . "</li>";
                echo "            <li>PM2.5: " . (isset($correspondanceQualite[$airQualite['code_pm25']]) ? $correspondanceQualite[$airQualite['code_pm25']] : 'N/A') . "</li>";
                echo "            <li>SO2: " . (isset($correspondanceQualite[$airQualite['code_so2']]) ? $correspondanceQualite[$airQualite['code_so2']] : 'N/A') . "</li>";
                echo "          </ul>";
            } else {
                echo "          <p>Données non disponibles.</p>";
            }
            echo "        </div>";

            // Section Pollen
            echo "        <div class='data-section'>";
            if ($villeData['pollen'] !== null) {
                $pollenData = $villeData['pollen'];
                $pollenLib = $pollenData['lib_qual'];
                // La nouvelle ligne pour afficher les pollens responsables
                $pollenResp = isset($pollenData['pollen_resp']) ? $pollenData['pollen_resp'] : 'Non spécifié';

                echo "          <p style='font-weight:bold; margin-top: 10px;'>Pollen : " . htmlspecialchars($pollenLib) . "</p>";
                echo "          <p style='font-size: 12px; line-height: 17px;'>Pollens responsables : " . htmlspecialchars($pollenResp) . "</p>";

            } else {
                echo "          <p>Données non disponibles.</p>";
            }
            echo "        </div>";

            echo "    </div>";
            echo "  </div>";
            echo "</div>";
        }
    }
}
?>
<?php

/**
 * Plugin Name: love4num Widget
 * Description: Un widget qui génère des numéros de loto à partir de mots, d'une phrase emplie d'amour.
 * Version: 1.0
 * Author: Johannr/LeBonUnivers
 */

require_once(plugin_dir_path(__FILE__) . 'firebase_config.php');


function love4num_styles()
{
    wp_enqueue_style('love4num-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'love4num_styles');


function love4num_scripts()
{
    wp_enqueue_script('love4num-js', plugin_dir_url(__FILE__) . 'js/love4num.js', array('jquery'), '1.0', true);
    wp_localize_script('love4num-js', 'love4numAjaxUrl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'love4num_scripts');

function love4num()
{
    $lotoImgPath = plugin_dir_url(__FILE__) . 'assets/iconlov4.png';
    $euromillionsImgPath = plugin_dir_url(__FILE__) . 'assets/iconlov4_5.png';
    $eurodreamsImgPath = plugin_dir_url(__FILE__) . 'assets/iconlov4_3.png';
    $gommeImgPath = plugin_dir_url(__FILE__) . 'assets/gomme.png'; // Chemin vers l'image gomme

    $content = <<<HTML
<div id="love4num-widget0">
       <!-- <div id="loto-widget"> -->
        <div class="line-input">
            <input type="text" id="phrase-positive" placeholder="Entrez votre phrase positive">
            <img src="{$gommeImgPath}" alt="Effacer" id="reset-btn" />
        </div>
        <div class="line-consigne">
            <p>Choisissez le tirage pour générer vos numéros d'amour :</p>
        </div>
        <div class="game-selection">
            <div class="game-option" data-game="loto"><img src="{$lotoImgPath}" alt="Loto"><span class="game-label"> Classique</span></div>
            <div class="game-option" data-game="euromillions"><img src="{$euromillionsImgPath}" alt="Euromillions"><span class="game-label">Européen</span></div>
            <div class="game-option" data-game="eurodreams"><img src="{$eurodreamsImgPath}" alt="Eurodreams"><span class="game-label">Rêves</span></div>
        </div>
    <!-- </div> -->
    <div id="resultats"></div>
</div>
HTML;
    return $content;
}

add_shortcode('love4num', 'love4num');




function generer_numeros_loto()
{
    $texte = isset($_POST['texte']) ? sanitize_text_field($_POST['texte']) : '';
    if (empty($texte)) {
        echo "Veuillez entrer une phrase ou des mots d'amour avant de générer des numéros.";
        wp_die();
    }

    $jeu = isset($_POST['jeu']) ? $_POST['jeu'] : 'loto'; // Récupère le type de jeu

    $seed = crc32($texte);
    mt_srand($seed);

    switch ($jeu) {
        case 'loto':
            $possibleNumbers = range(1, 49);
            shuffle($possibleNumbers);
            $numeros = array_slice($possibleNumbers, 0, 5);
            $numero_complementaire = rand(1, 10);
            break;
        case 'euromillions':
            $possibleNumbers = range(1, 50);
            shuffle($possibleNumbers);
            $numeros = array_slice($possibleNumbers, 0, 5);
            $possibleEtoiles = range(1, 12); // Correction pour la plage correcte des étoiles
            shuffle($possibleEtoiles);
            $etoiles = array_slice($possibleEtoiles, 0, 2);
            break;
        case 'eurodreams':
            $possibleNumbers = range(1, 40);
            shuffle($possibleNumbers);
            $numeros = array_slice($possibleNumbers, 0, 6);
            $numeroDream = rand(1, 5);
            break;
    }

    // Construction de la réponse selon le jeu
    $response = construire_reponse($jeu, $numeros, isset($etoiles) ? $etoiles : null, $numero_complementaire ?? $numeroDream);

    echo $response;
    echo afficher_statistiques_numeros($jeu, $numeros, $etoiles, $numero_complementaire);
    wp_die();
}

// Nouvelle fonction pour déterminer le titre en fonction du jeu
function titre_jeu($jeu)
{
    switch ($jeu) {
        case 'loto':
            return 'le Loto';
        case 'euromillions':
            return "l'Euromillion"; // Notez l'ajout de l'apostrophe
        case 'eurodreams':
            return "l'Eurodreams"; // De même ici
        default:
            return 'le jeu'; // Valeur par défaut au cas où
    }
}

function construire_reponse($jeu, $numeros, $etoiles = null, $numeroComplementaire)
{
    // Détermine le titre approprié en fonction du jeu
    $titreJeu = titre_jeu($jeu);

    $response = "<div class='titre'>Vos numéros pour $titreJeu</div>" .
        "<div class='numeros $jeu-numeros'>" . implode('</div><div class="numeros ' . $jeu . '-numeros">', $numeros) . "</div>";

    if ($jeu == 'eurodreams') {
        $response .= "<div class='numero-complementaire eurodreams-dream'>$numeroComplementaire</div>";
    } else if ($etoiles !== null) {
        $response .= "<div class='etoiles $jeu-etoiles'>" . implode('</div><div class="etoiles ' . $jeu . '-etoiles">', $etoiles) . "</div>";
    } else {
        $response .= "<div class='numero-complementaire $jeu-complementaire'>$numeroComplementaire</div>";
    }

    return $response;
}





// Hook pour les utilisateurs connectés
add_action('wp_ajax_generer_numeros_loto', 'generer_numeros_loto');

// Hook pour les visiteurs non connectés
add_action('wp_ajax_nopriv_generer_numeros_loto', 'generer_numeros_loto');


// Exemple d'une fonction pour récupérer les statistiques d'un numéro
function get_statistiques_numero($numero, $type, $jeu)
{
    // Mappage entre le type de jeu et le nom de la collection Firestore
    $collections = [
        'loto' => 'lotoStats',
        'euromillions' => 'euromillionsStats',
        'eurodreams' => 'eurodreamsStats',
    ];

    // Sélection de la collection en fonction du jeu
    $collectionName = isset($collections[$jeu]) ? $collections[$jeu] : null;
    if (!$collectionName) {
        // Gérer le cas où le nom de la collection n'est pas trouvé
        return null;
    }

    // Construction de l'URL pour interroger Firestore
    // Remplacez FIREBASE_PROJECT_ID par la valeur réelle stockée dans votre fichier firebase_config.php
    $url = sprintf(
        "https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents/%s/%s_%s",
        'love4num-3ffd4',
        $collectionName,
        $numero,
        $type
    );

    // Effectuer la requête GET
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        // Gérer l'erreur
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Extraire et retourner les informations nécessaires depuis $data
    // Vous devrez adapter cette partie en fonction de la structure exacte de vos données Firestore
    if (isset($data['fields'])) {
        // Exemple d'extraction des données
        return [
            'derniereSortie' => $data['fields']['derniereSortie']['stringValue'],
            'pourcentageDeSorties' => $data['fields']['pourcentageDeSorties']['integerValue'],
            'pourcentageDeSorties' => $data['fields']['pourcentageDeSorties']['stringValue'],
        ];
    }
    // Dans la fonction get_statistiques_numero, juste avant de retourner les données :
    echo "<div style='display:none;'>Statistiques récupérées pour le numéro $numero : " . htmlspecialchars(json_encode($data)) . "</div>";


    return null;
}


function afficher_statistiques_numeros($jeu, $numeros, $etoiles = null, $numeroComplementaire)
{
    // $debugLog = [];
    $response = "<div class='statistiques'>";

    // Ajouter un titre et une explication pour les statistiques
    $response .= "<div class='statistiques-intro'>
                    <h2>Vos statistiques :</h2>
                    <p>Les données sont basées sur les tirages depuis le 4 novembre 2019. La 'Fréquence de sortie' indique la probabilité d'apparition de chaque numéro.</p>
                  </div>";

    // Statistiques pour chaque numéro principal
    foreach ($numeros as $numero) {
        $stat = get_statistiques_numero($numero, "principal", $jeu);
        $response .= "<div class='stat-numero'>Numéro $numero : Fréquence de sortie : " . ($stat['pourcentageDeSorties'] ?? 'N/A') . "%, Dernière sortie - " . ($stat['derniereSortie'] ?? 'N/A') . "</div>";
    }

    // Si étoiles (pour l'Euromillions), afficher leurs statistiques
    if ($jeu == 'euromillions' && $etoiles) {
        foreach ($etoiles as $etoile) {
            $statEtoile = get_statistiques_numero($etoile, "chance", $jeu);
            $response .= "<div class='stat-etoile'>Étoile $etoile : Fréquence de sortie : " . ($statEtoile['pourcentageDeSorties'] ?? 'N/A') . "%, Dernière sortie - " . ($statEtoile['derniereSortie'] ?? 'N/A') . "</div>";
        }
    }

    // Gestion spécifique pour le numéro chance d'EuroDreams
    // if ($jeu == 'eurodreams' && $numeroComplementaire !== null) {
    //     $statChanceEurodreams = get_statistiques_numero($numeroComplementaire, "chance", $jeu);
    //     $response .= "<div class='stat-chance'>Numéro chance $numeroComplementaire : Fréquence de sortie : " . ($statChanceEurodreams['pourcentageDeSorties'] ?? 'N/A') . "%, Dernière sortie - " . ($statChanceEurodreams['derniereSortie'] ?? 'N/A') . "</div>";
    // } elseif ($numeroComplementaire !== null) { // Pour les autres jeux avec numéro complémentaire ou étoile
    //     $statCompl = get_statistiques_numero($numeroComplementaire, "chance", $jeu);
    //     $response .= "<div class='stat-complementaire'>Numéro complémentaire $numeroComplementaire : Fréquence de sortie : " . ($statCompl['pourcentageDeSorties'] ?? 'N/A') . "%, Dernière sortie - " . ($statCompl['derniereSortie'] ?? 'N/A') . "</div>";
    // }


    if ($jeu == 'eurodreams') {
        // Assurez-vous que le numéro chance pour Eurodreams est correctement passé et traité ici.
        // Vérifiez si la variable $numeroComplementaire contient bien la valeur attendue pour Eurodreams.
        $statNumeroChance = get_statistiques_numero($numeroComplementaire, "chance", $jeu);
        $response .= "<div class='stat-numero-chance'>Numéro Chance $numeroComplementaire : Fréquence de sortie : " . ($statNumeroChance['pourcentageDeSorties'] ?? 'N/A') . "%, Dernière sortie - " . ($statNumeroChance['derniereSortie'] ?? 'N/A') . "</div>";
    } elseif ($numeroComplementaire !== null) { // Pour les autres jeux avec numéro complémentaire ou étoile
        $statCompl = get_statistiques_numero($numeroComplementaire, "chance", $jeu);
        $response .= "<div class='stat-complementaire'>Numéro complémentaire $numeroComplementaire : Fréquence de sortie : " . ($statCompl['pourcentageDeSorties'] ?? 'N/A') . "%, Dernière sortie - " . ($statCompl['derniereSortie'] ?? 'N/A') . "</div>";
    }

    $response .= "</div>"; // Fin du bloc statistiques

    

    return $response;
}
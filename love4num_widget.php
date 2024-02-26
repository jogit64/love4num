<?php

/**
 * Plugin Name: love4num Widget
 * Description: Un widget qui génère des numéros de loto à partir de mots, d'une phrase emplie d'amour.
 * Version: 1.0
 * Author: Johannr/LeBonUnivers
 */

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
    $lotoImgPath = plugin_dir_url(__FILE__) . 'assets/loto.png';
    $euromillionsImgPath = plugin_dir_url(__FILE__) . 'assets/euromillions.png';
    $eurodreamsImgPath = plugin_dir_url(__FILE__) . 'assets/dreams.png';

    $content = <<<HTML
<div id="love4num-widget0">
    <h2>Transformez votre amour en numéros de chance</h2>
    <p>Entrez une phrase ou des mots d'amour pour voir comment l'univers transforme votre message en numéros de chance.</p>
    <div id="loto-widget">
        <input type="text" id="phrase-positive" placeholder="Entrez votre phrase positive">
        <p>Choisissez le tirage pour générer vos numéros d'amour :</p>
        <div class="game-selection">
            <div class="game-option" data-game="loto"><img src="{$lotoImgPath}" alt="Loto"><span class="game-label">Loto</span></div>
            <div class="game-option" data-game="euromillions"><img src="{$euromillionsImgPath}" alt="Euromillions"><span class="game-label">Euromillions</span></div>
            <div class="game-option" data-game="eurodreams"><img src="{$eurodreamsImgPath}" alt="Eurodreams"><span class="game-label">Eurodreams</span></div>
        </div>
    </div>
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
    wp_die();
}

function construire_reponse($jeu, $numeros, $etoiles = null, $numeroComplementaire)
{
    $response = "<div class='titre'>Vos numéros pour le $jeu</div>" .
        "<div class='numeros $jeu-numeros'>" . implode('</div><div class="numeros ' . $jeu . '-numeros">', $numeros) . "</div>";

    if ($etoiles !== null) {
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

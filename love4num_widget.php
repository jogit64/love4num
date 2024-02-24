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
            $keys = array_rand(range(1, 49), 5);
            $numeros = array_map(function ($key) {
                return $key + 1;
            }, $keys);
            $numero_complementaire = rand(1, 10);
            $response = "<div class='titre'>Vos numéros pour le Loto</div>" .
                "<div class='numeros loto-numeros'>" . implode('</div><div class="numeros loto-numeros">', $numeros) . "</div>" .
                "<div class='numero-complementaire loto-complementaire'>$numero_complementaire</div>";
            break;
        case 'euromillions':
            $keysNum = array_rand(range(1, 50), 5);
            $numeros = array_map(function ($key) {
                return $key + 1;
            }, $keysNum);
            $keysEtoiles = array_rand(range(1, 12), 2);
            $etoiles = array_map(function ($key) {
                return $key + 1;
            }, $keysEtoiles);
            $response = "<div class='titre'>Vos numéros pour l'Euromillions</div>" .
                "<div class='numeros euromillions-numeros'>" . implode('</div><div class="numeros euromillions-numeros">', $numeros) . "</div>" .
                "<div class='etoiles euromillions-etoiles'>" . implode('</div><div class="etoiles euromillions-etoiles">', $etoiles) . "</div>";
            break;
        case 'eurodreams':
            $keysNum = array_rand(range(1, 40), 6);
            $numeros = array_map(function ($key) {
                return $key + 1;
            }, $keysNum);
            $numeroDream = rand(1, 5);
            $response = "<div class='titre'>Vos numéros pour l'Eurodreams</div>" .
                "<div class='numeros eurodreams-numeros'>" . implode('</div><div class="numeros eurodreams-numeros">', $numeros) . "</div>" .
                "<div class='numero-dream eurodreams-dream'>$numeroDream</div>";
            break;
    }

    echo $response;
    wp_die();
}



// Hook pour les utilisateurs connectés
add_action('wp_ajax_generer_numeros_loto', 'generer_numeros_loto');

// Hook pour les visiteurs non connectés
add_action('wp_ajax_nopriv_generer_numeros_loto', 'generer_numeros_loto');
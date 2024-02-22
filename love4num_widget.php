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
    $content = <<<HTML
<div id="love4num-widget">
    <h2>Votre Amour en Numéros de Loto</h2>
    <p>Entrez une phrase ou des mots d'amour et voyez comment l'univers transforme votre message en numéros de loto chargés d'amour.</p>
    <div id="loto-widget">
        <input type="text" id="phrase-positive" placeholder="Entrez votre phrase positive">
        <button id="generer-numeros">Générer Numéros</button>
    </div>
    <div id="resultats"></div>
</div>
HTML;
    return $content;
}
add_shortcode('love4num', 'love4num');


function generer_numeros_loto()
{
    // Récupérer le texte de la requête
    $texte = isset($_POST['texte']) ? sanitize_text_field($_POST['texte']) : '';

    // Générer un seed à partir du texte
    $seed = crc32($texte);
    mt_srand($seed);

    $numeros = array_rand(range(1, 49), 5);
    $numeros_selectionnes = array_map(function ($index) {
        return $index + 1; // Ajustement car array_rand retourne des clés
    }, $numeros);
    sort($numeros_selectionnes);
    $numero_complementaire = rand(1, 10);

    echo "<div class='titre'>Numéros</div>"; // Titre pour les numéros
    // Boucle pour afficher les numéros
    echo "<div>"; // Conteneur pour les numéros pour une meilleure gestion du style
    foreach ($numeros_selectionnes as $num) {
        echo "<span class='numero'>$num</span> "; // Afficher chaque numéro
    }
    echo "</div>";
    echo "<div class='titre'>Numéro Complémentaire</div><div><span class='numero'>$numero_complementaire</span></div>"; // Titre et affichage pour le numéro complémentaire
    wp_die();
}

// Hook pour les utilisateurs connectés
add_action('wp_ajax_generer_numeros_loto', 'generer_numeros_loto');

// Hook pour les visiteurs non connectés
add_action('wp_ajax_nopriv_generer_numeros_loto', 'generer_numeros_loto');

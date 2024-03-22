jQuery(document).ready(function ($) {
  $(".game-option").click(function () {
    var texte = $("#phrase-positive").val().trim(); // Utilisez .trim() pour enlever les espaces blancs avant et après le texte

    if (texte === "") {
      alert(
        "Veuillez entrer une phrase ou des mots d'amour avant de générer des numéros."
      ); // Affiche une alerte, ou vous pouvez choisir d'afficher le message différemment
      return; // Sortie précoce de la fonction pour ne pas continuer avec la requête AJAX
    }

    var gameType = $(this).data("game"); // Récupère le type de jeu de l'attribut data-game
    var data = {
      action: "generer_numeros_loto",
      texte: texte,
      jeu: gameType,
    };
    $.post(love4numAjaxUrl, data, function (response) {
      $("#resultats").html(response);
    });
  });

  $("#reset-btn").click(function () {
    $("#phrase-positive").val(""); // Réinitialise le champ de texte
    $("#resultats").html(""); // Efface les résultats affichés
  });
});

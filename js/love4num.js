jQuery(document).ready(function ($) {
  $("#generer-numeros").click(function () {
    var data = {
      action: "generer_numeros_loto",
      texte: $("#phrase-positive").val(),
    };
    $.post(love4numAjaxUrl, data, function (response) {
      $("#resultats").html(response);
    });
  });
});

<?php


function calculateExactDrawsSinceLastOut($dateString, $gameType)
{
    if (empty($dateString)) {
        error_log("calculateExactDrawsSinceLastOut: dateString is undefined or empty.");
        return 0;
    }

    // Convertir la date du format "jj/mm/aa" au format DateTime PHP
    $dateParts = explode('/', $dateString);
    $convertedDateString = '20' . $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
    $lastOutDate = new DateTime($convertedDateString);
    $today = new DateTime();
    $draws = 0;

    // Définir les jours de tirage en fonction du type de jeu
    $drawDays = [];
    switch ($gameType) {
        case 'loto':
            $drawDays = [1, 3, 6]; // Lundi = 1, Mercredi = 3, Samedi = 6
            break;
        case 'euromillions':
            $drawDays = [2, 5]; // Mardi = 2, Vendredi = 5
            break;
        case 'eurodreams':
            $drawDays = [1, 4]; // Lundi = 1, Jeudi = 4
            break;
        default:
            error_log("calculateExactDrawsSinceLastOut: Invalid gameType provided.");
            return 0;
    }

    while ($lastOutDate <= $today) {
        if (in_array((int)$lastOutDate->format('N'), $drawDays)) {
            $draws++;
        }
        $lastOutDate->modify('+1 day');
    }

    return $draws;
}

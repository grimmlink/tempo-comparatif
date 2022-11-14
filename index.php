<?php

$tempoHistoUrl = 'https://selectra.info/api/tempo/calendar';
$tarifBase = 0.1659;
$consos = [];
$rawConfigHC = '0,1740;1400-1600-0,1402;0000-0600-0,1402';

$sumTDE = $sumTempo = $sumHCHP = 0;

if (($handle = fopen('conso.csv', "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ";")) !== false) {
        list($date, $conso) = $data;

        $sourceDate = trim(str_replace("﻿", '', $date));
        $newDate = DateTime::createFromFormat(DATE_ATOM, $sourceDate);

        $consos[] = [
            'date' => $newDate,
            'val' => $conso,
        ];
    }
    fclose($handle);
}

$tempoHistoJson = json_decode(file_get_contents('tempo.json'), true);
foreach ($tempoHistoJson['dates'] as $item) {
    $tempoHisto[$item['date']] = $item['couleur'];
}

$configsHC = explode(';', $rawConfigHC);
//var_dump($configsHC); exit;
$tarifHP = array_shift($configsHC);
$periodsHC = [];
foreach ($configsHC as $configHC) {
    list($start, $end, $tarif) = explode('-', $configHC);
    $periodsHC[] = [
        'start' => (int)$start,
        'end' => (int)$end,
        'tarif' => str_replace(',', '.', $tarif),
    ];
}

$comparatif = [];
$row = 0;
while ($row < count($consos)) {
    $interval = 30;
    $currentDate = $consos[$row]['date'];
    if (isset($consos[$row + 1])) {
        /** @var DateInterval $period */
        $period = $consos[$row + 1]['date']->diff($currentDate);
        $interval = $period->format('%i');
    }

    $divisionHoraire = (60 / $interval);

    // Base
    $priceBase = (int)$consos[$row]['val'] * ($tarifBase / 1000) / $divisionHoraire;

    // Tempo
    $isTempoHC = (int)$currentDate->format('Hi') > 2200 || (int)$currentDate->format('Hi') < 600;
    $couleurTempo = $tempoHisto[$currentDate->format('Y-m-d')] ?? 'TEMPO_BLEU';
    $tarifTempo = 0.1272;
    if ($couleurTempo === 'TEMPO_BLEU') {
        $tarifTempo = $isTempoHC ? 0.0862 : 0.1272;
    } elseif ($couleurTempo === 'TEMPO_BLANC') {
        $tarifTempo = $isTempoHC ? 0.1112 : 0.1653;
    } elseif ($couleurTempo === 'TEMPO_ROUGE') {
        $tarifTempo = $isTempoHC ? 0.1222 : 0.5486;
    }
    $priceTempo = (int)$consos[$row]['val'] * ($tarifTempo / 1000) / $divisionHoraire;

    // HC/HP
    $isHC = false;
    $tarifHCHP = $tarifHP;
    foreach ($periodsHC as $periodHC) {
        if ((int)$currentDate->format('Hi') > $periodHC['start'] || (int)$currentDate->format('Hi') < $periodHC['end']) {
            $isHC = true;
            $tarifHCHP = $periodHC['tarif'];
        }
    }
    $priceHCHP = (int)$consos[$row]['val'] * ($tarifHCHP / 1000) / $divisionHoraire;

    $comparatif[] = [
        $currentDate->format(DATE_ATOM),
        $tarifBase,
        $priceBase,
        $couleurTempo,
        $tarifTempo,
        $priceTempo,
        $isHC ? 'oui' : 'non',
        $tarifHCHP,
        $priceHCHP,
    ];

    $sumTDE += $priceBase;
    $sumTempo += $priceTempo;
    $sumHCHP += $priceHCHP;

    $row++;
}

echo "Somme Base : $sumTDE\n";
echo "Somme TEMPO : $sumTempo\n";
echo "Somme HCHP : $sumHCHP\n";

$fp = fopen('comparatif.csv', 'w');

foreach ($comparatif as $fields) {
    fputcsv($fp, $fields, ';');
}

fclose($fp);

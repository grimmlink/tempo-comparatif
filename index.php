<?php

$tarifsBase = [
    'abo' => [
        6  =>  13.86,
        9  =>  17.48,
        12 =>  21.14,
        15 =>  24.55,
        18 =>  27.81,
        24 =>  35.23,
        30 =>  42.69,
        36 =>  49.23,
    ],
    'base' => 0.2016
];

$tarifsHCHP = [
    'abo' => [
        6  => 14.47,
        9  => 18.35,
        12 => 22.13,
        15 => 25.71,
        18 => 29.40,
        24 => 37.12,
        30 => 44.12,
        36 => 51.30,
    ],
    'hchp' => [
        'hp' => 0.2146,
        'hc' => 0.1696,
    ],
];

$tarifsTempo = [
    'abo' => [
        6  => 14.40,
        9  => 18.09,
        12 => 21.82,
        15 => 25.30,
        18 => 30.37,
        30 => 43.42,
        36 => 51.03,
    ],
    'TEMPO_BLEU' => [
        'hp' => 0.1552,
        'hc' => 0.1288,
    ],
    'TEMPO_BLANC' => [
        'hp' => 0.1792,
        'hc' => 0.1447,
    ],
    'TEMPO_ROUGE' => [
        'hp' => 0.6586,
        'hc' => 0.1518,
    ],
];

$tarifsZenFlex = [
    'abo' => [
        6  => 13.09,
        9  => 16.82,
        12 => 20.28,
        15 => 23.57,
        18 => 26.84,
        24 => 33.70,
        30 => 39.94,
        36 => 46.24,
    ],
    'eco' => [
        'hp' => 0.2700,
        'hc' => 0.1795,
    ],
    'sobriete' => [
        'hp' => 0.7562,
        'hc' => 0.2700,
    ],
];

$tarifsZenFixe = [
    'abo' => [
        6  => 12.67,
        9  => 15.89,
        12 => 19.16,
        15 => 22.21,
        18 => 25.24,
        24 => 31.96,
        30 => 37.68,
        36 => 44.43,
    ],
    'hchp' => [
        'hp' => 0.1876,
        'hc' => 0.1456,
    ],
    'base' => 0.1753,
];

$puissance = $_POST['puissance'] ?? 6;

$aboBase = $tarifsBase['abo'][$puissance];
$tarifBase = $tarifsBase['base'];

$aboHCHP = $tarifsHCHP['abo'][$puissance];
$tarifHP = $tarifsHCHP['hchp']['hp'];
$tarifHC = $tarifsHCHP['hchp']['hc'];
$periodHC1 = $_POST['periodHC1'] ?? '1400-1600';
$periodHC2 = $_POST['periodHC2'] ?? '0000-0600';

$aboTempo = $tarifsTempo['abo'][$puissance];
$aboZenFlex = $tarifsZenFlex['abo'][$puissance];
$aboZenFixe = $tarifsZenFixe['abo'][$puissance];
$optionZenFixe = $_POST['optionZenFixe'] ?? 'hchp';

if (isset($_FILES['conso_file']) && file_exists($_FILES['conso_file']['tmp_name'])) {
    $consos = [];

    $sumBase = $sumTempo = $sumHCHP = $sumZenFlex = $sumZenFixe = 0;
    $nbMonths = 0;
    $prevMonth = null;
    $totalConso = 0;

    // Histo Tempo
    $tempoHistoJson = json_decode(file_get_contents('https://raw.githubusercontent.com/JbPasquier/tarifelec-vjs/main/tempo.json'),
        true);
    foreach ($tempoHistoJson['dates'] as $item) {
        $tempoHisto[$item['date']] = $item['couleur'];
    }
    // Histo ZenFlex
    $zenflexHisto = [];
    $zenflexHistoJson = json_decode(file_get_contents('https://raw.githubusercontent.com/masfaraud/ZenFlex/refs/heads/master/data.json'),
        true);
    foreach ($zenflexHistoJson['sobriete'] as $dateText) {
        $dateParse = date_parse($dateText);
        $date = $dateParse['year'] . '-' . $dateParse['month'] . '-' . $dateParse['day'];
        $zenflexHisto[$date] = 'sobriete';
    }

    // Prepare conso
    if (($handle = fopen($_FILES['conso_file']['tmp_name'], "r")) !== false) {
        $hasHeader = false;
        $line = 0;
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            if ($line === 0 && $data[0] == '﻿Identifiant PRM') {
                $hasHeader = true;
            }

            if (!$hasHeader || $line > 2) {
                list($date, $value) = $data;

                $sourceDate = trim(str_replace("﻿", '', $date));
                $newDate = DateTime::createFromFormat(DATE_ATOM, $sourceDate);

                $month = $newDate->format('n');
                if (!$prevMonth || $prevMonth !== $month) {
                    $prevMonth = $month;
                    $nbMonths++;
                }

                $consos[$newDate->format('U')] = [
                    'date' => $newDate,
                    'val' => $value,
                ];
            }

            $line++;
        }
        fclose($handle);
    }
    ksort($consos);
    $consos = array_values($consos);

    $firstDay = $consos[0]['date'];
    $lastDay = $consos[count($consos) - 1]['date'];

    // HC
    $periodsHC = [];
    list($start, $end) = explode('-', $periodHC1);
    $periodsHC[] = [
        'start' => (int)$start,
        'end' => (int)$end,
    ];
    if ($periodHC2 !== '') {
        list($start, $end, $tarif) = explode('-', $periodHC2);
        $periodsHC[] = [
            'start' => (int)$start,
            'end' => (int)$end,
        ];
    }

    $comparatif = [];
    $row = 0;
    while ($row < count($consos)) {
        $interval = 30;
        /** @var DateTime $currentDate */
        $currentDate = $consos[$row]['date'];
        $currentHour = (int)$currentDate->format('Hi');

        if (isset($consos[$row + 1])) {
            /** @var DateInterval $period */
            $period = $consos[$row + 1]['date']->diff($currentDate);
            $interval = (int)$period->format('%i');

            if ($interval === 0) {
                $interval = (int)$period->format('%h') * 60;
            }

            if ($interval === 0) {
                echo 'Interval of 0 on line '.$row.'<br />';
                echo '<pre>';
                var_dump($consos[$row]['date'], $consos[$row + 1]['date']);
                exit;
            }
        }

        $divisionHoraire = (60 / $interval);

        $valueKWH = (int)$consos[$row]['val'] / 1000 / $divisionHoraire;

        // Base
        $priceBase = $valueKWH * $tarifBase;

        // Tempo
        $tempoDate = (int)$currentDate->format('Hi') > 600 ? (clone $currentDate) : (clone $currentDate)->sub(new DateInterval('P1D'));
        $tempoPeriod = $currentHour > 2200 || $currentHour <= 600 ? 'hc' : 'hp';
        $couleurTempo = $tempoHisto[$tempoDate->format('Y-n-j')] ?? 'TEMPO_BLEU';
        $tarifTempo = $tarifsTempo[$couleurTempo][$tempoPeriod];
        $priceTempo = $valueKWH * $tarifTempo;

        // ZenFlex
        $zenFlexDate = clone $currentDate;
        $zenFlexPeriod = ($currentHour > 800 && $currentHour <= 1300) || ($currentHour > 1800 && $currentHour <= 2000) ? 'hp' : 'hc';
        $couleurZenFlex = array_key_exists($zenFlexDate->format('Y-m-d'), $zenflexHisto) ? 'sobriete' : 'eco';
        $tarifZenFlex = $tarifsZenFlex[$couleurZenFlex][$zenFlexPeriod];
        $priceZenFlex = $valueKWH * $tarifZenFlex;

//        echo $currentDate->format('Y-n-j H:i') . ' / ' . $tempoPeriod . ' / ' . $couleurTempo . ' / ' . $tarifTempo . '<br />';

        // HC/HP / ZenFixe
        $isHC = false;
        $tarifHCHP = $tarifHP;
        foreach ($periodsHC as $periodHC) {
            if (
                ($periodHC['start'] < $periodHC['end'] && $currentHour > $periodHC['start'] && $currentHour <= $periodHC['end']) // period in the same day
                 || ($periodHC['start'] > $periodHC['end'] && ( $currentHour > $periodHC['start'] || $currentHour <= $periodHC['end'] )) // period across 2 days
            ) {
                $isHC = true;
                $tarifHCHP = $tarifHC;
            }
        }
        $priceHCHP = $valueKWH * $tarifHCHP;


        // ZenFixe
        if ($optionZenFixe === 'base') {
            $tarifZenFixe = $tarifsZenFixe['base'];
            $priceZenFixe = $valueKWH * $tarifZenFixe;
        } else {
            $tarifZenFixe = $isHC ? $tarifsZenFixe['hchp']['hc'] : $tarifsZenFixe['hchp']['hp'];
            $priceZenFixe = $valueKWH * $tarifZenFixe;
        }

        $comparatif[] = [
            $currentDate->format(DATE_ATOM),
            $valueKWH,
            $tarifBase,
            $priceBase,
            $couleurTempo,
            $tarifTempo,
            $priceTempo,
            $couleurZenFlex,
            $tarifZenFlex,
            $priceZenFlex,
            $isHC ? 'oui' : 'non',
            $tarifHCHP,
            $priceHCHP,
            $tarifZenFixe,
            $priceZenFixe,
        ];

        $sumBase += $priceBase;
        $sumTempo += $priceTempo;
        $sumZenFlex += $priceZenFlex;
        $sumHCHP += $priceHCHP;
        $sumZenFixe += $priceZenFixe;

        $totalConso += $valueKWH * 1000;

        $row++;
    }
//    var_dump($comparatif);
//    exit;

    $totalBase = $sumBase + $aboBase * $nbMonths;
    $totalTempo = $sumTempo + $aboTempo * $nbMonths;
    $totalZenFlex = $sumZenFlex + $aboZenFlex * $nbMonths;
    $totalHCHP = $sumHCHP + $aboHCHP * $nbMonths;
    $totalZenFixe = $sumZenFixe + $aboZenFixe * $nbMonths;

    if (isset($_POST['export']) && $_POST['export'] === 'oui') {
        $fp = fopen('php://memory', 'w');
        fputcsv($fp, [
            'Date',
            'Consommation en kWh',
            'Tarif kWh Base',
            'Prix Base',
            'Couleur Tempo',
            'Tarif kWh Tempo',
            'Total Tempo',
            'Couleur ZenFlex',
            'Tarif kWh ZenFlex',
            'Total ZenFlex',
            'HC?',
            'Tarif kWh HC/HP',
            'Total HC/HP',
            'Tarif kWh ZenFixe',
            'Total ZenFixe',
        ], ';');

        foreach ($comparatif as $fields) {
            fputcsv($fp, $fields, ';');
        }
        fseek($fp, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="detail.csv";');
        fpassthru($fp);
        fclose($fp);
        exit;
    } else {
        $totalTable = '
            <h3>
                Période du '.$consos[0]['date']->format('d/m/Y').' au '.$consos[count($consos) - 1]['date']->format('d/m/Y').'
                 - Consommation totale : '.($totalConso / 1000).' kWh
            </h3>
            <table class="table table-striped">
                <tr>
                    <th></th>
                    <th>Abonnement ('.$nbMonths.' mois)</th>
                    <th>Consommation période</th>
                    <th>Total période</th>
                    <th>Economie</th>
                </tr>
                <tr>
                    <th>Base</th>
                    <td>'.number_format($aboBase * $nbMonths, 2).'€</td>
                    <td>'.number_format($sumBase, 2).'€</td>
                    <td>'.number_format($totalBase, 2).'€</td>
                    <td></td>
                </tr>
                <tr>
                    <th>TEMPO</th>
                    <td>'.number_format($aboTempo * $nbMonths, 2).'€</td>
                    <td>'.number_format($sumTempo, 2).'€</td>
                    <td>'.number_format($totalTempo, 2).'€</td>
                    <td>'.number_format(100 - (100 * $totalTempo / $totalBase), 2).'%</td>
                </tr>
                <tr>
                    <th>ZenFlex</th>
                    <td>'.number_format($aboZenFlex * $nbMonths, 2).'€</td>
                    <td>'.number_format($sumZenFlex, 2).'€</td>
                    <td>'.number_format($totalZenFlex, 2).'€</td>
                    <td>'.number_format(100 - (100 * $totalZenFlex / $totalBase), 2).'%</td>
                </tr>
                <tr>
                    <th>HC/HP</th>
                    <td>'.number_format($aboHCHP * $nbMonths, 2).'€</td>
                    <td>'.number_format($sumHCHP, 2).'€</td>
                    <td>'.number_format($totalHCHP, 2).'€</td>
                    <td>'.number_format(100 - (100 * $totalHCHP / $totalBase), 2).'%</td>
                </tr>
                <tr>
                    <th>ZenFixe</th>
                    <td>'.number_format($aboZenFixe * $nbMonths, 2).'€</td>
                    <td>'.number_format($sumZenFixe, 2).'€</td>
                    <td>'.number_format($totalZenFixe, 2).'€</td>
                    <td>'.number_format(100 - (100 * $totalZenFixe / $totalBase), 2).'%</td>
                </tr>
            </table>
            ';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comparatif conso electrique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

<div class="container">
    <h1>Comparatif de facture Base / HC / Tempo / ZenFlex / ZenFixe</h1>

    <form action="/" method="POST" enctype="multipart/form-data">

        <fieldset>
            <legend>PERSONALISATION</legend>
            <div class="row mb-3">
                <div class="col">
                    <label for="puissance" class="form-label">Puissance (kVA)</label>
                    <select class="form-control" name="puissance" id="puissance">
                        <option value="6" <?php echo $puissance === 6 ? 'selected' : '' ?>>6</option>
                        <option value="9" <?php echo $puissance === 9 ? 'selected' : '' ?>>9</option>
                        <option value="12" <?php echo $puissance === 12 ? 'selected' : '' ?>>12</option>
                        <option value="15" <?php echo $puissance === 15 ? 'selected' : '' ?>>15</option>
                        <option value="18" <?php echo $puissance === 18 ? 'selected' : '' ?>>18</option>
                        <option value="30" <?php echo $puissance === 30 ? 'selected' : '' ?>>30</option>
                        <option value="36" <?php echo $puissance === 36 ? 'selected' : '' ?>>36</option>
                    </select>
                </div>
                <div class="col">
                    <label for="periodHC1" class="form-label">Période HC 1</label>
                    <input type="text" class="form-control" name="periodHC1" id="periodHC1" value="<?php
                    echo $periodHC1; ?>" placeholder="<?php echo $periodHC1; ?>">
                    <p class="small">Format : <code>début[hhmm]-fin[hhmm]</code>.<br/>Exemple :
                        <code><?php echo $periodHC1; ?></code></p>
                </div>
                <div class="col">
                    <label for="periodHC2" class="form-label">Période HC 2</label>
                    <input type="text" class="form-control" name="periodHC2" id="periodHC2" value="<?php
                    echo $periodHC2; ?>" placeholder="<?php echo $periodHC2; ?>">
                    <p class="small">Format : <code>début[hhmm]-fin[hhmm]</code>.<br/>Exemple :
                        <code><?php echo $periodHC2; ?></code></p>
                </div>
                <div class="col">
                    <label for="aboZenFixe" class="form-label">Option ZenFixe</label>
                    <select class="form-control" name="optionZenFixe" id="optionZenFixe">
                        <option value="hchp" <?php echo $optionZenFixe === 'hchp' ? 'selected' : '' ?>>HC/HP</option>
                        <option value="base" <?php echo $optionZenFixe === 'base' ? 'selected' : '' ?>>Base</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Consommation</legend>
            <div class="row mb-3">
                <div class="col">
                    <label for="conso_file" class="form-label">Fichier de consommations <b>horaires</b> (CSV)</label>
                    <input type="file" class="form-control" name="conso_file" id="conso_file">
                    <p class="small">
                        <i>Fichier CSV à récupérer sur <a href="https://mon-compte-particulier.enedis.fr/mes-telechargements-mesures" target="_blank">Enedis.</a><br/>
                        Pensez à collecter les consommations horaires sur <a href="https://mon-compte-particulier.enedis.fr/donnees/" target="_blank"> Enedis !</a></i>
                    </p>
                </div>
            </div>
        </fieldset>

        <div class="mb-3">
            <label for="export">
                <input type="checkbox" id="export" name="export" value="oui"> Télécharger le détail en CSV
            </label>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary mb-3">Calculer</button>
        </div>
    </form>

    <?php
    if (isset($totalTable)) {
        echo $totalTable;
    } ?>


    <p class="text-end small"><a href="https://github.com/grimmlink/tempo-comparatif">Voir le code sur GitHub</a></p>
</div>

</body>
</html>


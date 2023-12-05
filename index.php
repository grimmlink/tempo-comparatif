<?php

$tarifsTempo = [
    'abo' => [
        6 => 153.60 / 12,
        9 => 192 / 12,
        12 => 231.48 / 12,
        15 => 267.60 / 12,
        18 => 303.48 / 12,
        30 => 457.56 / 12,
        36 => 531.36 / 12,
    ],
    'TEMPO_BLEU' => [
        'hp' => 0.1369,
        'hc' => 0.1056,
    ],
    'TEMPO_BLANC' => [
        'hp' => 0.1654,
        'hc' => 0.1246,
    ],
    'TEMPO_ROUGE' => [
        'hp' => 0.7324,
        'hc' => 0.1328,
    ],
];

$tarifsZenFlex = [
    'abo' => [
        6 => 13.03,
        9 => 16.55,
        12 => 19.97,
        15 => 23.24,
        18 => 26.48,
        24 => 33.28,
        30 => 39.46,
        36 => 45.72,
    ],
    'eco' => [
        'hp' => 0.2460,
        'hc' => 0.1464,
    ],
    'sobriete' => [
        'hp' => 0.7324,
        'hc' => 0.2460,
    ],
];

$tarifBase = $_POST['tarifBase'] ?? 0.2276;
$aboBase = $_POST['aboBase'] ?? 12.44;
$tarifHP = $_POST['tarifHP'] ?? 0.2460;
$tarifHC1 = $_POST['tarifHC1'] ?? '1400-1600-0.1828';
$tarifHC2 = $_POST['tarifHC2'] ?? '0000-0600-0.1828';
$aboHCHP = $_POST['aboHCHP'] ?? 12.85;
$aboTempo = $_POST['aboTempo'] ?? $tarifsTempo['abo'][6];
$aboZenFlex = $_POST['aboZenFlex'] ?? $tarifsZenFlex['abo'][6];

if (isset($_FILES['conso_file']) && file_exists($_FILES['conso_file']['tmp_name'])) {
    $consos = [];

    $sumBase = $sumTempo = $sumHCHP = $sumZenFlex = 0;
    $nbMonths = 0;
    $prevMonth = null;
    $totalConso = 0;

    // Histo Tempo
    $tempoHistoJson = json_decode(file_get_contents('https://raw.githubusercontent.com/grimmlink/tempo-comparatif/master/tempo.json'),
        true);
    foreach ($tempoHistoJson['dates'] as $item) {
        $tempoHisto[$item['date']] = $item['couleur'];
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
    list($start, $end, $tarif) = explode('-', $tarifHC1);
    $periodsHC[] = [
        'start' => (int)$start,
        'end' => (int)$end,
        'tarif' => floatval(str_replace(',', '.', $tarif)),
    ];
    if ($tarifHC2 !== '') {
        list($start, $end, $tarif) = explode('-', $tarifHC2);
        $periodsHC[] = [
            'start' => (int)$start,
            'end' => (int)$end,
            'tarif' => floatval(str_replace(',', '.', $tarif)),
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
        $couleurZenFlex = $couleurTempo === 'TEMPO_ROUGE' ? 'sobriete' : 'eco';
        $tarifZenFlex = $tarifsZenFlex[$couleurZenFlex][$zenFlexPeriod];
        $priceZenFlex = $valueKWH * $tarifZenFlex;

//        echo $currentDate->format('Y-n-j H:i') . ' / ' . $tempoPeriod . ' / ' . $couleurTempo . ' / ' . $tarifTempo . '<br />';

        // HC/HP
        $isHC = false;
        $tarifHCHP = $tarifHP;
        foreach ($periodsHC as $periodHC) {
            if (
                ($periodHC['start'] < $periodHC['end'] && $currentHour > $periodHC['start'] && $currentHour <= $periodHC['end']) // period in the same day
                 || ($periodHC['start'] > $periodHC['end'] && ( $currentHour > $periodHC['start'] || $currentHour <= $periodHC['end'] )) // period across 2 days
            ) {
                $isHC = true;
                $tarifHCHP = $periodHC['tarif'];
            }
        }
        $priceHCHP = $valueKWH * $tarifHCHP;

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
        ];

        $sumBase += $priceBase;
        $sumTempo += $priceTempo;
        $sumZenFlex += $priceZenFlex;
        $sumHCHP += $priceHCHP;

        $totalConso += $valueKWH * 1000;

        $row++;
    }
//    exit;

    $totalBase = $sumBase + $aboBase * $nbMonths;
    $totalTempo = $sumTempo + $aboTempo * $nbMonths;
    $totalZenFlex = $sumZenFlex + $aboZenFlex * $nbMonths;
    $totalHCHP = $sumHCHP + $aboHCHP * $nbMonths;

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
    <h1>Comparatif de facture Base / HC / Tempo</h1>

    <form action="/" method="POST" enctype="multipart/form-data">
        <fieldset>
            <legend>BASE</legend>
            <div class="row mb-3">
                <div class="col">
                    <label for="aboBase" class="form-label">Abonnement mensuel base</label>
                    <input type="text" class="form-control" name="aboBase" id="aboBase" value="<?php
                    echo $aboBase; ?>" placeholder="15">
                </div>
                <div class="col">
                    <label for="tarifBase" class="form-label">Tarif base</label>
                    <input type="text" class="form-control" name="tarifBase" id="tarifBase" value="<?php
                    echo $tarifBase; ?>" placeholder="0.1659">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>HC/HP</legend>
            <div class="row mb-3">
                <div class="col">
                    <label for="aboHCHP" class="form-label">Abonnement HC/HP</label>
                    <input type="text" class="form-control" name="aboHCHP" id="aboHCHP" value="<?php
                    echo $aboHCHP; ?>" placeholder="15">
                </div>
                <div class="col">
                    <label for="tarifHP" class="form-label">Tarif HP</label>
                    <input type="text" class="form-control" name="tarifHP" id="tarifHP" value="<?php
                    echo $tarifHP; ?>" placeholder="<?php echo $tarifHP; ?>">
                </div>
                <div class="col">
                    <label for="tarifHC1" class="form-label">Tarif HC 1</label>
                    <input type="text" class="form-control" name="tarifHC1" id="tarifHC1" value="<?php
                    echo $tarifHC1; ?>" placeholder="<?php echo $tarifHC1; ?>">
                    <p class="small">Format : <code>début[hhmm]-fin[hhmm]-tarif</code>.<br/>Exemple :
                        <code><?php echo $tarifHC1; ?></code></p>
                </div>
                <div class="col">
                    <label for="tarifHC2" class="form-label">Tarif HC 2</label>
                    <input type="text" class="form-control" name="tarifHC2" id="tarifHC2" value="<?php
                    echo $tarifHC2; ?>" placeholder="<?php echo $tarifHC2; ?>">
                    <p class="small">Format : <code>début[hhmm]-fin[hhmm]-tarif</code>.<br/>Exemple :
                        <code><?php echo $tarifHC2; ?></code></p>
                </div>
            </div>
        </fieldset>

        <div class="row mb-3">
            <div class="col">
                <fieldset>
                    <legend>Tempo</legend>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="aboTempo" class="form-label">Abonnement Tempo</label>
                            <input type="text" class="form-control" name="aboTempo" id="aboTempo" value="<?php
                            echo $aboTempo; ?>" placeholder="15">
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="col">
                <fieldset>
                    <legend>ZenFlex</legend>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="aboZenFlex" class="form-label">Abonnement ZenFlex</label>
                            <input type="text" class="form-control" name="aboZenFlex" id="aboZenFlex" value="<?php
                            echo $aboZenFlex; ?>" placeholder="15">
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        <fieldset>
            <legend>Consommation</legend>
            <div class="row mb-3">
                <div class="col">
                    <label for="conso_file" class="form-label">Fichier de consommations <b>horaires</b> (CSV)</label>
                    <input type="file" class="form-control" name="conso_file" id="conso_file">
                    <p class="small">
                        <i>Fichier CSV à récupéré sur <a href="https://mon-compte-particulier.enedis.fr/mes-telechargements-mesures" target="_blank">Enedis.</a><br/>
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


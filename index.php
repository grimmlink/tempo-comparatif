<?php

$tarifBase = $_POST['tarifBase'] ?? 0.2062;
$aboBase = $_POST['aboBase'] ?? 11.93;
$tarifHP = $_POST['tarifHP'] ?? 0.2228;
$tarifHC1 = $_POST['tarifHC1'] ?? '1400-1600-0.1615';
$tarifHC2 = $_POST['tarifHC2'] ?? '';
$aboHCHP = $_POST['aboHCHP'] ?? 12.35;
$aboTempo = $_POST['aboTempo'] ?? 12.28;

$tarifsTempo = [
    [
        'start' => DateTime::createFromFormat('Y-m-d H:i:s', '01-02-2023 00:00:00'),
        'end' => DateTime::createFromFormat('Y-m-d H:i:s', '01-10-2023 00:00:00'),
        'tarifs' => [
            'abo' => [
                6 => 147.36 / 12,
                9 => 183.96 / 12,
                12 => 225.36 / 12,
                15 => 255.24 / 12,
                18 => 287.76 / 12,
                24 => 432.72 / 12,
            ],
            'TEMPO_BLEU' => [
                'hp' => 0.1249,
                'hc' => 0.0970,
            ],
            'TEMPO_BLANC' => [
                'hp' => 0.1508,
                'hc' => 0.1140,
            ],
            'TEMPO_ROUGE' => [
                'hp' => 0.6712,
                'hc' => 0.1216,
            ],
        ],
    ],
//    [
//        'start' => DateTime::createFromFormat('Y-m-d H:i:s', '01-08-2022 00:00:00'),
//        'end' => DateTime::createFromFormat('Y-m-d H:i:s', '01-02-2023 00:00:00'),
//        'tarifs' => [
//            'abo' => [
//                9 => 187.32 / 12,
//                12 => 223.20 / 12,
//                15 => 235.20 / 12,
//                18 => 263.40 / 12,
//                24 => 370.44 / 12,
//            ],
//            'TEMPO_BLEU' => [
//                'hp' => 0.1277,
//                'hc' => 0.0837,
//            ],
//            'TEMPO_BLANC' => [
//                'hp' => 0.1949,
//                'hc' => 0.1237,
//            ],
//            'TEMPO_ROUGE' => [
//                'hp' => 0.3250,
//                'hc' => 0.1341,
//            ],
//        ],
//    ],
//    [
//        'start' => DateTime::createFromFormat('Y-m-d H:i:s', '01-02-2022 00:00:00'),
//        'end' => DateTime::createFromFormat('Y-m-d H:i:s', '01-08-2022 00:00:00'),
//        'tarifs' => [
//            'abo' => [
//                9 => 144.48 / 12,
//                12 => 173.64 / 12,
//                15 => 199.56 / 12,
//                18 => 224.40 / 12,
//                24 => 336.60 / 12,
//            ],
//            'TEMPO_BLEU' => [
//                'hp' => 0.0984,
//                'hc' => 0.0642,
//            ],
//            'TEMPO_BLANC' => [
//                'hp' => 0.1301,
//                'hc' => 0.0850,
//            ],
//            'TEMPO_ROUGE' => [
//                'hp' => 0.4495,
//                'hc' => 0.0942,
//            ],
//        ],
//    ],
//    [
//        'start' => DateTime::createFromFormat('Y-m-d H:i:s', '01-08-2021 00:00:00'),
//        'end' => DateTime::createFromFormat('Y-m-d H:i:s', '01-02-2022 00:00:00'),
//        'tarifs' => [
//            'abo' => [
//                9 => 145.92 / 12,
//                12 => 174.96 / 12,
//                15 => 200.88 / 12,
//                18 => 225.72 / 12,
//                24 => 337.92 / 12,
//            ],
//            'TEMPO_BLEU' => [
//                'hp' => 0.0924,
//                'hc' => 0.0701,
//            ],
//            'TEMPO_BLANC' => [
//                'hp' => 0.1153,
//                'hc' => 0.0852,
//            ],
//            'TEMPO_ROUGE' => [
//                'hp' => 0.4904,
//                'hc' => 0.0933,
//            ],
//        ],
//    ],
//    [
//        'start' => DateTime::createFromFormat('Y-m-d H:i:s', '01-02-2021 00:00:00'),
//        'end' => DateTime::createFromFormat('Y-m-d H:i:s', '01-08-2021 00:00:00'),
//        'tarifs' => [
//            'abo' => [
//                9 => 130.08 / 12,
//                12 => 151.92 / 12,
//                15 => 170.04 / 12,
//                18 => 186.24 / 12,
//                24 => 268.44 / 12,
//            ],
//            'TEMPO_BLEU' => [
//                'hp' => 0.0938,
//                'hc' => 0.0701,
//            ],
//            'TEMPO_BLANC' => [
//                'hp' => 0.1161,
//                'hc' => 0.0846,
//            ],
//            'TEMPO_ROUGE' => [
//                'hp' => 0.4905,
//                'hc' => 0.0925,
//            ],
//        ],
//    ],
];

//$tempoHistoYear = 2022;
//while($tempoHistoYear <= date('Y')) {
////    echo 'https://particulier.edf.fr/services/rest/referentiel/historicTEMPOStore?dateBegin='.$tempoHistoYear.'&dateEnd='.($tempoHistoYear+1); exit;
//    $json = file_get_contents('https://particulier.edf.fr/services/rest/referentiel/historicTEMPOStore?dateBegin='.$tempoHistoYear.'&dateEnd='.($tempoHistoYear+1));
////    $json = json_decode(file_get_contents('https://particulier.edf.fr/services/rest/referentiel/historicTEMPOStore?dateBegin='.$tempoHistoYear.'&dateEnd='.($tempoHistoYear+1)), true);
//    var_dump($json); exit;
//    $tempoHistoYear++;
//}

if (isset($_POST['tarifBase']) && isset($_POST['tarifHP']) && isset($_POST['tarifHC1']) && isset($_FILES['conso_file']) && file_exists($_FILES['conso_file']['tmp_name'])) {
    $consos = [];

    $sumBase = $sumTempo = $sumHCHP = 0;
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
        $tempoDate = (int)$currentDate->format('Hi') < 600 ? (clone $currentDate) : (clone $currentDate)->sub(new DateInterval('P1D'));
        $tempoPeriod = $currentHour > 2200 || $currentHour < 600 ? 'hc' : 'hp';
        $couleurTempo = $tempoHisto[$tempoDate->format('Y-n-j')] ?? 'TEMPO_BLEU';
        $tarifTempo = $tarifsTempo[0]['tarifs'][$couleurTempo][$tempoPeriod];
        $priceTempo = $valueKWH * $tarifTempo;

        // HC/HP
        $isHC = false;
        $tarifHCHP = $tarifHP;
        foreach ($periodsHC as $periodHC) {
            if ($currentHour > $periodHC['start'] || $currentHour < $periodHC['end']) {
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
            $isHC ? 'oui' : 'non',
            $tarifHCHP,
            $priceHCHP,
        ];

        $sumBase += $priceBase;
        $sumTempo += $priceTempo;
        $sumHCHP += $priceHCHP;

        $totalConso += $valueKWH * 1000;

        $row++;
    }

    $totalBase = $sumBase + $aboBase * $nbMonths;
    $totalTempo = $sumTempo + $aboTempo * $nbMonths;
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
                    echo $tarifHP; ?>" placeholder="0,1740;1400-1600-0,1402;0000-0600-0,1402">
                </div>
                <div class="col">
                    <label for="tarifHC1" class="form-label">Tarif HC 1</label>
                    <input type="text" class="form-control" name="tarifHC1" id="tarifHC1" value="<?php
                    echo $tarifHC1; ?>" placeholder="1400-1600-0,1402">
                    <p class="small">Format : <code>début[hhmm]-fin[hhmm]-tarif</code>.<br/>Exemple :
                        <code>1400-1600-0.1402</code></p>
                </div>
                <div class="col">
                    <label for="tarifHC2" class="form-label">Tarif HC 2</label>
                    <input type="text" class="form-control" name="tarifHC2" id="tarifHC2" value="<?php
                    echo $tarifHC2; ?>" placeholder="0000-0600-0.1402">
                    <p class="small">Format : <code>début[hhmm]-fin[hhmm]-tarif</code>.<br/>Exemple :
                        <code>0000-0600-0.1402</code></p>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Consommation</legend>
            <div class="row mb-3">
                <div class="col">
                    <label for="aboTempo" class="form-label">Abonnement Tempo</label>
                    <input type="text" class="form-control" name="aboTempo" id="aboTempo" value="<?php
                    echo $aboTempo; ?>" placeholder="15">
                </div>
                <div class="col">
                    <label for="conso_file" class="form-label">Fichier de conso (CSV)</label>
                    <input type="file" class="form-control" name="conso_file" id="conso_file">
                    <p class="small">
                        Fichier CSV récupéré sur <a href="https://mon-compte-particulier.enedis.fr/suivi-de-mesures">Enedis</a>
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


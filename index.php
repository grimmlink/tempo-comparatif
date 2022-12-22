<?php

$tarifBase = $_POST['tarifBase'] ?? 0.1659;
$aboBase = $_POST['aboBase'] ?? 11.36;
$tarifHP = $_POST['tarifHP'] ?? 0.1740;
$tarifHC1 = $_POST['tarifHC1'] ?? '1400-1600-0.1402';
$tarifHC2 = $_POST['tarifHC2'] ?? '';
$aboHCHP = $_POST['aboHCHP'] ?? 14.82;
$aboTempo = $_POST['aboTempo'] ?? 15;

if (isset($_POST['tarifBase']) && isset($_POST['tarifHP']) && isset($_POST['tarifHC1']) && isset($_FILES['conso_file']) && file_exists($_FILES['conso_file']['tmp_name'])) {
    $consos = [];

    $sumBase = $sumTempo = $sumHCHP = 0;
    $nbMonths = 0;
    $prevMonth = null;
    $totalConso = 0;

    // Prepare conso
    if (($handle = fopen($_FILES['conso_file']['tmp_name'], "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            list($date, $conso) = $data;

            $sourceDate = trim(str_replace("﻿", '', $date));
            $newDate = DateTime::createFromFormat(DATE_ATOM, $sourceDate);

            $month = $newDate->format('n');
            if (!$prevMonth || $prevMonth !== $month) {
                $prevMonth = $month;
                $nbMonths++;
            }

            $consos[$newDate->format('U')] = [
                'date' => $newDate,
                'val' => $conso,
            ];

            $totalConso += floatval($conso);
        }
        fclose($handle);
    }
    ksort($consos);
    $consos = array_values($consos);

    $firstDay = $consos[0]['date'];
    $lastDay = $consos[count($consos) - 1]['date'];

    // Histo Tempo
    $tempoHistoJson = json_decode(file_get_contents('https://raw.githubusercontent.com/grimmlink/tempo-comparatif/master/tempo.json'),
        true);
    foreach ($tempoHistoJson['dates'] as $item) {
        $tempoHisto[$item['date']] = $item['couleur'];
    }

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
        $currentDate = $consos[$row]['date'];
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

        $sumBase += $priceBase;
        $sumTempo += $priceTempo;
        $sumHCHP += $priceHCHP;

        $row++;
    }

    $totalBase = $sumBase + $aboBase * $nbMonths;
    $totalTempo = $sumTempo + $aboTempo * $nbMonths;
    $totalHCHP = $sumHCHP + $aboHCHP * $nbMonths;

    if (isset($_POST['export']) && $_POST['export'] === 'oui') {
        $fp = fopen('php://memory', 'w');
        fputcsv($fp, [
            'Date',
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
                 - Consommation totale : '.($totalConso/1000).' kWh
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
                        Fichier CSV incluant uniquement votre consommation respectant ce format (format d'export de la
                        conso
                        horaire enedis : https://mon-compte-particulier.enedis.fr/suivi-de-mesures/)<br/>
                        <code>
                            2022-11-02T07:00:00+01:00;288<br/>
                            2022-11-02T07:30:00+01:00;298<br/>
                            2022-11-02T08:00:00+01:00;252<br/>
                            2022-11-02T08:30:00+01:00;278
                        </code>
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


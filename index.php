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

<?php

$tarifBase = $_POST['tarif'] ?? 0.1659;
$rawConfigHC = $_POST['hc_config'] ?? '0,1740;1400-1600-0,1402;0000-0600-0,1402';

?>
<div class="container">
    <h1>Comparatif de facture Base / HC / Tempo</h1>

    <form action="/" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="tarif" class="form-label">Tarif base</label>
            <input type="text" class="form-control" name="tarif" id="tarif" value="<?php
            echo $tarifBase; ?>" placeholder="0.1659">
        </div>
        <div class="mb-3">
            <label for="hc_config" class="form-label">HC périodes (format <code>tarifHP;debut-fin;tarifHC;debut-fin;...</code> exemple :
                "0,1740;1400-1600-0,1402;0000-0600-0,1402")</label>
            <input type="text" class="form-control" name="hc_config" id="hc_config" value="<?php
            echo $rawConfigHC; ?>" placeholder="0,1740;1400-1600-0,1402;0000-0600-0,1402">
        </div>
        <div class="mb-3">
            <label for="conso_file" class="form-label">Fichier de conso (CSV)</label>
            <input type="file" class="form-control" name="conso_file" id="conso_file">
            <p class="small">
                Fichier CSV incluant uniquement votre consommation respectant ce format (format d'export de la conso horaire enedis : https://mon-compte-particulier.enedis.fr/suivi-de-mesures/)<br/>
                <code>
2022-11-02T07:00:00+01:00;288<br/>
2022-11-02T07:30:00+01:00;298<br/>
2022-11-02T08:00:00+01:00;252<br/>
2022-11-02T08:30:00+01:00;278
                </code>
            </p>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary mb-3">Calculer</button>
        </div>
    </form>
<?php

if (isset($_POST['tarif']) && isset($_POST['hc_config']) && isset($_FILES['conso_file'])) {
    $consos = [];
    $randomHash = uniqid('detail-');

    $sumBase = $sumTempo = $sumHCHP = 0;

    if (($handle = fopen($_FILES['conso_file']['tmp_name'], "r")) !== false) {
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

    $firstDay = $consos[0]['date'];
    $lastDay = $consos[count($consos)-1]['date'];


    $tempoHistoJson = json_decode(file_get_contents('https://raw.githubusercontent.com/grimmlink/tempo-comparatif/master/tempo.json'), true);
    foreach ($tempoHistoJson['dates'] as $item) {
        $tempoHisto[$item['date']] = $item['couleur'];
    }

    $configsHC = explode(';', $rawConfigHC);
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

        $sumBase += $priceBase;
        $sumTempo += $priceTempo;
        $sumHCHP += $priceHCHP;

        $row++;
    }

    $fp = fopen($randomHash.'.csv', 'w');
    fputcsv($fp, ['Date', 'Tarif kWh Base', 'Prix Base', 'Couleur Tempo', 'Tarif kWh Tempo', 'Total Tempo', 'HC?', 'Tarif kWh HC/HP', 'Total HC/HP'], ';');

    foreach ($comparatif as $fields) {
        fputcsv($fp, $fields, ';');
    }

    fclose($fp);

    echo '
    <table class="table table-striped">
        <tr>
            <th></th>
            <th>Période '.$consos[0]['date']->format('d/m/Y') .' à '.$consos[count($consos)-1]['date']->format('d/m/Y').'</th>
        </tr>
        <tr>
            <th>Somme Base</th>
            <td>'.$sumBase.'€</td>
        </tr>
        <tr>
            <th>Somme TEMPO</th>
            <td>'.$sumTempo.'€</td>
        </tr>
        <tr>
            <th>Somme HCHP</th>
            <td>'.$sumHCHP.'€</td>
        </tr>
    </table>
    
    <a href="/'.$randomHash.'.csv">Télécharger le détail en CSV</a>
    ';
}
?>

</div>
</body>
</html>


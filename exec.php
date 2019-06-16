<?php
$goToDir = "cd ./librec/bin/";
$goToCSVFolder = "cd ./results/csv/";
$models = [
    'ratio',
    'kfold'
];

$confAvailabels = [
    'globalavarege.properties',
    'itemknn.properties',
    'mostpopular.properties',
    'slopeone.properties',
    'userknn.properties',
];

$datasets = [
    'Music_InCarMusic',
    'Travel_TripAdvisor_v2'
];
$header = "Algoritmo,MPE,MSE,RMSE,MAE";

exec("$goToCSVFolder && rm $(fd .csv)");

foreach ($models as $model) {
    foreach ($datasets as $dataset) {
        $confFolder = getcwd() . "/conf/{$model}/{$dataset}";
        foreach ($confAvailabels as $confProp) {
            $runLibRec = "librec rec -exec -conf {$confFolder}/{$confProp}";
            exec("$goToDir && $runLibRec  2>&1 ", $output);
            $algorith = explode(".", $confProp);
            if ($model == "ratio") {
                $file = getcwd() . "/results/csv/ratio_{$dataset}.csv";
                $fp = fopen($file, "a+");
                $result = getMetricsByRatio($output, $algorith[0]);
                fputcsv($fp, $result);
                fclose($fp);
                file_prepend($header,$file);
            } else {
                $file = getcwd() . "/results/csv/kfold_{$dataset}.csv";
                $fp = fopen($file, "a+");
                $result = getMetricsByKFold($output, $algorith[0]);
                fputcsv($fp, $result);
                fclose($fp);
                file_prepend($header,$file);
            }
        }
    }
}


function getMetricsByKFold($output, $algotithName)
{
    $metrics = array($algotithName);
    $avgFounded = false;
    array_filter($output, function ($lineOutput) use (&$avgFounded, &$metrics) {
        if (preg_match("@Average@", $lineOutput) && !$avgFounded) {
            $avgFounded = true;
        }
        if ($avgFounded) {
            getCurrentMetricFromLine($lineOutput, $metrics);
        }
    });
    return $metrics;
}

/**
 * Array Map de Ratio
 */
function getMetricsByRatio($output, $algotithName)
{
    $metrics = array($algotithName);
    array_map(function ($lineOutput) use (&$metrics) {
        getCurrentMetricFromLine($lineOutput, $metrics);
    }, $output);
    return $metrics;
}

function getCurrentMetricFromLine($lineOutput, &$metrics)
{
    $currentMetric = preg_match("@MSE|RMSE|MPE|MAE@", $lineOutput, $match);
    if (!empty($currentMetric)) {
        $metricName = trim($match[0]);
        preg_match("@$metricName.*is.*(\d+\.\d+)@ius", $lineOutput, $metricResult);
        $metrics[$metricName] = $metricResult[1];
    }
}

function file_prepend ($string, $filename) {
    $fileContent = file_get_contents ($filename);
    file_put_contents ($filename, $string . "\n" . $fileContent);
}
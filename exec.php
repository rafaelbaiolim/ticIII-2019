<?php
$goToDir = "cd ./librec/bin/";
$models = [
    'kfold',
    'ratio'
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

foreach ($models as $model) {
    foreach ($datasets as $dataset) {
        $confFolder = getcwd() . "/conf/{$model}/{$dataset}";
        foreach ($confAvailabels as $confProp) {
            $runLibRec = "librec rec -exec -conf {$confFolder}/{$confProp}";
            exec("$goToDir && $runLibRec  2>&1 ", $output);
            $algorith = explode(".",$confProp);
            switch ($model) {
                case "ratio":
                    $fp = fopen(getcwd() ."/results/ratio.csv", "a+");
                    $metrics = getMetricsByRatio($output,[
                        "Métrica" => "Ratio",
                        "Algoritmo" => $algorith[0]
                    ]);
                    fputcsv($fp,$metrics);
                    fclose($fp);
                    break;
                case "kfold":
                    $fp = fopen(getcwd() ."/results/kfold.csv", "a+");
                    $metrics = getMetricsByKFold($output,[
                        "Métrica" => "KCV",
                        "Algoritmo" => $algorith[0]
                    ]);
                    fputcsv($fp,$metrics);
                    fclose($fp);
                    break;
            }
                        
        }
    }
}

function getMetricsByKFold($output, $metrics = array())
{
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
function getMetricsByRatio($output, $metrics = array("Métrica" => "Ratio"))
{
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

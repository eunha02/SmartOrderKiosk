<?php
// QR코드 처리 성능 측정
$logFileQR = 'performance_log_qr.txt';
$logContentsQR = file_get_contents($logFileQR);
$logLinesQR = explode("\n", $logContentsQR);
$totalTimeQR = 0;
foreach ($logLinesQR as $lineQR) {
    $linePartsQR = explode(" - Processing time: ", $lineQR);
    if (count($linePartsQR) == 2) {
        $timeQR = floatval($linePartsQR[1]);
        $totalTimeQR += $timeQR;
    }
}
$lineCountQR = count($logLinesQR);
$averageTimeQR = $lineCountQR > 0 ? $totalTimeQR / $lineCountQR : 0;
$performanceGoalQR = 4; // 예상 평균 처리 시간 4초로 설정
$goalStatusQR = $averageTimeQR <= $performanceGoalQR ? "목표 달성" : "목표 미달";

// 메뉴 화면 출력 성능 측정
$logFileMenus = 'performance_log_menu.txt';
$logContentsMenus = file_get_contents($logFileMenus);
$logLinesMenus = explode("\n", $logContentsMenus);
$totalTimeMenus = 0;
foreach ($logLinesMenus as $lineMenus) {
    $linePartsMenus = explode(" - Processing time: ", $lineMenus);
    if (count($linePartsMenus) == 2) {
        $timeMenus = floatval($linePartsMenus[1]);
        $totalTimeMenus += $timeMenus;
    }
}
$lineCountMenus = count($logLinesMenus);
$averageTimeMenus = $lineCountMenus > 0 ? $totalTimeMenus / $lineCountMenus : 0;
$performanceGoalMenus = 2; // 예상 평균 처리 시간 2초로 설정
$goalStatusMenus = $averageTimeMenus <= $performanceGoalMenus ? "목표 달성" : "목표 미달";


// 매출 분석 성능 측정
$logFileSales = 'performance_log_sales.txt';
$logContentsSales = file_get_contents($logFileSales);
$logLinesSales = explode("\n", $logContentsSales);
$totalTimeSales = 0;
foreach ($logLinesSales as $lineSales) {
    $linePartsSales = explode(" - Processing time: ", $lineSales);
    if (count($linePartsSales) == 2) {
        $timeSales = floatval($linePartsSales[1]);
        $totalTimeSales += $timeSales;
    }
}
$lineCountSales = count($logLinesSales);
$averageTimeSales = $lineCountSales > 0 ? $totalTimeSales / $lineCountSales : 0;
$performanceGoalSales = 2; // 예상 평균 처리 시간 2초로 설정
$goalStatusSales = $averageTimeSales <= $performanceGoalSales ? "목표 달성" : "목표 미달";
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>성능 측정 결과</title>
</head>
<body>
    <h1>성능 측정 결과</h1>
    <h2>QR코드 처리 성능</h2>
    <pre>
        코드 인식 및 로그인 처리 시간은 4초 이내 완료:
        
        평균 처리 시간: <?php echo $averageTimeQR; ?> 초
        성능 목표: <?php echo $performanceGoalQR; ?> 초
        성능 목표 상태: <?php echo $goalStatusQR; ?>
    </pre>

    <h3>메뉴 화면 출력 성능</h3>
    <pre>
        사용자 별 맞춤형 메뉴 추천이 화면에 표시되는 시간은 2초 이내 완료:
        
        평균 처리 시간: <?php echo $averageTimeMenus; ?> 초
        성능 목표: <?php echo $performanceGoalMenus; ?> 초
        성능 목표 상태: <?php echo $goalStatusMenus; ?>
    </pre>

    <h3>매출 분석 성능</h3>
    <pre>
        매출 분석 및 요약 성능 측정 결과 출력 시간은 2초 이내 완료:
        
        평균 처리 시간: <?php echo $averageTimeSales; ?> 초
        성능 목표: <?php echo $performanceGoalSales; ?> 초
        성능 목표 상태: <?php echo $goalStatusSales; ?>
    </pre>
</body>
</html>
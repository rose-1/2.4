<?php
    session_start();
    header('Content-Type: image/png');
    $image = imagecreatetruecolor(500, 350) or die('Невозможно инициализировать GD поток');;

    $sertificateTemplateUrl = __DIR__ . '\resources\certificate.png';
   // $sertificateTemplateUrl = __DIR__ . '\resources\ss.png';
    $imageBack = imagecreatefrompng($sertificateTemplateUrl);
//$imageBack = imagecreatefrompng('../resources/certificate.png');
    imagecopy($image, $imageBack, 0, 0, 0, 0, 500, 350);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    $fontFile = '../resources/font.ttf';
    if (!file_exists($fontFile)) {
        echo 'Файл шрифта не найден!';
        exit;
    }
    $finalMark = round(5 * $_SESSION['userScore'] / $_SESSION['maxScore']);
    $textTestName = $_SESSION['testName'];
    $textMarkFormat = "%s , Ваша оценка: %s (набрано %s баллов из %s.)";
    $textMark = sprintf($textMarkFormat, $_SESSION['userName'], ($finalMark < 2 ? 2 : $finalMark), $_SESSION['userScore'],
        $_SESSION['maxScore']);
    $textErrors = "Допущено ошибок: %s.";
    $textDate = date('H:i   d.m.y');
    imagettftext($image, (mb_strlen($textTestName) > 50 ? 12 : 14), 0, 60, 140, $textColor, $fontFile, $textTestName);
    imagettftext($image, (mb_strlen($textMark) > 50 ? 12 : 14), 0, 60, 170, $textColor, $fontFile, $textMark);
    imagettftext($image, 14, 0, 60, 200, $textColor, $fontFile, sprintf($textErrors, $_SESSION['errorCounts']));
    imagettftext($image, 12, 0, 340, 280, $textColor, $fontFile, $textDate);
    imagepng($image);
    imagedestroy($image);
    imagedestroy($imageBack);

/*
 $testQuestionsArray = $testData['questions'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sertificateTemplateUrl = __DIR__ . '\src\setificate-template.png';
    $userName = $_SESSION['userdata']['login'];
    $testsCount = count($testQuestionsArray);
    //$correctTestsCount = count(array_filter($_POST, filterCorrect));
    $image = imagecreatefrompng($sertificateTemplateUrl);
    $blackColor = imagecolorexact($image, 0, 0, 0);
    // Не получилось конвертировать русские символы, печатались крокозябры
    // пробовал по методичкам и более 5 решений из интернета
    $font = __DIR__ . '\font\arial.ttf';
    imagettftext($image, 40, 0, 180, 450, $blackColor, $font, $userName);
    imagettftext($image, 20, 0, 180, 525, $blackColor, $font, $testName);
   // imagettftext($image, 20, 0, 180, 650, $blackColor, $font, $correctTestsCount . '\\' . $testsCount);
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
 * */
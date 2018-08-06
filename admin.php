<?php
require_once 'core/functions.php';
$currentUser = getCurrentUser();
if (!$currentUser) {
    /* если пользователь не залогинен - отправляем на страницу index */
    redirect('index');
}
if (!isAdmin($currentUser)) {
    /* если у пользователя нет прав, отправляем header */
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    die;
}
$homeWorkNum = '2.4';
$homeWorkCaption = 'Куки, сессии и авторизация.';
$fileReady = false;
$filesPath = __DIR__ . '/uploadedFiles/';
$additionalHint = '';
$additionalHintStyle = '';
$warningStyle = 'font-weight: 700; color: red;';
/* проверяем нажимали ли кнопку LoadFileToServer */
if (isset($_POST['LoadFileToServer'])) {
    $fileReady = (isset($_FILES['myFile'])) ? checkFile($_FILES['myFile'], $filesPath) : false;
    /* Проверяем файл с помощью функции, в зависимости от результата получаем подсказку */
    switch ($fileReady) {
        case 'FileNotSet':
            $additionalHint = 'Файл не загружен, так как не был выбран.';
            break;
        case 'WrongFileType':
            $additionalHint = 'Файл не загружен (тип файла не подходит).';
            break;
        case 'ErrorLoading':
            $additionalHint = 'Произошла ошибка при загрузке файла, попробуйте повторить.';
            break;
        case 'SameFileExist':
            $additionalHint = 'Файл не загружен, так как на сервере уже есть идентичный файл.';
            break;
        case 'FileStructureNotValid':
            $additionalHint = 'Структура загружаемого файла не подходит, попробуйте загрузить другой файл.';
            break;
        case 'FileNotMoved':
            $additionalHint = 'Произошла ошибка при обработке файла на сервере, попробуйте повторить.';
            break;
        case false:
            $additionalHint = 'Ошибка загрузки, попробуйте повторить.';
            break;
        case 'FileUploadOK':
            $fileReady = true;
            $additionalHint = 'Файл успешно загружен';
            if (!headers_sent()) {
                header('Location: list.php'); /* при успешной загрузке - перенаправляем на список тестов */
                exit;
            }
            break;
        default:
            break;
    }
    if ($fileReady !== true and $fileReady !== false) {
        $fileReady = false;
        $additionalHintStyle = $warningStyle; /* выделяем стиль подсказки */
    }
}
/* проверяем есть ли на сервере загруженные файлы */
if (count(getNamesJson($filesPath)) > 0) {
    $fileReady = true;
}
/* Если нажали Очистить папку */
if (isset($_POST['ClearFilesFolder'])) {
    clearDir(__DIR__ . '/uploadedFiles/');
    $additionalHint = "Папка с файлами очищена!";
    $fileReady = false;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Домашнее задание по теме <?= $homeWorkNum ?> <?= $homeWorkCaption ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>
<header>
    <div class="container">
        <p class="greet">Здравствуйте, <?= $currentUser['name'] ?>!</p>
        <a class="logout" href="./logout.php">Выход</a>
    </div>
</header>

<div class="container main">
    <h1>Интерфейс загрузки файла</h1>
    <p>На этой странице необходимо выбрать и загрузить json-файл с тестами для дальнейшей работы. Для этих целей можно
        использовать файлы: <a href="./exampleTests/english.json" download="">english.json</a>,
        <a href="./exampleTests/multiplication.json" download="">multiplication.json</a> и
        <a href="./exampleTests/units.json" download="">units.json</a>. В форму загрузки встроена проверка загружаемого
        файла на наличие на сервере (по хешу). Если загружаемый файл уже есть на сервере, то он не будет загружен.</p>

    <form method="post" action="" enctype="multipart/form-data">

        <fieldset>
            <legend>Загрузка файлов</legend>
            <label>Файл: <input class="btn" type="file" name="myFile"></label>
            <hr>
            <p style="<?= $additionalHintStyle ?>"><?= $additionalHint ?></p>
            <div class="container">
                <input class="btn btn-prime" type="submit" name="LoadFileToServer" value="Отправить новый файл на сервер">
            </div>
        </fieldset>

        <?php if ($fileReady) { ?>
            <fieldset>
                <legend>Список файлов</legend>
                <p>Json-файлы с тестами, загруженные на сервер:</p>

                <ul>
                    <?php foreach (getNamesJson($filesPath) as $test) : /* Выводим список файлов и названий тестов */ ?>
                        <li><?= $test . ' / ' . json_decode(file_get_contents($filesPath . $test), true)['testName'] ?></li>
                    <?php endforeach; ?>
                </ul>

                <p>Можно перейти к выбору теста.</p>
                <hr>
                <div class="container">
                    <input class="btn" type="submit" name="ClearFilesFolder" value="Очистить папку"
                           title="При нажатии папка с загруженными файлами на сервере будет очищена">
                    <input class="btn btn-prime" type="submit" formaction="list.php" name="ShowTestsList"
                           value="К тестам =>"
                           title="Перейти в выполнению тестов">
                </div>
            </fieldset>
        <?php } ?>
    </form>
</div>
</body>
</html>
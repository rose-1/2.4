<?php
require_once 'core/functions.php';
$homeWorkNum = '2.4';
$homeWorkCaption = 'Куки, сессии и авторизация.';
$filesPath = __DIR__ . '/uploadedFiles/';
$testsReady = false;
$additionalHint = '';
$currentUser = getCurrentUser();
if (!$currentUser) {
    /* если пользователь не залогинен - отправляем на страницу index */
    redirect('index');
}
/* проверяем список json файлов с тестами и собираем массив из их названий */
$testFilesList = getNamesJson($filesPath);
if (count($testFilesList) > 0) {
    $tests = array();
    foreach ($testFilesList as $fileName) {
        $tests[] = json_decode(file_get_contents($filesPath . $fileName), true)['testName'];
    }
    $testsReady = true;
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
    <h1>Интерфейс выбора варианта теста</h1>

    <form method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>Тесты</legend>

            <?php if ($testsReady && isset($tests)) { ?>
                <p>Выберите один из <?= count($tests) ?> вариантов теста, который вы желаете пройти:</p>
                <?php
                $i = 0;
                foreach ($tests as $testNum => $test):
                    $i++;
                    $needChecked = ($i === 1 ? 'Checked' : '');
                    ?>

                    <p><label><input type="radio" name="testNum"
                                     value="<?= $testNum ?>" <?= $needChecked ?>><?= $test ?></label></p>

                <?php endforeach; ?>

                <?php
            } else {
                $additionalHint = 'Не удалось извлечь список тестов, попробуйте добавить тесты (доступно только для администраторов).';
            } ?>
            <hr>
            <p><?= $additionalHint ?></p>
            <div class="container">
                <?php if (isAdmin($currentUser)) : ?>
                    <input class="btn" type="submit" formaction="admin.php" name="ShowAdminForm" value="<= Добавить тест"
                           title="Вернуться к загрузке тестов">
                <?php endif; ?>

                <?php if (!$testsReady) : ?>
                    <input class="btn" type="submit" formaction="logout.php" name="ShowLoginForm"
                           value="Вернуться к форме входа" title="Вернуться к форме входа">
                <?php endif; ?>

                <?php if ($testsReady) : ?>
                    <input class="btn btn-prime" type="submit" formaction="test.php" formmethod="get" name="ShowTest"
                           value="Пройти тест =>" title="Перейти в выполнению выбранного теста">
                <?php endif; ?>
            </div>

        </fieldset>
    </form>
</div>
</body>
</html>
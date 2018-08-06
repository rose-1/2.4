<?php
require_once 'core/functions.php';
$homeWorkNum = '2.4';
$homeWorkCaption = 'Куки, сессии и авторизация.';
$filesPath = __DIR__ . '/uploadedFiles/';
$additionalHint = '';
$labelStyle = '';
$warningStyle = 'font-weight: 700; color: red;';
$rightStyle = 'color: green;';
$secondHint = '';
$errorCounts = 0; /* Количество ошибок */
$userScore = 0; /* Баллы, которые набрал тестируемый */
$maxScore = 0; /* Максимальное количество баллов, которое можно получить */
$errorCode = null;
$currentUser = getCurrentUser();
if (!$currentUser) {
    /* если пользователь не залогинен - отправляем на страницу index */
    redirect('index');
}
/* проверяем передался ли номер теста */
$testNum = getParam('testNum');
/*if (isset($_GET['testNum'])) {
    $testNum = $_GET['testNum'];
} elseif (isset($_POST['testNum'])) {
    $testNum = $_POST['testNum'];
}*/
/* извлекаем тест */
if (isset($testNum)) {
    $test = getSelectedTest($testNum, $filesPath);
    $testReady = ($test !== false) ? $test : false;
    if ($testReady) {
        $maxScore = count($test['questions']);
        $userScore = $maxScore;
    }
} else {
    $testReady = false;
}
/* возвращаем заголовки, если номер теста не указан, или тест с таким номером не найден */
if ($testReady === false && !headers_sent()) {
    if (isset($testNum)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        $errorCode = 404;
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        $errorCode = 400;
    }
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
    <h1>Интерфейс прохождения выбранного теста</h1>

    <form method="post" enctype="multipart/form-data">

        <fieldset>
            <legend><?= ($testReady && isset($test) ? $test['testName'] : 'Тесты') ?></legend>

            <?php
            if ($testReady && isset($test)) {
                $needChecked = '';
                foreach ($test['questions'] as $questionNum => $question):
                    $questionType = ($question['type'] === 'single' ? 'radio' : 'checkbox');
                    $i = 0;
                    ?>

                    <fieldset>
                        <legend><?= $question['question'] ?></legend>

                        <?php
                        foreach ($question['answers'] as $answerNum => $answer):
                            ++$i;
                            $labelName = ($question['type'] === 'single' ? $questionNum : $questionNum . '|' . $answerNum);
                            /*Если label - это чекбокс, то делаем имя в таком формате: "вопрос + | + № ответа", иначе - только имя вопроса.
                            Это нужно для правильной работы переключателей и передачи параметров для проверки теста */
                            $needChecked = ((!isset($_POST['ShowTestResults']) && $i === 1 && $questionType === 'radio') ||
                            (isset($_POST['ShowTestResults']) && isset($_POST[$labelName]) && $_POST[$labelName] === $answer) ? 'Checked' : '');
                            /* Расставляем галки/радио-кнопки правильно: если кнопка ShowTestResults не была нажата, то для первых
                            элементов типа radio, ставим атрибут Checked, если кнопка была нажата - загружаем как было установлено
                            пользователем */
                            if (isset($_POST['ShowTestResults'])) {
                                /* если нажали кнопку ShowTestResults и имя заполнено */
                                $labelStyle = elementStyle($labelName, $answer, $question['rightAnswers'], $warningStyle, $rightStyle);
                                /* определяем стиль элемента (в зависимости от наличия / отсутствия ошибки) */
                                if (isError($labelName, $answer, $question['rightAnswers'])) {
                                    /* если допущена ошибка - увеличиваем счетчик ошибок */
                                    $errorCounts = ++$errorCounts;
                                    $userScore = ($questionType === 'radio') ? --$userScore : $userScore - (1 / count($question['answers']));
                                    /* подсчитываем баллы - за каждый неправильный ответ отнимаем 1 балл, если в вопросе
                                    несколько ответов, то отнимаем 1 бал поделенный на количество ответов в вопросе */
                                }
                            }
                            ?>

                            <label style="<?= $labelStyle ?>"><input type="<?= $questionType ?>" name="<?= $labelName ?>"
                                                                     value="<?= $answer ?>" <?= $needChecked ?>><?= $answer ?>
                            </label>

                        <?php endforeach; ?>

                    </fieldset>

                <?php
                endforeach;
                /* вывод подсказки при нажатии ShowTestResults */
                if (isset($_POST['ShowTestResults'])) :
                    if ($errorCounts === 0) {
                        $additionalHint = $currentUser['name'] . ', Вы правильно ответили на все вопросы! Поздравляем!';
                    } else {
                        $additionalHint = $currentUser['name'] . ', Вы завершили тест. Количество ошибок: ' . $errorCounts . ' шт.';
                    }
                    $userScore = round($userScore, 2);
                    $_SESSION['userName'] = $currentUser['name'];
                    $_SESSION['errorCounts'] = $errorCounts;
                    $_SESSION['userScore'] = $userScore;
                    $_SESSION['maxScore'] = $maxScore;
                    $_SESSION['testName'] = $test['testName'];
                    $secondHint = 'Вы набрали ' . $userScore . ' баллов из ' . $maxScore . ' возможных.';
                    ?>

                    <hr>
                    <div class="container">
                        <img src="./core/certificate.php" alt="Сертификат">
                    </div>

                <?php endif; ?>

                <hr>

                <?php
            } else /* выводим ошибки если  тест не извлечен или ошибка в номере теста */ {
                switch ($errorCode) {
                    case 400:
                        echo '<h2>400 Bad Request</h2>';
                        $additionalHint = 'Не указан номер теста.';
                        break;
                    case 404:
                        echo '<h2>404 Not Found</h2>';
                        $additionalHint = 'Указан неправильный номер теста, или тест не найден в загруженном файле.';
                        break;
                    default:
                        $additionalHint = 'Не удалось извлечь список тестов, попробуйте вернуться и загрузить файл заново.';
                }
            }
            ?>

            <p><?= $additionalHint ?></p>
            <p><?= $secondHint ?></p>
            <div class="container">

                <?php if (isAdmin($currentUser)) : ?>
                    <input class="btn" type="submit" formaction="admin.php" name="ShowAdminForm" value="<<= Добавить тест"
                           title="Вернуться к загрузке файла">
                <?php endif; ?>

                <input class="btn" type="submit" formaction="list.php" name="ShowListForm"
                       value="<= Вернуться к выбору теста" title="Вернуться к загрузке тестов">

                <?php if ($testReady) { ?>
                    <input type="hidden" name="testNum" value="<?= (isset($testNum) ? $testNum : 0) ?>">
                    <input class="btn btn-prime" type="submit" formaction="test.php" name="ShowTestResults"
                           value="Проверить" title="Проверить результаты теста">
                <?php } ?>
            </div>

        </fieldset>
    </form>
</div>
</body>
</html>
/
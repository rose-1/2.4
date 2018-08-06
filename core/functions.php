<?php
session_start();
define('FILE_DATA_USERS', __DIR__ . '/../data/users.json');
/**
 * Реализует механизм проверок при авторизации
 * @param $login
 * @param $password
 * @param null $captcha
 * @return bool
 */
function checkForLogin($login, $password, $captcha = null)
{
    $_SESSION['loginErrors'] = [];
    if (userIsBlocked()) {
        $_SESSION['loginErrors'][] = 'Превышено количество попытох ввода! Возможность входа заблокирована на 1 час.';
        return false;
    }
    if (isset($captcha) && !isValidCaptcha($captcha)) {
        $_SESSION['loginErrors'][] = 'Капча введена неправильно';
        increaseLoginAttempts();
        return false;
    }
    if (!login($login, $password)) {
        $_SESSION['loginErrors'][] = 'Неправильный логин или пароль';
        increaseLoginAttempts();
        return false;
    }
    return true;
}
/**
 * Реализует механизм авторизации
 * @param $login
 * @param $password
 * @return bool
 */
function login($login, $password)
{
    $user = !empty($login) && empty($password) ? getUser('guest') : getUser($login);
    /*Если заполнен логин, но не заполнен пароль - используем учетную запись guest, иначе - ищем пользователя по логину */
    if ($user !== null && empty($password)) {
        /* для гостя */
        $_SESSION['user'] = getUser('guest');
        /* запоминаем введеный логин как имя */
        $_SESSION['user']['name'] = $login;
        return true;
    }
    if ($user !== null && $user['password'] == getHash($password)) {
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}
/**
 * Возвращает список ошибок, произошедших во время входа
 * @return mixed
 */
function getLoginErrors()
{
    return !empty($_SESSION['loginErrors']) ? $_SESSION['loginErrors'] : null;
}
/**
 * Проверяет заблокирован ли пользователь
 * @return bool
 */
function userIsBlocked()
{
    if (!empty($_SESSION['timeBlock'])) {
        /* если время блокировки установлено */
        if ($_SESSION['timeBlock'] - time() <= 3600) {
            /* если час не прошел */
            return true;
        } else {
            /* если час прошел */
            $_SESSION['timeBlock'] = null;
            return false;
        }
    } else {
        /* если время блокировки не установлено */
        if (getLoginAttempts(true) >= 5) {
            /* если превышено допустимое количество входов */
            $_SESSION['timeBlock'] = time();
            return true;
        } else {
            return false;
        }
    }
}
/**
 * Проверяет нужно ли вводить капчу
 * @return bool
 */
function isNeedCaptcha()
{
    return getLoginAttempts() >= 6;
}
/**
 * Проверяет, правильно ли введена капча
 * @param $captcha
 * @return bool
 */
function isValidCaptcha($captcha)
{
    return $_SESSION['captcha'] === $captcha;
}
/**
 * функция возвращает количество попыток входа без капчи или с капчей (если $withCaptcha === true)
 * @param bool $withCaptcha
 * @return int
 */
function getLoginAttempts($withCaptcha = false)
{
    if ($withCaptcha) {
        return isset($_SESSION['loginCaptchaAttempts']) ? $_SESSION['loginCaptchaAttempts'] : 0;
    } else {
        return isset($_SESSION['loginAttempts']) ? $_SESSION['loginAttempts'] : 0;
    }
}
/**
 * функция устанавливает количество попыток входа без капчи или с капчей (если $withCaptcha === true)
 * @return bool
 */
function increaseLoginAttempts()
{
    if (isNeedCaptcha()) {
        $_SESSION['loginCaptchaAttempts'] = getLoginAttempts(true) + 1;
        return true;
    } else {
        $_SESSION['loginAttempts'] = getLoginAttempts() + 1;
        return true;
    }
}
/**
 * Получает список пользователей
 * @return array|mixed
 */
function getUsers()
{
    if (!is_file(FILE_DATA_USERS)) {
        return [];
    }
    $usersData = file_get_contents(FILE_DATA_USERS);
    $users = json_decode($usersData, true);
    if (!$users) {
        return [];
    }
    return $users;
}
/**
 * Ищет пользователя по логину
 * @param $login
 * @return mixed|null
 */
function getUser($login)
{
    $users = getUsers();
    foreach ($users as $user) {
        if ($user['login'] === $login) {
            return $user;
        }
    }
    return null;
}
/**
 * Проверяет, является ли $user админом
 * @param $user
 * @return bool
 */
function isAdmin($user)
{
    if (isset($user['role']) && $user['role'] == 'admin') {
        return true;
    }
    return false;
}
/**
 * Проверяет, является ли метод ответа POST
 * @return bool
 */
function isPost()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}
/**
 * Проверяет установлен ли параметр $name в запросе
 * @param $name
 * @return null
 */
function getParam($name)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
}
/**
 * Возвращает текущего пользователя (если есть) или его параметр при наличии $param
 * @param null $param
 * @return null
 */
function getCurrentUser($param = null)
{
    if (isset($param)) {
        return isset($_SESSION['user']) && isset($_SESSION['user'][$param]) ? $_SESSION['user'][$param] : null;
    }
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}
/**
 * Отправляет переадресацию на указанную страницу
 * @param $action
 */
function redirect($action)
{
    header('Location: ' . $action . '.php');
    die;
}
/**
 * Уничтожает сессию и переадресует на страницу входа
 */
function logout()
{
    session_destroy();
    redirect('index');
}
/**
 * Возвращает хеш md5 от полученного параметра
 * @param $password
 * @return string
 */
function getHash($password)
{
    return md5($password);
}
/**
 * Проверяет загружаемый файл. Если все в порядке возвращает 'FileUploadOK' или ошибку если что-то не так
 * @param $file
 * @param $filesPath
 * @return string
 */
function checkFile($file, $filesPath)
{
    if (!isset($file['name']) or empty($file['name'])) {
        return 'FileNotSet';
    }
    if (isset($file['type'])) {
        if ($file['type'] !== 'application/json') {
            return 'WrongFileType';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'ErrorLoading';
        }
    }
    if (isset($file['tmp_name'])) {
        if (in_array(hash_file('md5', $file['tmp_name']), get_hash_json($filesPath))) {
            return 'SameFileExist';
        }
        $decodedFile = json_decode(file_get_contents($file['tmp_name']), true);
        if (!isset($decodedFile['testName']) or !isset($decodedFile['questions'])) {
            return 'FileStructureNotValid';
        }
        if (!move_uploaded_file($file['tmp_name'], $filesPath . setNameJson($filesPath))) {
            return 'FileNotMoved';
        }
    }
    return 'FileUploadOK';
}
/**
 * Очищает папку от файлов
 * @param $dir
 */
function clearDir($dir)
{
    $list = array_values(getNamesJson($dir));
    foreach ($list as $file) {
        unlink($dir . $file);
    }
}
/**
 * Сканирует папку и возвращает первое незанятое название файла 1.json, 2.json и т.д.
 * @param $dir
 * @return string
 */
function setNameJson($dir)
{
    $filesList = getNamesJson($dir);
    $fileName = (count($filesList) + 1) . '.json';
    $i = 2;
    while (is_file($dir . $fileName)) {
        $fileName = (count($filesList) + $i) . '.json';
        $i++;
    }
    return $fileName;
}
/**
 * Возвращает массив с именами json-файлов (с тестами)
 * @param $dir
 * @return array
 */
function getNamesJson($dir)
{
    $array = array_diff(scandir($dir), array('..', '.'));
    sort($array);
    return $array;
}
/**
 * Возвращает массив с хешами json-файлов
 * @param $dir
 * @return array
 */
function get_hash_json($dir)
{
    $hash_list = array();
    if (count(getNamesJson($dir)) > 0) {
        foreach (getNamesJson($dir) as $file) {
            $hash_list[] = hash_file('md5', $dir . $file);
        }
    }
    return $hash_list;
}
/**
 * Получает на входе номер теста и папку с файлами, а возращает сам тест или false
 * @param $testNum
 * @param $filesPath
 * @return bool|mixed
 */
function getSelectedTest($testNum, $filesPath)
{
    if (isset($testNum) && isset($filesPath)) {
        $testFilesList = getNamesJson($filesPath); /* список названий файлов с тестами */
        if (count($testFilesList) > 0 && count($testFilesList) > $testNum && isset($testFilesList[$testNum])) {
            return json_decode(file_get_contents($filesPath . $testFilesList[$testNum]), true);
        }
    }
    return false;
}
/**
 * получает на входе код варианта ответа, ответ и правильные ответы. Возвращает true если была допущена
 * ошибка или false - если нет
 * @param $labelName
 * @param $answer
 * @param $rightAnswers
 * @return bool
 */
function isError($labelName, $answer, $rightAnswers)
{
    if ((isset($_POST[$labelName]) && $_POST[$labelName] === $answer && !in_array($_POST[$labelName], $rightAnswers)) or
        (in_array($answer, $rightAnswers) && isset($_POST[$labelName]) === false)) {
        return true;
    }
    return false;
}
/* Функция получает на входе код варианта ответа, ответ и правильные ответы, стили для правильного и неправильного
ответов и возвращает подходящий стиль */
function elementStyle($labelName, $answer, $rightAnswers, $warningStyle, $rightStyle)
{
    if (isset($_POST[$labelName]) && $_POST[$labelName] === $answer) {
        if (in_array($_POST[$labelName], $rightAnswers)) {
            return $rightStyle;
        } else {
            return $warningStyle;
        }
    } elseif (in_array($answer, $rightAnswers)) {
        return $warningStyle;
    }
    return '';
}
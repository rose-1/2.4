<?php
require_once 'core/functions.php';
if (getCurrentUser()) {
    redirect('list');
}
if (isPost() && checkForLogin(getParam('login'), getParam('password'), getParam('captcha'))) {
    redirect('list');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <title>Авторизация</title>
</head>
<body>
<header>
    <div class="container">
        <p>Для продолжения необходимо войти. Можно ввести имя в поле логин и не вводить пароль, чтобы войти как
            гость.</p>
    </div>
</header>

<section>
    <div class="container">

        <h1>Авторизация</h1>

        <?php
        if (!empty(getLoginErrors())):
            foreach (getLoginErrors() as $error):
                ?>
                <p><?= $error ?></p>
            <?php
            endforeach;
        endif;
        ?>

        <form method="POST" id="login-form">
            <div class="form-group">
                <label for="lg">Логин</label>
                <input type="text" name="login" id="lg" class="form-control">
            </div>
            <div class="form-group">
                <label for="key">Пароль</label>
                <input type="password" name="password" id="key" class="form-control">
            </div>

            <?php if (isNeedCaptcha()): ?>
                <figure><img src="core/captcha.php" alt="" id="captcha"/></figure>
                <span class="captcha-upd"
                      onclick="document.getElementById('captcha').src = 'core/captcha.php?' + Math.random()">Обновить код</span>
                <div class="form-group">
                    <label for="captcha">Введите код с картинки:</label>
                    <input type="text" name="captcha" id="captcha" class="form-control">
                </div>
            <?php endif; ?>

            <input type="submit" id="btn-login" class="btn btn-prime" value="Войти">
        </form>

    </div>
</section>
</body>
</html>
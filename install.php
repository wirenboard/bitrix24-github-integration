<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/client.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

if (empty($_REQUEST['auth']['domain']) || empty($_REQUEST['auth']['member_id']) || empty($_REQUEST['auth']['access_token']) || empty($_REQUEST['auth']['refresh_token'])) {
    echo 'Приложение необходимо установить из портала Битрикс24';
    exit(200);
}

$params = Client::load();

$params = [
    'B24_APPLICATION_ID'     => $_ENV['APPLICATION_ID'],
    'B24_APPLICATION_SECRET' => $_ENV['APPLICATION_SECRET'],
    'B24_APPLICATION_SCOPE'  => explode(',', $_ENV['APPLICATION_SCOPE']),
    'B24_REDIRECT_URI'       => 'https://'.$_SERVER['SERVER_NAME'].'/index.php',
    'DOMAIN'                 => $_REQUEST['auth']['domain'],
    'MEMBER_ID'              => $_REQUEST['auth']['member_id'],
    'AUTH_ID'                => $_REQUEST['auth']['access_token'],
    'REFRESH_ID'             => $_REQUEST['auth']['refresh_token'],
];

Client::save($params);

$result = '';
if (Client::check()) {
    $result = 'Приложение установлено.<br>';
} else {
    $result = 'Приложение установлено c ошибками.<br>';
}
echo $result;
exit(200);
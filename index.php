<?php
error_reporting(E_ALL & ~E_NOTICE);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/client.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

use Bitrix24\Task\Item as TaskItem;
use Bitrix24\Task\CommentItem as TaskCommentItem;
use Bitrix24\User\User;

$formatter = new LineFormatter(LineFormatter::SIMPLE_FORMAT, LineFormatter::SIMPLE_DATE);
$formatter->includeStacktraces(true);

$stream = new StreamHandler(__DIR__.'/app.log');
$stream->setFormatter($formatter);

$logger = new Logger('App');
$logger->pushHandler($stream);

$client = new Client();
$bitrix24 = $client->getBitrix24();

$task = new TaskItem($bitrix24);
$taskComment = new TaskCommentItem($bitrix24);

$input = file_get_contents('php://input');
if (!$input) {
    exit(200);
}

$data = json_decode($input, true);
$logger->debug('hook', $data);

if (!empty($data['action']) && !empty($data['pull_request'])) {
    $action = $data['action'];
    $ref = $data['pull_request']['head']['ref'];
    preg_match('/\/(\d+)-/m', $ref, $matches);
    if (empty($matches[1])) {
        exit(200);
    }

    $taskId = $matches[1];
    $taskItem = $task->getData($taskId);
    if (!$taskItem || empty($taskItem['result'])) {
        exit(200);
    }

    $taskStatus = $taskItem['result']['STATUS'];
    $isMerged = !is_null($data['pull_request']['merged_at']);

    $userId = null;
    if (!empty($data['pull_request']['user']['login'])) {
        $username = $data['pull_request']['user']['login'];
        $userId = getUserByUsername($username, $bitrix24);
    }
    
    if ($action == 'opened') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Добавлен Pull Request '.$data['pull_request']['html_url'],
            'AUTHOR_ID' => $userId,
        ]);
        if ($taskStatus != 3) {
            $task->startExecution($taskId);
        }
    }
    else if ($action == 'synchronize') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Обновлен Pull Request '.$data['pull_request']['html_url'],
            'AUTHOR_ID' => $userId,
        ]);
    }
    else if ($action == 'closed') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Закрыт Pull Request '.$data['pull_request']['html_url'],
            'AUTHOR_ID' => $userId,
        ]);
        if ($taskStatus != 5 && $isMerged) {
            $task->complete($taskId);
        }
    }
    else if ($action == 'reopened') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Переоткрыт Pull Request '.$data['pull_request']['html_url'],
            'AUTHOR_ID' => $userId,
        ]);
        if ($taskStatus == 5) {
            $task->renew($taskId);
        }
    }
}

function getUserByUsername($username, $bitrix24) {
    $users = $bitrix24->call('user.search', [
        'FILTER' => [
            'UF_WEB_SITES' => $username,
        ]
    ]);

    if (is_array($users) && !empty($users['result']) && count($users['result'])) {
        return (int)($users['result'][0]['ID']);
    }

    return null;
}
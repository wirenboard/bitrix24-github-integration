<?php
error_reporting(E_ALL & ~E_NOTICE);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/client.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

use Bitrix24\Task\Item as TaskItem;
use Bitrix24\Task\CommentItem as TaskCommentItem;

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

    if ($action == 'opened') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Добавлен Pull Request '.$data['pull_request']['html_url']
        ]);
    }
    else if ($action == 'synchronize') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Обновлен Pull Request '.$data['pull_request']['html_url']
        ]);
    }
    else if ($action == 'closed') {
        $taskComment->add($taskId, [
            'POST_MESSAGE' => 'Закрыт Pull Request '.$data['pull_request']['html_url']
        ]);
        $task->complete($taskId);
    }
}
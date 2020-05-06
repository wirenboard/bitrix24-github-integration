## Установка

* Клонируем репозиторий

```shell
git clone https://github.com/wirenboard/bitrix24-github-integration.git
```

* Заходим в папку приложения и устанавливаем зависимости

```shell
cd bitrix24-github-integration
composer install
```

## Настройка Bitrix24

* Создаем приложение в Bitrix24

```shell
1. Переходим по ссылке (вместо {host} указываем ваш): https://{host}.bitrix24.ru/marketplace/local/edit/0/
2. В первом поле вписываем название приложения, например Github
3. Отмечаем чекбокс - Приложение использует только API
4. В разделе Права доступа отмечаем чекбокс - Задачи (task)
5. В поле - Укажите ссылку* пишем адрес где развернуто сервер приложение, например http://host/index.php
6. В поле - Укажите ссылку для первоначальной установки (необязательно) пишем адрес к install.php, например 
https://host/install.php
7. Нажимаем - Сохранить
```

## Настройка серверного приложения

* Копируем конфиг

```shell
cp .env.example .env
nano .env
```

* Заполняем данные

```shell
APPLICATION_ID= берем из колонки Код приложения (Client ID) по ссылке https://{host}.bitrix24.ru/marketplace/local/list/
APPLICATION_SECRET= берем из колонки Ключ приложения (Client Secret) по ссылке https://{host}.bitrix24.ru/marketplace/local/list/
APPLICATION_DOMAIN={host}.bitrix24.ru (подставляем вместо {host} свой)
APPLICATION_SCOPE=task
```

* Авторизуем серверное приложение

```shell
В списке приложений по адресу https://{host}.bitrix24.ru/marketplace/local/list/ слева от названия своего приложения
нажимаем на меню и выбираем в контекстном окне - Переустановить приложение
Если все сделали правильно, то в папке серверного приложения рядом с index.php появится файл auth который в себе содержит
данные авторизации в json
```

## Настраиваем Github Webhook

```shell
1. Открываем ссылку https://github.com/{username}/{repository}/settings/hooks/new
2. В поле - Payload URL указываем адрес серверного приложения, например http://host/index.php
3. В поле - Content type выбираем application/json
4. В разделе Which events would you like to trigger this webhook? выбираем Let me select individual events. и 
в открывшемся списке отмечаем только Pull requests. Галочку у Pushes снимаем
```

## Использование

Серверное приложение работает по шаблону:

```shell
*/{bitrix task id}-*
```

Например:

```shell
feature/100-add-new-type, где 100 это номер задачи в Bitrix24
bugfix/253-fix-user-bug, где 253 это номер задачи в Bitrix24
```
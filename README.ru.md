Opencart module
===============

Модуль интеграции CMS Opencart >= 2.3 c [RetailCRM](http://retailcrm.ru)

### Предыдущие версии:

[v1.x](https://github.com/retailcrm/opencart-module/tree/v1.x)

[v2.x (2.0, 2.1, 2.2)](https://github.com/retailcrm/opencart-module/tree/v2.2)

#### Модуль позволяет:

* Экспортировать в CRM данные о заказах и клиентах и получать обратно изменения по этим данным
* Синхронизировать справочники (способы доставки и оплаты, статусы заказов и т.п.)
* Выгружать каталог товаров в формате [ICML](http://www.retailcrm.ru/docs/Developers/ICML)

#### Установка

Установите модуль скопировав необходимые файлы в корень сайта

```
unzip master.zip
cp -r opencart-module/* /path/to/site/root
```

#### Активируйте модуль

В списке модулей нажмите "Установить"

#### Настройте параметры интеграции

На странице настроек модуля укажите URL Вашей CRM и ключ авторизации, после сохранения этих данных укажите соответствия справочников типов доставок, оплат и статусов заказа.

#### Получение измений из CRM

Для получения изменений и новых данных добавьте в cron следующую запись:

```
*/5 * * * * /usr/bin/php /path/to/opencart/system/cron/history.php >> /path/to/opencart/system/logs/cronjob_history.log 2>&1
```

#### Настройка экспорта каталога

Для периодической выгрузки каталога добавьте в cron следующую запись:

```
* */4 * * * /usr/bin/php /path/to/opencart/system/cron/icml.php >> /path/to/opencart/system/logs/cronjob_icml.log 2>&1
```

В настройках CRM установите путь к файлу выгрузки

```
http://youropencartsite.com/retailcrm.xml
```

#### Выгрузка существующих заказов и покупателей

Запустите команду единожды:
/usr/bin/php /path/to/opencart/system/cron/export.php

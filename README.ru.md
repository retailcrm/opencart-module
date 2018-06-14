Opencart module
===============

Модуль интеграции CMS Opencart >= 2.3 c [retailCRM](https://retailcrm.ru)

Информация о [кастомизации](https://github.com/retailcrm/opencart-module/wiki/%D0%9A%D0%B0%D1%81%D1%82%D0%BE%D0%BC%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F-%D0%B8%D0%BD%D1%82%D0%B5%D0%B3%D1%80%D0%B0%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D0%BE%D0%B3%D0%BE-%D0%BF%D0%BB%D0%B0%D0%B3%D0%B8%D0%BD%D0%B0)

### Предыдущие версии:

[v1.x](https://github.com/retailcrm/opencart-module/tree/v1.x)

[v2.x (2.0, 2.1, 2.2)](https://github.com/retailcrm/opencart-module/tree/v2.2)

#### Модуль позволяет:

* Экспортировать в retailCRM данные о заказах и клиентах и получать обратно изменения по этим данным
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

На странице настроек модуля укажите URL retailCRM и ключ авторизации, после сохранения этих данных укажите соответствия справочников типов доставок, оплат и статусов заказа.

#### Получение измений из retailCRM

Для получения изменений и новых данных добавьте в cron следующую запись:

```
*/5 * * * * /usr/bin/php /path/to/opencart/system/cron/history.php >> /path/to/opencart/system/storage/logs/cronjob_history.log 2>&1
```

#### Настройка экспорта каталога

Для периодической выгрузки каталога добавьте в cron следующую запись:

```
* */4 * * * /usr/bin/php /path/to/opencart/system/cron/icml.php >> /path/to/opencart/system/storage/logs/cronjob_icml.log 2>&1
```

В настройках retailCRM установите путь к файлу выгрузки

```
http://youropencartsite.com/retailcrm.xml
```
#### Настройка выгрузки акционных цен

Для периодической выгрузки акционных цен в CRM в настройках модуля укажите тип цены, в который необходимо выгружать акционные цены, в крон добавьте следующую запись

```
0 0 * * * /usr/bin/php /path/to/opencart/system/cron/prices.php >> /path/to/opencart/system/storage/logs/cronjob_prices.log 2>&1
```

#### Выгрузка существующих заказов и покупателей

Запустите команду единожды:
/usr/bin/php /path/to/opencart/system/cron/export.php

#### Кастомизация моделей

Для создания кастомных классов скопируйте файл модели в директорию custom, в новом файле измените название класса с "ModelExtensionRetailcrmFilename" на "ModelExtensionRetailcrmCustomFilename", где "Filename" - название файла с заглавной буквы. После этого модуль будет использовать методы кастомного класса.

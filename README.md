Opencart module
===============

Модуль интеграции CMS Openacrt c  [RetailCRM](http://retailcrm.ru)

### Модуль позволяет:

* Экспортировать в CRM данные о заказах и клиентах и получать обратно изменения по этим данным
* Синхронизировать справочники (способы доставки и оплаты, статусы заказов и т.п.)
* Выгружать каталог товаров в формате [ICML](http://retailcrm.ru/docs/Разработчики/ФорматICML) (IntaroCRM Markup Language)

### Установка

#### Скачайте модуль

```
https://github.com/retailcrm/opencart-module/archive/master.zip
```


#### Установите модуль скопировав необходимые файлы в корень сайта
```
unzip master.zip
cp -r opencart-module/* /path/to/opecart/instance
```

#### Активируйте модуль

В основном меню Extension -> Modules -> Intstall module.

#### Настройка экспорта данных

Для периодической выгрузки каталога добавьте в cron следующую запись:

```
* */4 * * * /usr/bin/php /path/to/opencart/system/cron/export.php >> /path/to/opencart/system/logs/cronjob_export.log 2>&1
```

В настройках CRM установите путь к файлу выгрузки

```
/download/retailcrm.xml
```

#### Выгрузка новых заказов в CRM (для версии opencart 1.5.x.x, для версии 2.0 и старше не требуется)

В файле:

```
/catalog/model/checkout/order.php
```

Добавьте следующие строки в метод addOrder непосредственно перед языковой конструкцией return:

```
$this->load->model('retailcrm/order');
$this->model_retailcrm_order->send($data, $order_id);
```

В файле:

```
/admin/model/sale/order.php
```

Добавьте следующие строки в методы addOrder и editOrder непосредственно перед языковой конструкцией return:

```
if (!isset($data['fromApi'])) {
    $this->load->model('retailcrm/order');
    $this->model_retailcrm_order->send($data, $order_id);
}
```

#### Получение измений из CRM

Для получения изменений и новых данных добавьте в cron следующую запись:

```
*/5 * * * * /usr/bin/php /path/to/opencart/system/cron/retailcrm/history.php >> /path/to/opencart/system/logs/cronjob_history.log 2>&1
```


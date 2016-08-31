Opencart module
===============

Модуль интеграции CMS Openacart 1.5.x.x c [RetailCRM](http://retailcrm.ru)

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


#### Выгрузка новых заказов в CRM

##### VQmod

Скопируйте файл _retailcrm_create_order.xml_ в _/path/to/site/root/vqmod/xml_.

Для обновления кеша VQmod может потрбоваться удалить файлы _/path/to/site/root/vqmod/vqcache/vq2-admin_model_sale_order.php_ и _/path/to/site/root/vqmod/vqcache/vq2-catalog_model_checkout_order.php_

##### Ручная установка

В файле:

```
/catalog/model/checkout/order.php
```

Добавьте следующие строки в метод addOrder непосредственно перед языковой конструкцией return:

```php
$this->load->model('retailcrm/order');
$this->model_retailcrm_order->sendToCrm($data, $order_id);
```

В файле:

```
/admin/model/sale/order.php
```

Добавьте следующие строки в методы addOrder и editOrder в самом конце каждого метода:

```php
if (!isset($data['fromApi'])) {
    $this->load->model('setting/setting');
    $status = $this->model_setting_setting->getSetting('retailcrm');

    if (!empty($data['order_status_id'])) {
        $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];
    }

    $this->load->model('retailcrm/order');
    if (isset ($order_query)) {
        $this->model_retailcrm_order->changeInCrm($data, $order_id);
    } else {
        $this->model_retailcrm_order->sendToCrm($data, $order_id);
    }
}
```

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

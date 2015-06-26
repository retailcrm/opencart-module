Opencart module
==============

Opencart module for interaction with [RetailCRM](http://retailcrm.ru) through [REST API](http://www.retailcrm.ru/docs/).

###Features

* Send orders to RetailCRM
* Get changes from RetailCRM
* Configure relations between dictionaries of IntaroCRM and Opencart (statuses, payments, delivery types and etc)
* Generate catalog export file in [ICML](http://retailcrm.ru/docs/Разработчики/ФорматICML) format

###Install

#### Download module

```
https://github.com/retailcrm/opencart-module/archive/master.zip
```


#### Install module
```
unzip master.zip
cp -r opencart-module/* /path/to/opecart/instance
```

#### Activate via Admin interface.

Go to Modules -> Intstall module. Before running exchange you must configure module.

#### Export

Setup cron job for periodically catalog export

```
* */12 * * * /usr/bin/php /path/to/opencart/cli/cli_export.php >> /path/to/opencart/system/logs/cronjob_export.log 2>&1
```

Into your CRM settings set path to exported file

```
/download/retailcrm.xml
```

#### Export new order from shop to CRM

Add this lines:

```
$this->load->model('retailcrm/order');
$this->model_retailcrm_order->send($data, $order_id);
```

into:

```
/catalog/model/checkout/order.php
```

script, into addOrder method before return statement

Add this lines:

```
if (!isset($data['fromApi'])) {
    $this->load->model('setting/setting');
    $status = $this->model_setting_setting->getSetting('retailcrm');
    $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];

    $this->load->model('retailcrm/order');
    $this->model_retailcrm_order->send($data, $order_id);
}
```

into:

```
/admin/model/sale/order.php
```

script, into addOrder & editOrder methods at the end of these methods

#### Export new order from CRM to shop

Setup cron job for exchange between CRM & your shop

```
*/5 * * * * /usr/bin/php /path/to/opencart/cli/cli_history.php >> /path/to/opencart/system/logs/cronjob_history.log 2>&1
```


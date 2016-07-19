Opencart module
===============

Module allows integrate CMS Opencart 1.5.x.x with [retailCRM](http://retailcrm.pro)

#### Features:

* Export orders to retailCRM & fetch changes back
* Export product catalog into [ICML](http://www.retailcrm.pro/docs/Developers/ICML) format

#### Install

Copy files to the site root

```
unzip master.zip
cp -r opencart-module/* /path/to/site/root
```

#### Setup

* Go to Admin -> Extensions -> Modules -> retailCRM
* Fill you api url & api key
* Specify directories matching


#### Export orders to retailCRM

##### VQmod

Copy _retailcrm_create_order.xml_ into _/path/to/site/root/vqmod/xml_.

For VQmod cache renewal you may need to delete files _/path/to/site/root/vqmod/vqcache/vq2-admin_model_sale_order.php_ & _/path/to/site/root/vqmod/vqcache/vq2-catalog_model_checkout_order.php_

##### Manual setup

In the file:

```
/catalog/model/checkout/order.php
```

add theese lines into addOrder method right before return statement:

```php
$this->load->model('retailcrm/order');
$this->model_retailcrm_order->sendToCrm($data, $order_id);
```

In the file:

```
/admin/model/sale/order.php
```

add theese lines into addOrder & editOrder methods right before return statement:

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

#### Getting changes in orders

Add to cron:

```
*/5 * * * * /usr/bin/php /path/to/opencart/system/cron/history.php >> /path/to/opencart/system/logs/cronjob_history.log 2>&1
```

#### Setting product catalog export

Add to cron:

```
* */4 * * * /usr/bin/php /path/to/opencart/system/cron/icml.php >> /path/to/opencart/system/logs/cronjob_icml.log 2>&1
```

Your export file will be available by following url

```
http://youropencartsite.com/retailcrm.xml
```

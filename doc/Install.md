Installation
============

### Clone module.
```
git clone git@github.com:/intarocrm/opencart-module.git
```

### Install Rest API Client.

```
cd opencart-module/system/library/intarocrm
./composer.phar install
```

### Install module
```
cp -r opencart-module/* /path/to/opecart/instance
```

### Activate via Admin interface.

Go to Modules -> Intstall module. Before running exchange you must configure module.

### Export

Setup cron job for periodically catalog export

```
* */12 * * * /usr/bin/php /path/to/opencart/cli/cli_export.php >> /path/to/opencart/system/logs/cronjob_export.log 2>&1
```

Into your CRM settings set path to exported file

```
/download/intarocrm.xml
```

### Exchange setup


#### Export new order from shop to CRM

```
$this->load->model('intarocrm/order');
$this->model_intarocrm_order->send($data, $order_id);
```

Add this lines into:
* /catalog/controller/checkout/confirm.php script, into index method before setOutput call
* /admin/model/sale/order.php script, into addOrder & editOrder methods at the end of these methods

#### Export new order from CRM to shop

Setup cron job for exchange between CRM & your shop

```
*/5 * * * * /usr/bin/php /path/to/opencart/cli/cli_export.php >> /path/to/opencart/system/logs/cronjob_history.log 2>&1
```

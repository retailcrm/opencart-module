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
/downloads/intarocrm.xml
```

### Exchange setup


#### Export new order from shop to CRM

Open /catalog/model/checkout/order.php script. Into addOrder method add this line before return statement:

```
$this->crmOrderAction($data, $order_id, 'create');
```

In the end of this file add method:

```
protected function crmOrderAction($order, $order_id, $action=null)
{
    $this->load->model('setting/setting');
    $settings = $this->model_setting_setting->getSetting('intarocrm');
    $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

    if(isset($settings['intarocrm_url']) && $settings['intarocrm_url'] != '' && isset($settings['intarocrm_apikey']) && $settings['intarocrm_apikey'] != '') {
        include_once __DIR__ . '/../../../system/library/intarocrm/apihelper.php';

        $order['order_id'] = $order_id;
        $crm = new ApiHelper($settings);

        if ($action != null) {
            $method = 'order' . ucfirst($action);
            $crm->$method($order);
        }
    }

}
```

#### Export new order from CRM to shop

Setup cron job for exchange between CRM & your shop

```
*/5 * * * * /usr/bin/php /path/to/opencart/cli/cli_export.php >> /path/to/opencart/system/logs/cronjob_history.log 2>&1
```

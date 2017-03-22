Opencart module
===============

Module allows integrate CMS Opencart >= 2.3 with [retailCRM](http://retailcrm.pro)

### Previous versions:

[v1.x](https://github.com/retailcrm/opencart-module/tree/v1.x)

[v2.x (2.0, 2.1, 2.2)](https://github.com/retailcrm/opencart-module/tree/v2.2)

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

#### Export existing orders and customers

You want to run this command onecly:
/usr/bin/php /path/to/opencart/system/cron/export.php

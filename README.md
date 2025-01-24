[![Build Status](https://github.com/retailcrm/opencart-module/workflows/ci/badge.svg)](https://github.com/retailcrm/opencart-module/actions)
[![Coverage](https://img.shields.io/codecov/c/gh/retailcrm/opencart-module/master.svg?logo=codecov&logoColor=white)](https://codecov.io/gh/retailcrm/opencart-module)
[![GitHub release](https://img.shields.io/github/release/retailcrm/opencart-module.svg?logo=github&logoColor=white)](https://github.com/retailcrm/opencart-module/releases)
[![PHP version](https://img.shields.io/badge/PHP->=5.4-blue.svg?logo=php&logoColor=white)](https://php.net/)

Opencart module
===============

This module allows to integrate CMS Opencart >= 2.3 with [RetailCRM](https://retailcrm.pro).

### Previous versions:

[v1.x](https://github.com/retailcrm/opencart-module/tree/v1.x)

[v2.x (2.0, 2.1, 2.2)](https://github.com/retailcrm/opencart-module/tree/v2.2)

#### Features:

* Export orders to RetailCRM & receive changes from RetailCRM.
* Export product catalog to the [ICML](https://help.retailcrm.pro/Developers/ICML) format.

#### Install

**Note:** `/path/to/your/site` is just a placeholder. You should replace it with the actual path to your site root in the examples below. The module won't work if you'll use those examples without changing the path placeholder.

Copy module files to the site root (replace `/path/to/your/site` with the actual path to your site):

```
unzip master.zip
cp -r opencart-module/src/* /path/to/your/site
```

#### Setup

* Go to Admin -> Extensions -> Modules -> RetailCRM
* Fill you API URL & API key
* Specify directories matching

#### Migrating to 4.* from early modules versions

It's necessary to remove the `/path/to/your/site/system/library/retailcrm` before copying current module into your site.

#### Getting changes in orders

Add to cron (replace `/path/to/your/site` with the actual path to your site):

```
*/5 * * * * /usr/bin/php /path/to/your/site/system/library/retailcrm/cron/history.php >> /path/to/your/site/system/storage/logs/cronjob_history.log 2>&1
```

#### Setting product catalog export

Add to cron (replace `/path/to/your/site` with the actual path to your site):

```
* */4 * * * /usr/bin/php /path/to/your/site/system/library/retailcrm/cron/icml.php >> /path/to/your/site/system/storage/logs/cronjob_icml.log 2>&1
```

Your export file should be available by following url:

```
http://youropencartsite.com/retailcrm.xml
```

Replace `youropencartsite.com` with your site domain and `http` with your site scheme.

#### Export existing orders and customers

Run this command (replace `/path/to/your/site` with the actual path to your site):
```sh
/usr/bin/php /path/to/your/site/system/library/retailcrm/cron/export.php
```

You should run this command only once.

#### Setting inventories export

Add to cron (replace `/path/to/your/site` with the actual path to your site):

```
*/15 * * * *  /usr/bin/php /path/to/your/site/system/library/retailcrm/cron/inventories.php >> /path/to/your/site/system/storage/logs/cronjob_inventories.log 2>&1
```
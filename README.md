Opecart module
=============

Opencart module for interaction with [IntaroCRM](http://www.intarocrm.com) through [REST API](http://docs.intarocrm.ru/rest-api/).

Module allows:

* Send to IntaroCRM new orders
* Configure relations between dictionaries of IntaroCRM and Opencart (statuses, payments, delivery types and etc)
* Generate [ICML](http://docs.intarocrm.ru/index.php?n=Пользователи.ФорматICML) (IntaroCRM Markup Language) for catalog loading by IntaroCRM

Installation
-------------

### 1. Manual installation


#### Clone module.
```
git clone git@github.com:/intarocrm/opencart-module.git
```

#### Install Rest API Client.

```
cd opencart-module/system/library/intarocrm
./composer.phar install
```

#### Install module
```
cp -r opencart-module/* /path/to/opecart/instance
```

#### Activate via Admin interface.

Go to Modules -> Intstall module.

#### Export

Catalog export script will be here
```
/index.php?route=export/intarocrm
```

#### Exchange setup

Look into example folder

#### TODO
* Edit order hook
* Export old customers & orders
* Generate static xml
* New customers export


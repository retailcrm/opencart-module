## v4.2.1
* Daemon Collector hidden

## v4.2.0
* Added option to upload inventories

## v4.1.19
* Added option to round order amount

## v4.1.18
* Fixed constant with version of module.

## v4.1.17
* Added additional parameters to GET requests.

## v4.1.16
* Support for services in ICML

## v4.1.15
* Display module version

## v4.1.14
* Added currency validation when configuring the module

## v4.1.13
* Changed the logic of customer subscriptions to promotional newsletters

## v4.1.12
* Added escaping for db query in method for getting zone

## v4.1.11
* Fixed the transfer of the weight offers

## v4.1.10
* Types of deliveries and payments are displayed only active status and available stores

## v4.1.9
* Optimization of the history processing algorithm

## v4.1.8
* Fixed customer externalId when creating a customer and sending it to RetailCRM

## v4.1.7
* Fixed notices in ICML generation and while setting delivery type
* Fixed incorrect data check before setting payment data
* Some other minor improvements

## v4.1.6
* Fix for typo in the history routine
* Fix for incorrect protocol in the ICML product image links

## v4.1.5
* Send discount details into the system
* Prefix for payment external ID (to ensure that it's unique)
* Make payment sum optional
* Weight calculation based on product options

## v.4.1.4
* Create payment only when the payment type is specified
* Checking the availability of promotional price for the product when uploading the order

## v.4.1.3
* Removed the ability to specify the API version

## v.4.1.2
* Added accounting for a gift certificate price when creating an order and sending it to RetailCRM

## v.4.1.1
* Updated the mechanics of processing customer addresses

## v.4.1.0
* Added the ability to connect Online Consultant

## v.4.0.1
* Fixed the transfer of coupon discounts
* Fixed console commands

## v.4.0.0
* Added support for corporate customers
* Added support for changing the customer in the order

## v.3.3.9
* Fixed prices in ICML

## v.3.3.8
* Fixed warnings output when generating ICML

## v.3.3.7
* Changed the configuration of travis-ci for build

## v.3.3.6
* Minor bug fixes

## v.3.3.5
* Added generation of dimensions in the catalog

## v.3.3.4
* Fixed a bug with incorrect uploading of promotional prices for products with characteristics

## v.3.3.3
* Added removal of product's price type for unidentified promotional prices

## v.3.3.2
* Added the return of leftover product stocks when the order is canceled

## v.3.3.1
* Fixed a bug with sending order data to RetailCRM when receiving the history of changes from RetailCRM

## v.3.3.0
* Added setting for recording the history of order changes in Opencart
* Fixed a bug with calling the order editing event when uploading the history of changes from RetailCRM
* Added transfer of the price type when creating or editing an order

## v.3.2.4
* Added the ability to transfer promotional prices for multiple user groups
* Added the ability to transfer zero price for unspecified promotional prices
* Removed the retailcrm base price from the price types conformity settings

## v.3.2.2
* Removed generation of the customer's externalId when order was created without registeration on the website.

## v.3.2.1
* Changed the logic for transferring data for orders and customers. Contact information for delivery is transferred to the order's card, and the payer's contact information is transferred to the customer's card.

## v.3.2.0
* Added uploading images for categories in ICML
* Added setting for selecting the currency in which the price will be uploaded in ICML

## v.3.1.6
* Fixed getting events from the database for OC 3.0

## v.3.1.5
* Bug fixes

## v.3.1.4
* Fixed incorrect combining of delivery types

## v.3.1.3
* Added module activation in the RetailCRM marketplace

## v.3.1.2
* Added Spanish translation
* Reworked English translation

## v.3.1.1
* Added customer creation when manually uploading an order from admin panel

## v.3.1.0
* Redesigned the twig template
* Added a setting for transmitting the order number to RetailCRM
* Improved mechanics of delivery type transfer to RetailCRM
* Fixed an error when uploading a single order in admin panel

## v.3.0.5
* Fixed errors in the twig template
* Added processing of the history of changes when saving settings for setting the current sinceId, if the history in RetailCRM is empty

## v.3.0.4
* Added checking the user group in the order when editing
* Added transfer of discount for bonus points

## v.3.0.3
* Fixed a bug with changing the user's password

## v.3.0.2
* Improved the mechanics of uploading changes from RetailCRM to the site
* Improved the mechanics of selecting delivery types on the site
* Added the ability to periodically upload promotional prices for products
* Improved compatibility with Opencart 3.0

## v.2.4.3
* Minor bug fixes, added error output when uploading individual orders

## v.2.4.2
* Improved synchronization of custom fields
* Added settings for default delivery and payment types
* Improved Daemon Collector configuration
* Improved the twig template for compatibility with Opencart 3.0

## v.2.4.1
* Fixed history of changes (improved address processing, added order processing with empty fields, improved customer history)
* History is now synced by sinceId
* Checking available API versions using the /api/versions method
* Added the ability to map custom fields (for API V5)

## v.2.4.0
* Added the ability to work on 3 API versions (v3, v4, v5)
* Added compatibility with Opencart 3.0

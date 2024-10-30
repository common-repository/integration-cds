=== Dataverse Integration ===
Contributors: alexacrm, georgedude, wizardist
Tags: Dataverse, Dynamics 365, Dynamics CRM, Microsoft CRM, Power Apps
Requires at least: 5.4
Tested up to: 6.5.2
Requires PHP: 8.2
Stable tag: trunk
License: MIT
License URI: https://opensource.org/licenses/MIT

The easiest way to integrate WordPress with Dataverse (formerly Common Data Service), PowerApps, Dynamics 365, or Dynamics CRM.

== Description ==

This plugin directly connects WordPress with Dataverse (formerly Common Data Service), Dynamics 365 / CRM, creating powerful portal solutions for your business.

[Dataverse](https://docs.microsoft.com/en-au/powerapps/maker/data-platform/data-platform-intro) (formerly Common Data Service) lets you securely store and manage data that's used by business applications. Data from your Dynamics 365 applications is also stored within the Dataverse allowing you to quickly build apps which leverage your Dynamics 365 data and extend your apps using PowerApps.

The plugin extends the Power platform to WordPress and provides full access to the Dataverse data. Written from ground-up, the plugin uses Web API to communicate with Dataverse.

= Features =

* 100% Web API-based - future-proof investment.
* Secure server-to-server authentication. No more usernames or passwords.
* Create custom forms in WordPress and map them to Dataverse tables and columns for create or update operations. Write data from the forms directly to Dataverse / CDS / Dynamics 365.
* Collect leads, contact requests, support queries and much more without any coding.
* Query Dataverse / Dynamics 365 records using FetchXML language. Give your customers direct access to product catalogs, event lists, knowledge base articles.
* Create custom layouts for Dataverse / Dynamics 365 data using powerful and flexible [Twig template engine](https://twig.symfony.com/). Display data directly from Dataverse / Dynamics 365 without any coding.
* Bind WordPress posts and pages to Dataverse / Dynamics 365 records. Build a customized record view in WordPress like product information sheets.
* Extensible through WordPress [actions and filters](https://codex.wordpress.org/Plugin_API).

= Requirements =

This plugin requires PHP 8.2 or greater. cURL and intl extensions are required as well.

= Documentation =

Plugin documentation is available at [docs.alexacrm.com/](https://docs.alexacrm.com/).

= Disclaimer =

For this plugin to work, access to a working instance of Dynamics 365 Customer Engagement or Dataverse for Apps is required. Without that the plugin is absolutely useless. Please, do not raise issues related to that fact. If you are curious to try, you can always sign up for a [free trial of Dynamics 365](https://trials.dynamics.com/).

== Installation ==

Installing Dataverse Integration is just like any other WordPress plugin:

* Navigate to Admin Area / **Plugins** page
* In the search field enter **Dataverse Integration**, then click **Search Plugins**, or press Enter
* Select **Dataverse Integration** and click **Install Now**
* Once installed, click **Activate**
* Navigate to **Dataverse Integration** in the Admin Area to proceed to initial configuration.

= Initial configuration =

To get the plugin up and running you need to specify connection settings on the **Connection** tab.

You can learn how to get the required credentials, set up initial connection and start building your first portal
in the [Getting Started](https://docs.alexacrm.com/integration-cds/getting-started/) tutorial at docs.alexacrm.com.

== Changelog ==

= 2.67 =

* Improved debugging in Twig templates
* Updated documentation links
* Minor bug fixes

= 2.66.1 =

* Removed outdated files cache adapter in favor of default Filesystem adapter

= 2.66 =

* Allow to use local date and time fields in forms and templates
* Fixed incorrect rendering of Twig templates with debug information

= 2.65.2 =

* Fixed incorrect submitting for forms with recaptcha enabled

= 2.65.1 =

* Fixed rendering reCAPTCHA v3 and v2 invisible

= 2.65 =

* Updated cache package to the latest version
* Improved PHP 8.2 compatibility
* Fixed using default records limit for views

= 2.64 =

* PHP 8.2 or greater is now required
* Fixed log files management and downloads
* Updated toolkit dependency

= 2.63.1 =

**PHP Version Support Notice**
This version of the plugin is the final release to support PHP 7.4, 8.0, and 8.1. Future releases will require PHP 8.2 or higher. We will continue to backport critical security fixes to this version for PHP 7.4, 8.0, and 8.1.
Moving forward, the plugin will require PHP versions that are actively supported according to the [official PHP supported versions](https://www.php.net/supported-versions.php).

* Fixed errors handling in Twig templates

= 2.63 =

* Added expand filter for Twig templates
* Added detection for mobile devices in Twig templates
* Fixed caching search results in lookup dialog
* Raised WordPress tested version to 6.5.2

= 2.62 =

* Added settings panel to admin area
* Fixed possible security vulnerability with log file names
* Updated toolkit dependency

= 2.61 =

* Maintenance release

= 2.60 =
* Added compatibility with PHP 8
* Updated 3rd party dependencies
* Added solution info to Connection details in the admin area

= 2.59 =

* Fixed file logs initializing in case the log file is not writable
* Fixed 'Notify administrator about logged events' settings
* Added entity display name in Entities list in admin area

= 2.58 =

* Maintenance release

= 2.57 =

* Fixed rendering image_url and file_url functions in Twig in case some parameters is missing
* Fixed caching specific data types

= 2.56 =

* Fixed loading lodash dependency in the admin area
* Changed URL for image_url and file_url functions in Twig
* Raised WordPress tested version to 6.4.2
* Removed outdated user data migration

= 2.55 =

* Fixed loading custom script files in forms
* Fixed fetching a list of available addons
* Fixed a return value type in the delete cache endpoint

= 2.54 =

* Fixed deleting missing cache types when deleting all caches, improved cache handling
* Changed composer libraries building to increase compatibility with third party plugins

= 2.53 =

* Fixed deleting various cache types
* Fixed saving table records cache settings
* Fixed detecting if addon is active

= 2.52 =

* Added cache panel in admin area with more caching features
* Allowed to delete more cache types
* Updated toolkit dependency

= 2.51.1 =

* Updated WordPress tested version

= 2.51 =

* Maintenance release

= 2.50 =

* Maintenance release

= 2.49.1 =

* Fixed crash when trying to read an undefined variable in user binding settings

= 2.49 =

* Maintenance release

= 2.48 =

* Allowed to use boolean type attributes without values in form tag for Twig templates
* Fixed checking for required PHP extensions
* Fixed minor bugs

= 2.47.1 =

* Fixed unsupported browsers detection upon forms rendering

= 2.47 =

* Added abillity to configure error notifications
* Fixed error message in Twig in case requested record is not found
* Fixed displaying logical names instead of display names in entities list
* Fixed a custom schedule in cron job for error notifications

= 2.46 =

* Updated Twig to v3.5.0
* Updated Twig Intl Extension to v3.5.0
* Fixed script loading conflict with Elementor plugin at the post editor page in admin area

= 2.45 =

* Minor bug fixes and improvements

= 2.44.1 =

* Maintenance release

= 2.44 =

* Fixed using Twig cache to storing templates
* Minor improvements of the admin area UI
* Updated toolkit dependency

= 2.43 =

* Fixed loading assets if WordPress is installed in a subdirectory

= 2.42.1 =

* Fixed incompatibility with version 2.x of the Monolog library

= 2.42 =

* Added extra fields for Image and File column types in Twig templates
* Improved caching errors handling when working with metadata

= 2.41 =

* Maintenance release

= 2.40 =

* Add REST API endpoints to reset user password for specified user and to retrieve a resetting link
* Add links to previous versions of the addon to the addon card if any available
* Add loading indicator to the addons page upon actions
* Fix an entity display name in various entities lists
* Update Monolog library to v1.27.1

= 2.39 =

* Allow to install and manage addons from the admin area
* Allow to disable Monaco editor at the WordPress admin pages
* Fixed service messages formatting in admin area
* Optimized the size of the plugin

= 2.38 =

IMPORTANT!
If your installation includes premium plugin, please download and install updated version of the Dataverse solution in your Dataverse instance: https://wpab.alexacrm.com/release/WordPressIntegration_1_2_0_0_managed.zip

* Add REST API endpoint for getting plugin settings
* Apply a custom bootstrap theme to the admin panel
* Handle REST API request errors in some cases and show error messages correspondingly

= 2.37 =

* Allow extending plugin admin panel with custom pages
* Fix layout and styles for navigation bar in admin area

= 2.36 =

* Redesigned admin area look and feel
* Fixed minor bugs

= 2.35 =

* Added compatibility with WordPress 6.0

= 2.34.3 =

* Maintenance release

= 2.34.2 =

* Fixed a crash on a fresh install or during update in some cases

= 2.34.1 =

* Maintenance release

= 2.34 =

* Added translate filter for Twig templates
* Fixed entities list sorting
* Minor IU improvements

= 2.33 =

* Changed entities selection filter to ALL by default
* Fixed labels for the list of entities in the entities selection

= 2.32 =

* Added detection for unsupported browsers on form rendering

= 2.31 =

* Fixed creating Dataverse Twig block from Custom HTML block in Gutenberg editor

= 2.30 =

* Allow flushing cache separately by type
* Fixed loading theme customizer with Dataverse Integration enabled
* Updated Twig to v3.3.8

= 2.29 =

* Fixed minor bugs

= 2.28.1 =

* Extended the required php-extensions list
* Lowered the error reporting level for missing entity attributes on deserialization

= 2.28 =

* Updated Twig to v3.3.7
* Updated Twig Intl Extension to v3.3.5
* Allow conditional access to entities in templates using custom filter

= 2.27 =

* Maintenance release

= 2.26 =

* Maintenance release

= 2.25 =

* Add more debug logging for Gutenberg editor block init
* Allow specifying Sdk client version for connection using advanced settings

= 2.24 =

* Improved advanced settings and added auto-save feature
* Added entity filter option to the advanced settings

= 2.23.3 =

* Fix incorrect logger initialization in some cases

= 2.23.2 =

* Remove WP_DEBUG flag dependency for Query Monitor logging
* Added more logs for caching

= 2.23.1 =

* Rollback to store database logs as transient
* Allow setting up database logs expiration time
* Remove redundant debug info from logs

= 2.23 =

* Improved database logs
* More reliable check for required php extensions

= 2.22 =

* Allowed saving logs to the database
* Added 'Remove logs' feature
* Improved integration with the Query Monitor

= 2.21 =

* Allowed authentication using certificate file
* Added integration with Query Monitor for extended logging
* Added more logging for Dataverse requests

= 2.20.1 =

* Maintenance release

= 2.20 =

* Allow extending advanced settings in admin area
* Added automatic error notification for administrators
* Updated documentation

= 2.19.1 =

* Restored displaying all the entities in the entities list if no filter defined

= 2.19 =

* Maintenance release

= 2.18.2 =

* Fixed custom form submission messages

= 2.18.1 =

* Added multilingual support
* Added environment validation (PHP version and PHP modules)

= 2.18 =

* Added autocomplete for view (entity, lookups, count, parameters) attributes
* Added autocomplete for form (lookup_filter, entity, keep, recaptcha) attributes

= 2.17 =

* Add custom Wordpress hooks and javascript events to customize form submission process
* Allow to setup advanced settings via administrative UI
* Fix whitespace characters rendering in Monaco editor

= 2.16 =

* Add Rest Api endpoint to flush cache separately by type
* Add option for Monaco editor to show whitespace characters
* Add forms loading animation on frontend pages
* Remove preloading any entity metadata by default and allow to specifiy entity metadata to preload
* Fix wp_cache_delete_group function is not defined in case 3rd party cache plugins not implement it

= 2.15 =

* Maintenance release

= 2.14 =

* Minor bug fixes and improvements

= 2.13 =

* Maintenance release

= 2.12 =

* Added support for Wordpress Object Cache
* Updated Monaco editor
* Fixed crash in case no cache storage is provided

= 2.11 =

* Maintenance release

= 2.10 =

* No changes in the free plugin

= 2.9 =

* Fixed converting outdated Plain Twig block to Monaco Twig block for the page editor

= 2.8.2 =

* Fixed checking premium license issue

= 2.8.1 =

* Added Monaco Twig block for the page editor
* Removed Plain Twig block from the page editor

= 2.8 =

* Unified authentication keys format

= 2.7.1 =

* Add logging for missing AUTH_KEY
* Lower the logging level for missing ICDS_FORM_AUTH_KEY to warning
* Fix exposing application secret in debug logs

= 2.7 =

* Add multi-select picklist field type support in custom forms

= 2.6.3 =

* Fix dashboard icon url

= 2.6.2 =

* Allow to use custom auth key for credentials encryption
* Replace plugin icon in the dashboard

= 2.6.1 =

* Added Twig Intl Extension to support related filters

= 2.6 =

* Improve logging system

= 2.5.1 =

* No changes in the free plugin

= 2.5 =

* Added UI to manage webhooks

= 2.4 =

* Enhanced access to annotation files, entity files and images via display/download endpoints
* Use the EntityReference|json as lookup value in custom forms
* Custom webhook API for advanced integration scenarios
* Parameterized redirects in custom forms

= 2.3.1 =

* Fixed: styling for entity and user binding UI
* Fixed: premium updates and announcements

= 2.3 =

Plugin renamed to **Dataverse Integration**

* Better indication of progress when configuring connection to Dataverse
* Support annotation images and files via image_url() and file_url() in Twig
* Fixed: error message not displayed if an error occurs when configuring connection to Dataverse
* Fixed: dismissing notifications, alerts not working

= 2.2 =

* Fixed: crash on 32bit systems in Admin UI
* Fixed: premium solution discovery
* Fixed: incorrect parsing of some log lines

= 2.1.1 =

* Fixed: crash with some reCAPTCHA configurations
* Fixed: crash during parsing of some log lines

= 2.1 =

* New: Upload files and images into File / Image columns in Dataverse tables via custom forms
* New: Surface Dataverse images via Twig (currently, not larger than 16MB)
* New: Download Dataverse files via Twig (currently, not larger than 16MB)

= 2.0-beta3 =

* Fresh settings UI look
* Added file upload support in custom forms

= 2.0-beta1 =

* BC: PHP 7.4 or greater is required

= 1.3-beta1 =

* New: Simple Twig block for the Gutenberg editor which helps to avoid templating problems that arise with Custom HTML and Shortcode blocks
* Fixed: HTML forms with reCAPTCHA enabled could not be submitted

= 1.2-beta1 =

* New: Create HTML forms in Twig and capture leads, feedback and a lot more into your Dataverse / Dynamics 365
* New: Configure reCAPTCHA and protect your forms from spam
* Changed: Better log display, debugging details
* Plugin settings UI changes
* Performance improvements

= 1.1.1 =

* Internal changes in the plugin with no effect on functionality

= 1.1 =

* Read the last few log records on the Status tab without downloading log files

= 1.0.1 =

* Minor changes to the Admin UI loading procedure

= 1.0 =

* Initial public release

# H5P extension

## Framework integration
Some notes on framework integration. The H5P documentation on this does not exist. The 'best practice' is to simply 'look into' existing integrations for Drupal And WordPress.

### H5P packages
You can use the H5P Core and Editor libraries to build your integration:

```bash
composer require h5p/h5p-core
composer require h5p/h5p-editor

```

### H5P Core Classes
The H5P Core classes available to you can be found in: `typo3conf/ext/h5p/vendor/h5p/h5p-core/h5p.classes.php`

This class had usefull constants, classes and interfaces.

## Notes

Hub activation -> consent

Before you start
To be able to start creating interactive content you must first install at least one content type.

The H5P Hub is here to simplify this process by automatically installing the content types you choose and providing updates for those already installed.

In order for the H5P Hub to be able to do this, communication with H5P.org is required.
As this will provide H5P.org with anonymous data on how H5P is used we kindly ask for your consent before proceeding.
You can read more on the plugin communication page.

I consent, give me the Hub!

I disapprove

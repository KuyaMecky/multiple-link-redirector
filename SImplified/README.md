# External Link Redirect Plugin

## Description
The External Link Redirect plugin allows you to redirect specific external links to custom destinations. This is useful for affiliate links, tracking, or simply redirecting users to updated URLs.

## Installation
1. Download the plugin files.
2. Upload the plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage
1. The plugin automatically enqueues a JavaScript file that handles the redirection.
2. To add or modify redirects, edit the `$redirects` array in the `enqueue_external_redirect_script` function within the plugin file.

```php
$redirects = [
    'https://shorturl.at/KaDb8' => 'https://82bet.com/#/register?invitationCode=668323190180',
    // Add more redirects here.
];
```

## Example
If you want to add a new redirect, simply add a new entry to the `$redirects` array:

```php
$redirects = [
    'https://shorturl.at/KaDb8' => 'https://82bet.com/#/register?invitationCode=668323190180',
    'https://example.com/old-link' => 'https://example.com/new-link',
];
```

## Changelog
### Version 1.1
- Initial release with basic redirect functionality.

## Author
Michael Tallada

## License
This plugin is licensed under the GPLv2 or later.
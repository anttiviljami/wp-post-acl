# WP Post ACL
[![Latest Stable Version](https://poser.pugx.org/anttiviljami/wp-post-acl/v/stable)](https://packagist.org/packages/anttiviljami/wp-post-acl) [![Total Downloads](https://poser.pugx.org/anttiviljami/wp-post-acl/downloads)](https://packagist.org/packages/anttiviljami/wp-post-acl) [![Latest Unstable Version](https://poser.pugx.org/anttiviljami/wp-post-acl/v/unstable)](https://packagist.org/packages/anttiviljami/wp-post-acl) [![License](https://poser.pugx.org/anttiviljami/wp-post-acl/license)](https://packagist.org/packages/anttiviljami/wp-post-acl)

A simple way to control who can edit WordPress posts or pages.

Adds an Edit Permissions metabox to the post edit page, where you can select which users can edit the post.

Only applies to users of role *editor*.

## Custom Post Types

You can apply ACL rules to custom post types too. Just define your custom post type in wp-config.php like this:

```php
define( 'ACL_POST_TYPES', serialize( [ 'post', 'page', 'my-cpt' ] ) );
```

## Screenshots

### Edit Permissions as administrator on post edit page
![Edit permissions](/assets/screenshot-1.png)

## Installation

### The Composer Way (preferred)

Install the plugin via [Composer](https://getcomposer.org/)
```
composer require anttiviljami/wp-post-acl
```

Activate the plugin
```
wp plugin activate wp-post-acl
```

### The Old Fashioned Way

This plugin is available on the [official WordPress.org plugin directory](https://wordpress.org/plugins/wp-post-acl/).

You can also install the plugin by directly uploading the zip file as instructed below:

1. [Download the plugin](https://github.com/anttiviljami/wp-post-acl/archive/master.zip)
2. Upload to the plugin to /wp-content/plugins/ via the WordPress plugin uploader or your preferred method
3. Activate the plugin


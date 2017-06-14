# WordPress Admin Command Palette 2
A live-search modal for the WordPress Admin.

## Description
The Admin Command Palette (ACP) is a modal window in the WordPress Admin that live searches admin content, which saves you many clicks and page loads. You can:

* Search for and navigate to user-generated content (Posts, Pages, Users, etc.).
* Search for and navigate to WordPress Admin Pages (All Posts, Add New Post, etc.).
* Perform WordPress Admin actions via the ACP or a keyboard shortcut (Publish, Add Media, View Post, etc.).

This plugin makes WordPress Admin user interactions efficient.

## Installation

1. Upload admin-command-palette to the /wp-content/plugins/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Use the keyboard shortcut "shift+shift" to activate the search modal window.
1. See Frequently Asked Questions for further setup instructions.

## Frequently Asked Questions

### How does the search determine a match?

The live search uses a standard WordPress Search Query to match a search keyword against a post title, post content or post excerpt.

### Settings

You can customize the plugin settings on the Admin Command Palette settings page in the WordPress Admin (Settings -> Admin Command Palette).

* Included Post Types (Default: post, page): Select post types from this checkbox group to include them in the search. All registered post types except for navigation menu items are included in this list.
* Included Taxonomies (Default: none): Select taxonomies from this checkbox group to include them in the search. All registered taxonomies are included in this list.

### Keyboard Shortcuts
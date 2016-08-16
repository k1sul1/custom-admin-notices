#Custom admin notices
This WordPress plugin allows you to easily create custom notices that are displayed in WP-admin.

### Option saving is broken at the moment and will be fixed ASAP. Developer has cats to feed.

Installing
---
Easiest way to install is with composer:

`composer require k1sul1/custom-admin-notices dev-master`

But you can also just download this repository as a .zip file, and install it like you normally would.

![screenshot](https://github.com/k1sul1/custom-admin-notices/blob/master/assets/screenshot-1.png?raw=true)

Filters
---
`custom-admin-notices_banner_arguments` allows you to adjust the query parameters. Not sure why you would want to do that, but possible arguments listed here: https://codex.wordpress.org/Class_Reference/WP_Query

`custom-admin-notices_notice_title` allows you to customize the notice title. Don't want a title? Make it empty!

`custom-admin-notices_notice_content` allows you to customize the notice content.

`custom-admin-notices-is_dismissible` allows you to disable or enable dismission of all notices generated by plugin or by post id.

Contributing
---
Contributions welcome! Feature wishlist:

* More hooks!
* Translations?

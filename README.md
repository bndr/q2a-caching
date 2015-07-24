q2a-caching V0.5
===========

Question2Answer Caching Plugin

This plugin is still under construction. Although the trial in a secure test environment is OK. But, Never use in your formal site. And, this (master) branch is under development always. When you download this plugin, please download from newest tagged release version.

Description
===========

This plugin will cache the pages for unregistered users.

Restrictions (important)
=====

1. Welcome message is disabled.
2. "qa_id" cookie is ignored at unlogged in status.
3. Post author ("me") by anonymous users is changed to "anonymous".
4. Post buttons ("edit", "close", "hide", etc) are not displayed to anonymous users.

Installation
===========

1. Move the q2a-caching directory to your qa-plugin directory.
2. Check the permissions of files.
3. Turn on the caching in the Admin->Plugins page
4. Enjoy the caching

Updates
===========

V0.5 (sama55)

1. [2015/07/23] Fix issue of view count [#12](https://github.com/sama55/q2a-caching/issues/12)
2. [2015/07/24] Fix issue of deletion of user [#14](https://github.com/sama55/q2a-caching/issues/14)
3. [2015/07/24] Fix issue of x_queue event [#15](https://github.com/sama55/q2a-caching/issues/15)
4. [2015/07/24] Fix issue of x_approve,x_reject event [#16](https://github.com/sama55/q2a-caching/issues/16)
5. [2015/07/24] Fix issue of u_level event [#17](https://github.com/sama55/q2a-caching/issues/17)
6. [2015/07/24] Fix issue of u_favorite,u_unfavorite event [#18](https://github.com/sama55/q2a-caching/issues/18)
7. [2015/07/24] Fix issue of tag_favorite,tag_unfavorite,cat_favorite,cat_unfavorite event [#19](https://github.com/sama55/q2a-caching/issues/19)
8. [2015/07/24] Fix issue of operability about option panel [#20](https://github.com/sama55/q2a-caching/issues/20)


V0.4 [2015/07/23] sama55

1. Optimize program codes
2. Fix bug about comment form error
3. Fix bug about submit error on admin panel
4. Improve compression efficiency (Replace with Minify)
5. Separate cache folder for PC and mobile themes
6. Restore excluded requests option, and fix bug about root access
7. Remove changes for CSRF protector

V0.3 [2015/07/21] sama55

1. When POST/PUT was requested in admin panel, caches are removed
2. When requests is 404 (Page not found), it is not cached.
3. When cache file is empty, cache is not created.
4. Change compression logic (=> only tab and comment)
5. Change logic of CSRF protector
6. When user is not logged in, welcome notification bar is removed.

V0.2 [2015/07/17] sama55

1. Fix bug (write_cache() in qa-cashing-main.php)
2. Change all options name (For batting risk with other plugin) 
3. Change option names in admin panel
4. Change default expiration time from 7200 to 3600
5. Add Expiration Events
6. Add excluded requests option
7. Add compress option in admin form
8. Add debug output option in admin form
9. Add Reset button in admin form
10. Add Clear cache button in admin form
11. Remove Q2A debug (performance) data from cache
12. Output Q2A debug (performance) data at last process

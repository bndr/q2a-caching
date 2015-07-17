q2a-caching V0.3
===========

Question2Answer Caching Plugin

Description
===========

This plugin will cache the pages for unregistered users. This (master) branch is under development always. When you download this plugin, please download from newest tagged release version.

Installation
===========

1. Move the q2a-caching directory to your qa-plugin directory.
2. Check the permissions of files.
3. Turn on the caching in the Admin->Plugins page
4. Enjoy the caching

Notes
=====

Caching for registered users is being worked on.

Updates
===========

V0.2 [2016/07/17] sama55

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

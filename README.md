q2a-caching 0.2
===========

Question2Answer Caching Plugin 0.2

Description
===========

This plugin will cache the pages for unregistered users.

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

V0.2 [2016/07/17]

1. Fix bug (write_cache() in qa-cashing-main.php)
2. Change option names in admin panel
3. Change default expiration time from 7200 to 3600
4. Add compress option in admin form
5. Add debug output option in admin form
6. Add Reset button in admin form
7. Remove Q2A debug (performance) data from cache
8. Output Q2A debug (performance) data at last process

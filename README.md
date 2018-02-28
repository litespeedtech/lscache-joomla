LiteSpeed Cache for Joomla
============================

The LiteSpeed Cache plugin is a high-performance cache component for Joomla sites running on a LiteSpeed web server.

The LiteSpeed Cache extension was originally written by LiteSpeed Technologies. It is released under the GNU General Public Licence 
(GPLv3).

See https://www.litespeedtech.com/products/cache-plugins for more information.



Prerequisites
-------------
This version of LiteSpeed Cache requires Joomla 3.x or later and LiteSpeed Web Server 5.2.3 or later.



Installing
-------------
Modify the .htaccess file in the Joomla site directory, adding the following directives:

    <IfModule LiteSpeed>
    CacheLookup on
    RewriteEngine On
    RewriteRule .* - [E=esi_on:1]
    </IfModule>

Download *com_lscache* and *lscache_plugin*, ZIP the directories together and install the zip file using the Joomla Administrator menu: 
*Extensions->Manage->Install->Upload Package File*

Enable the LiteSpeed Cache Plugin using the Administrator menu: *Extensions->Plugins*


Configuration
--------------

Using Joomla Administrator menu: *Components->LiteSpeed Cache* , click the *Options* button to change LiteSpeed Cache settings.



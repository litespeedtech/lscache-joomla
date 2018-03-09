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
    </IfModule>

If your Joomla site has a separate mobile view, please add the following directives:

    <IfModule LiteSpeed>
    RewriteEngine On
    RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC] RewriteRule .* - [E=Cache-Control:vary=ismobile]
    </IfModule>

Download *com_lscache* and *lscache_plugin*, ZIP the directories together and install the zip file using the Joomla Administrator menu: 
*Extensions->Manage->Install->Upload Package File*

Enable the LiteSpeed Cache Plugin using the Administrator menu: *Extensions->Plugins*

Configuration
--------------
Enable caching in global configuration

Using Joomla Administrator menu: *Components->LiteSpeed Cache* , click the *Options* button to change LiteSpeed Cache settings.

Disable Joomla, or any other caching plugin (like jotcahce, recacher and so on)

Then

using Joomla administrator menu: *Components->LiteSpeedCache* , click *Options* button to change LiteSpeedCache settings.

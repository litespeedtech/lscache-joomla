LiteSpeedCache for Joomla
============================

The LiteSpeedCache plugin is a high performance cache plugin for Joomla sites running on a LiteSpeed webserver.

The LiteSpeedCache extension was originally written by LiteSpeed Technologies. It is released under the GNU General Public Licence V3
(GPLv3).

See https://www.litespeedtech.com/products/cache-plugins for more information.



Prerequisites
-------------
This version of LiteSpeedCache requires Joomla 3.x or later and LiteSpeed LSWS Server 5.2.3 or later.



Installing
-------------
Modify .htaccess file in Joomla site directory, adding the following directives:

    <IfModule LiteSpeed>
    CacheLookup on
    </IfModule>

If your Joomla site has a separate mobile view, please add the following directives:

    <IfModule LiteSpeed>
    RewriteEngine On
    RewriteCond %{HTTP_USER_AGENT} Mobile|Android|Silk/|Kindle|BlackBerry|Opera\ Mini|Opera\ Mobi [NC] RewriteRule .* - [E=Cache-Control:vary=ismobile]
    </IfModule>


Download *com_lscache* and *lscache_plugin* directory, ZIP each directory and install the zip file using Joomla administrator menu: 
*Extensions->Manage->install*

Configuration
--------------
Enable caching in global configuration

In: administrator menu: *Extensions->Plugins*

Enable *LiteSpeedCache Plugin*, place plugin last

Disable Joomla, or any other caching plugin (like jotcahce, recacher and so on)

Then

using Joomla administrator menu: *Components->LiteSpeedCache* , click *Options* button to change LiteSpeedCache settings.

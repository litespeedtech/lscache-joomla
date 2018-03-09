LiteSpeed Cache for Joomla
============================
The LiteSpeed LSCache Plugin for Joomla is a high performance, low cost, user friend cache plugin for Joomla sites running on a LiteSpeed web server, it will tremendously speed up your site, reduce server load with minimal management efforts.

For most Joomla sites,  the default setting works well, very few option need to be changed as your demand.

For advanced Joomla sites such as E-Commerce sites, the advanced ESI feature and cache options for logged-in users will help run your complex business site like a static file site. It will tremendously improve your customer experiences.

The smart auto-purge feature will minimize your management works. No more worrying about cache sync problems. LScache will detect that you changed an article, you changed a menu setting, you changed a module setting, or other settings, it will purge related pages automatically.  So you can even set a longer cache expiration time to improve visitor experience, confident that the cache will be purged when relevant setting changes.

Some component, some page may not work well with cache,  we can set flexible exclude rule for those components and pages. We have both simple and easy setting way and powerful complex setting way (support regular expressions).

We'll keep on improve this cache plugin as demand, your opinion and feedback is most precious for us.

The LiteSpeed Cache Plugin was originally written by LiteSpeed Technologies. It is released under the GNU General Public Licence 
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

Download the latest version zip file from github *package* folder and install the zip file using the Joomla Administrator menu: 
*Extensions->Manage->Install->Upload Package File*

Disable other caching plugins if possible to avoid conflicts. 


Configuration
--------------

using Joomla administrator menu: *Components->LiteSpeedCache* , click *Options* button to change LiteSpeedCache settings.

LiteSpeed Cache for Joomla
============================
LiteSpeed Cache for Joomla is a high performance, low cost, user friendly cache plugin. It will tremendously speed up your site, and reduce server load with minimal management efforts.

For most Joomla sites, the default settings work well. Very few options need to be changed.

For advanced Joomla sites such as E-Commerce sites, the advanced ESI feature and cache options for logged-in users will help run your complex business site like a static file site. It will tremendously improve your customer experience.

The smart auto-purge feature will minimize your management needs. No more worrying about cache sync problems. LSCache will detect that you changed an article, you changed a menu setting, or you changed a module setting. Then, it will automatically purge related pages. So, you can set a longer cache expiration time to improve visitor experience, confident that the cache will be purged when the relevant content or settings change.

Some components and some pages may not work well with cache. We can set flexible exclude rules for those components and pages. We have both a simple easy way and a powerful complex way (supporting regular expressions).

We'll keep on improving this cache plugin. Your opinion and feedback is important to us!

The LiteSpeed Cache Plugin was originally written by LiteSpeed Technologies. It is released under the GNU General Public Licence 
(GPLv3).

See https://www.litespeedtech.com/products/cache-plugins for more information.



Prerequisites
-------------
This version of LiteSpeed Cache requires Joomla 3.x or later and LiteSpeed Web Server 5.2.3+ or OpenLiteSpeed.



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

Download the latest version zip file from github `package` folder and install the zip file using the Joomla Administrator menu: 
**Extensions > Manage > Install > Upload Package File**

Disable other page caching plugins if possible to avoid conflicts. 


Configuration
--------------

Using the Joomla administrator menu, navigate to **Components > LiteSpeed Cache**, and click the **Options** button to change LiteSpeed Cache settings.

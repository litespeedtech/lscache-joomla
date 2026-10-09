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
Using Admin Panel -> System -> Install -> Extension  Menu to install Joomla Cache Plugin,  If choose "Install from URL", you may choose the correct plugin download url such as:  https://github.com/litespeedtech/lscache-joomla/raw/master/Joomla4/package/lscache-latest.zip


<details>
  <summary>Click to show details</summary>

![Plugin Installation picture](\/images\/joomlaPluginInstall.gif)
</details>


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

<details>
  <summary>Click to show details</summary>

![Plugin Installation picture](\/images\/joomlaPluginConfig.gif)
</details>


Caching alongside a cookie consent manager
--------------

Joomla's own page cache plugin lets an extension declare, through the `onPageCacheGetKey` event, that its output depends on something the plugin itself can't see, GDPR consent state being the main case: a visitor who accepted or declined cookies can get different markup (embedded videos, tracking pixels, third party iframes) than a visitor who hasn't decided yet. LSCache honours that same event, so any consent manager that already implements it, com_gdpr among others, gets a correctly split cache under LiteSpeed too instead of one shared copy that's wrong for half your visitors.

One setting on the **Components > LiteSpeed Cache > Options > Advanced** tab controls this:

- **Cookies Carrying A Consent Decision** (`consentCookies`, empty by default). A comma separated list of cookie names your consent manager sets once a visitor has actually decided. Empty means this is off, no vary happens at all, that's true whether you've never touched the field or cleared it on purpose. Once you list a name and save, the cache varies by whatever `onPageCacheGetKey` publishes; while a visitor carries none of the listed cookies, LSCache treats them as undecided and serves the shared default copy, so first time visitors, PageSpeed/Lighthouse runs and the auto-recache crawler still benefit from the cache. Only once one of these cookies appears does that visitor get their own cached variant. Only fill this in if you've checked that your site's HTML actually depends on consent state, comparing a few pages with and without the consent cookie present. The most common name across consent managers is `cookieconsent_status`. If your consent manager ever starts filtering markup server-side while this is left empty, the cache will ignore its state and serve tracking scripts to visitors who refused them.

If you don't run a cookie consent manager at all, you don't need to touch this setting: it starts empty, and with no `pagecache` plugin publishing a key this feature already does nothing.


Logging
--------------

Using Admin Panel to set LiteSpeed Cache Component Logging Levels, and enable Global Configuration -> Logging -> Log Almost Everything, then LiteSpeed Cache Plugin Logging will output to system logging file.


<details>
  <summary>Click to show details</summary>

![Plugin Installation picture](\/images\/joomlaPluginDebug.gif)
</details>


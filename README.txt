=== Network Site Details ===
Contributors: rihansen11, ryangusman, hilfans0, dheaardn, ekobahran, meilinaeka, ajidmujaddid, zhafranilham, hilfans, telkomuniversity
Tags: telkomuniversity, multisite, dashboard, export, data
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 4.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A centralized dashboard for managing and exporting data from all subsites in a WordPress Multisite network. Includes Excel export and visual charts.

== Description ==

Network Site Details by Telkom University is a WordPress plugin that helps network administrators manage and export multisite data efficiently. It collects data from all subsites in your multisite network and provides features to export the data in Excel format, along with visual charts for better insights.

Version 1.0 is the initial release and will be continuously improved in the future.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugin screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Network Admin > Dashboard to access the plugin.

== Frequently Asked Questions ==

= What does this plugin do? =  
This plugin collects data from your WordPress multisite network and allows you to export it in Excel format.

= Is this plugin free to use? =  
Yes, this plugin is licensed under GPLv2 or later, so it is free to use and modify.

== Screenshots ==

1. Main dashboard of the Network Site Details plugin.
2. Subsite data table and Excel export button.
3. Charts for post and visitor counts.

== Changelog ==

= 3.7.4 =

Fix: Fixed a fatal error Call to undefined method WP_Site_Query::get() that reappeared on the Network Admin All Sites page by correcting how query variables are accessed.

= 3.6.1 =

Fix: Fixed an issue where the shortcode dashboard would not display statistics and the site list after the caching system update.

Fix: Fixed the messy CSS layout of the control panel and statistics cards on the shortcode dashboard.

= 3.6.0 =

Feature: Implemented a caching system (WordPress Transients API) on the shortcode dashboard to drastically improve performance.

Tweak: Dashboard data is now cached for 1 month to reduce server load and speed up page load times.

Tweak: The "Refresh Data" button now correctly clears the cache and forces a recalculation of the latest data.

= 3.5.0 =

Feature: Added a "Subsite Content Growth per Year" line chart to the top of the shortcode dashboard.

Tweak: Added titles and labels for the X/Y axes on the chart for data clarity.

Tweak: The "Active Sites" statistics card is now clickable to filter for sites active within the last 3 months.

Fix: Fixed a CSS issue where pagination numbers were not visible on some themes.

Tweak: Reverted the Grid view design to the more detailed and informative card model.

= 3.4.0 =

Feature: Re-enabled the [network_site_details_report] shortcode functionality to display the report dashboard on public pages.

Feature: Completely redesigned the shortcode display into a modern and responsive interactive dashboard.

Feature: Added key statistics (Total Sites, Total Posts, Active Sites, Avg Posts/Site).

Feature: Added interactive controls: Search, Sort, Filter by Year, Pagination, and Grid/List view toggle.

Tweak: Applied the Telkom University color scheme to the dashboard design.

= 3.2.0 =

Tweak: Adjusted the CSV export functionality to only include the columns: Site Name, URL, Last Updated, Registered, Users, and Post Count.

Tweak: Removed the "Last Login User" column from the admin page for consistency.

Optimization: Implemented batch processing for the CSV export feature to prevent timeouts on large-scale networks.

= 3.1.0 =

Feature: Added an "Export to CSV" button on the Network Admin All Sites page.

= 3.0.0 =

Feature: Added the ability to sort the "Post Count" column on the admin page.

Fix: Fixed a fatal error Call to undefined method WP_Site_Query::get() that occurred when trying to sort.

= 2.0.0 =

Refactor: Removed the custom "Site Details" admin page.

Feature: Integrated functionality directly into the native WordPress "All Sites" page.

Feature: Added "Post Count" and "Last Login User" columns.

Feature: Made the new columns configurable via the "Screen Options" menu.

= 1.0.0 =

Initial version. Created a new "Site Details" admin page to display a basic report.

== Upgrade Notice ==

= 1.0 =  
Initial release of Network Site Details plugin for WordPress Multisite.

== Usage ==

- After activation, go to Network Admin > Dashboard to view multisite data.
- Use the "Export to Excel" button to download data in Excel format.
- View charts and tables for multisite data analysis.

== Support ==

For support and bug reports, please visit:  
[https://github.com/rihansen11/NetworkSiteDetail](https://github.com/rihansen11/NetworkSiteDetail)

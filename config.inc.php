<?php
// Navigation config (requires settings plugin)
//$GLOBALS['settingsnav']['summary'] = array('part' => '', 'label' => 'accountsummary', 'href' => '#', 'onclick' => 'parent.location.href="./?_task=mail&_action=plugin.summary"', 'descr' => 'summary');

/* Show warnings if folder contains more than x messages */
$config['sent_warning'] = 1;
$config['junk_warning'] = 1;
$config['trash_warning'] = 10;

/* Allow purge of the following folders */
$config['sent_purge'] = true;
$config['junk_purge'] = true;
$config['trash_purge'] = true;

/* Show summary even if disabled when
   running out of quota */
$config['alert_quota_on'] = true;
$config['alert_quota_pct'] = 80;

/* Show summary even if disabled when
   MOTD file (./plugins/summary/motd/[en_US].hmtl
   changed */
$config['motd_changed'] = true;

/* In order to disallow user to disable summary page or to hide timezone section
   use Roundcube 'dontoverride' directive: */
$config['dont_override'] = array('nosummary');


/* Log last login data */
$config['summary_log_lastlogin'] = false;
$config['summary_log_lastlogin_ip'] = false;
$config['summary_link_geoiptool'] = false; // Use GeoIP tools provided by MyRoundcube

/* Use external database for GeoIP tracking
   Note: If you use this option then you have to run SQL scripts located in ./plugins/summary/SQL manually.
         First run 'initial' script and then all others in order of dates (ascending) */
//$config['summary_db_dsn'] = false; //'mysql://root:pass@localhost/geoip?new_link=true';

/* Geo location distance to force solving question/secret answer for login
   Note: - false = disable feature, integer = distance in kilometer
         - feature requires pwtools plugin */
$config['double_login_distance'] = false; // 100;
?>
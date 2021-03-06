<?php
/*
# 
# This file is part of Roundcube "summary" plugin.
# 
# This file is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
# 
# Copyright (c) 2019 PICCORO Lenz McKAY - info with osposweb@gmail.com
# Copyright (c) 2012 - 2015 Roland 'Rosali' Liebl - dev-team [at] myroundcube [dot] net
#
# http://fusilsystem.com
# 
# 2017 query to ip address vistited are not executed
# 2019 first removed depends in require settings plugin
# 2019 libgpl depends removed
*/
class summary extends rcube_plugin
{
    var $task = 'login|mail|settings|addressbook|calendar|tasks';
    private $geoipservice = 'https://geoip.myroundcube.com/json/'; //TODO integrate with roundcube geoip plgin?
    private $db;
    static private $plugin = 'summary';
    static private $author = 'osposweb@gmail.com';
    static private $authors_comments = '<a href="https://gitlab.com/roundcubevnz/roundcube-plugin-summary" target="_blank">https://gitlab.com/roundcubevnz/roundcube-plugin-summary</a>';
    static private $version = '4.0.2.1';
    static private $date = '26-04-2015';
    static private $licence = 'CC BY SA roundcubevnz';
    static private $requirements = array('Roundcube' => '1.0', 'PHP' => '5.3', 'required_plugins' => array(), 'recommended_plugins' => array('pwtools' => 'config'));
    static private $prefs = array('nosummary');
    static private $config_dist = 'config.inc.php.dist';
    static private $sqladmin = array('db_dsnw', 'summary');
    static private $tables = array('summary', 'blacklist', 'blacklistcandidates', 'geoip');
    static private $db_version = array('initial', '20140120', '20140122', '20140313', '20140331'); // this are used for autoinstall does not work with normal roundcube
    function init()
    {
        $rcmail = rcmail::get_instance();
        if ($rcmail->action == 'jappix.loadmini') {
            return;
        }
        if (is_dir(INSTALL_PATH . 'plugins/db_version')) {
            $this->require_plugin('db_version');
            if (!$load = db_version::exec(self::$plugin, self::$tables, self::$db_version)) {
                return;
            }
        }
        /*$this->require_plugin('libgpl');*/
        if (!in_array('global_config', $rcmail->config->get('plugins'))) {
            $this->load_config();
            /*$this->require_plugin('settings');*/
        }
        if ($dsn = $rcmail->config->get('summary_db_dsn')) {
            $this->db = rcube_db::factory($dsn, '', false);
            $this->db->set_debug((bool) $rcmail->config->get('sql_debug'));
            $this->db->db_connect('r');
        } else {
            $this->db = $rcmail->db;
        }
        $this->add_texts('localization/', false);
        $this->add_hook('login_after', array(
            $this,
            'login_after'
        ));
        $this->add_hook('render_page', array(
            $this,
            'render_page'
        ));
        $this->add_hook('template_object_summary_mailboxes', array(
            $this,
            'summary_html'
        ));
        $this->add_hook('template_object_summary_lastlogin', array(
            $this,
            'summary_html_lastlogin'
        ));
        $this->add_hook('template_object_summary_timezone', array(
            $this,
            'summary_html_timezone'
        ));
        $this->add_hook('template_object_summary_disable', array(
            $this,
            'summary_html_disable'
        ));
        $this->add_hook('template_object_summary_motd', array(
            $this,
            'summary_html_motd'
        ));
        $this->add_hook('template_object_summary_quota', array(
            $this,
            'summary_html_quota'
        ));
        $this->add_hook('preferences_list', array(
            $this,
            'prefs_table'
        ));
        $this->add_hook('preferences_save', array(
            $this,
            'save_prefs'
        ));
        $this->register_action('plugin.summary_refresh', array(
            $this,
            'refresh'
        ));
        $this->register_action('plugin.summary_getClientTimezone', array(
            $this,
            'getClientTimezone'
        ));
        $this->register_action('plugin.summary', array(
            $this,
            'summary_startup'
        ));
        $this->register_action('plugin.summary_show', array(
            $this,
            'summary_show'
        ));
        $this->register_action('plugin.summary_expunge', array(
            $this,
            'summary_expunge'
        ));
        $this->register_action('plugin.summary_purge', array(
            $this,
            'summary_purge'
        ));
        $this->register_action('plugin.summary_disable', array(
            $this,
            'summary_disable'
        ));
        $this->register_action('plugin.summary_suspicious', array(
            $this,
            'summary_suspicious'
        ));
        $this->register_action('plugin.summary_geoip', array(
            $this,
            'summary_geoip'
        ));
        if ($_GET['_action'] == 'plugin.summary_geoip_db') {
            $this->summary_geoip_db();
        }
        $this->register_action('plugin.summary_answer', array(
            $this,
            'summary_answer'
        ));
    }
    static function about($keys = false)
    {
        $requirements = self::$requirements;
        foreach (array(
            'required_',
            'recommended_'
        ) as $prefix) {
            if (is_array($requirements[$prefix . 'plugins'])) {
                foreach ($requirements[$prefix . 'plugins'] as $plugin => $method) {
                    if (class_exists($plugin) && method_exists($plugin, 'about')) {
                        $class                                      = new $plugin(false);
                        $requirements[$prefix . 'plugins'][$plugin] = array(
                            'method' => $method,
                            'plugin' => $class->about($keys)
                        );
                    } else {
                        $requirements[$prefix . 'plugins'][$plugin] = array(
                            'method' => $method,
                            'plugin' => $plugin
                        );
                    }
                }
            }
        }
        $config = array();
        if (is_string(self::$config_dist)) {
            if (is_file($file = INSTALL_PATH . 'plugins/' . self::$plugin . '/' . self::$config_dist))
                include $file;
            else
                write_log('errors', self::$plugin . ': ' . self::$config_dist . ' is missing!');
        }
        $ret = array(
            'plugin' => self::$plugin,
            'version' => self::$version,
            'db_version' => self::$db_version,
            'date' => self::$date,
            'author' => self::$author,
            'comments' => self::$authors_comments,
            'licence' => self::$licence,
            'requirements' => $requirements,
            'sqladmin' => self::$sqladmin
        );
        if (is_array(self::$prefs))
            $ret['config'] = array_merge($config, array_flip(self::$prefs));
        else
            $ret['config'] = $config;
        if (is_array($keys)) {
            $return = array(
                'plugin' => self::$plugin
            );
            foreach ($keys as $key) {
                $return[$key] = $ret[$key];
            }
            return $return;
        } else {
            return $ret;
        }
    }
    function refresh()
    {
        $rcmail              = rcmail::get_instance();
        $response            = array();
        $mailbox             = $this->summary_html(array());
        $response['mailbox'] = $mailbox['content'];
        $motd                = $this->summary_html_motd(array());
        $response['motd']    = $motd['content'];
        $quota               = $this->summary_html_quota(array());
        $response['quota']   = $quota['content'];
        $rcmail->output->command('plugin.summary_refresh', $response);
    }
    function getClientTimezone()
    {
        $rcmail = rcmail::get_instance();
        $cltz   = (int) get_input_value('_cltz', RCUBE_INPUT_POST) * 60;
        $m      = '+';
        if ($cltz > 0)
            $m = '-';
        $cltz = $m . date('H:i', strtotime('2000-06-30 24:00:00') - $cltz);
        $html = $this->_summary_html_timezone($cltz, get_input_value('_dst', RCUBE_INPUT_POST));
        $rcmail->output->command('plugin.summary_getClientTimezone', $html);
    }
    function redirect()
    {
        if (isset($_GET['_framed'])) {
            header('Location: ./?_task=mail&_action=plugin.summary_show&_msg=successfullyprocessed&_framed=1');
        } else {
            header('Location: ./?_task=mail&_action=plugin.summary&_msg=successfullyprocessed');
        }
        exit;
    }
    function login_after($args)
    {
        $rcmail = rcmail::get_instance();
        if ($geoip = get_input_value('_geoip', RCUBE_INPUT_POST)) {
            $ip = $geoip['ip'];
            foreach ($geoip as $field => $value) {
                $_SESSION['geoip'][$ip] = $geoip;
            }
        } else {
            $ip = $this->getVisitorIP();
        }
        $sql = 'DELETE FROM ' . get_table_name('blacklist') . ' WHERE ts < ?';
        $rcmail->db->query($sql, time() - 3600);
        $sql    = 'SELECT * FROM ' . get_table_name('blacklist') . ' WHERE ip=?';
        $result = $rcmail->db->limitquery($sql, 0, 1, $ip);
        $result = $rcmail->db->fetch_assoc($result);
        if (is_array($result)) {
            $sql = 'DELETE FROM ' . get_table_name('blacklistcandidates') . ' WHERE ip=?';
            $rcmail->db->query($sql, $ip);
            header('Location: ./?_task=logout&_err=pwtools.locked');
            exit;
        }
        $args['_task']   = 'mail';
        $args['_action'] = 'plugin.summary';
        return $args;
    }
    function render_page($p)
    {
        $rcmail = rcmail::get_instance();
        if ($rcmail->task == 'logout' || $_GET['_task'] == 'logout' || $_GET['_action'] == 'logout') {
            return $p;
        }
        if ($p['template'] != 'summary.summary' && $_SESSION['suspiciouslogin']) {
            header('Location: ./?_action=plugin.summary&_task=mail');
            exit;
        }
        if ($msg = get_input_value('_msg', RCUBE_INPUT_GPC)) {
            $rcmail->output->show_message('summary.' . $msg, 'confirmation');
        }
        if (!isset($_GET['_extwin']) && $rcmail->config->get('skin') == 'larry' && $_SESSION['username']) {
            $href    = './?_task=mail&_action=plugin.summary&_topnav=1';
            $onclick = '';
            if (class_exists('tabbed')) {
                $href    = '#';
                $onclick = 'onclick=parent.location.href=\'./?_task=mail&_action=plugin.summary&_topnav=1\'';
            }
            if ($p['template'] == 'summary.summary') {
                $href = '#';
            }
            $rcmail->output->add_script('$(".topleft").html($(".topleft").html() + "<a ' . $onclick . ' id=\'summarylink\' href=\'' . $href . '\'>' . $this->gettext('accountsummary') . '</a>");', 'foot');
        }
        if ($p['template'] == 'settings') {
            if ($section = get_input_value('_section', RCUBE_INPUT_GET)) {
                $rcmail->output->add_script('$("#rcmrow' . $section . '").children().trigger("mousedown");', 'docready');
            }
        }
        $curIP = $this->getVisitorIP();
        /*$sql='UPDATE '.get_table_name('summary').' SET ts=?, ip=? WHERE user_id=?';$rcmail->db->query($sql,date('Y-m-d H:i:s'),$curIP,$rcmail->user->ID);*/
        if ($p['template'] == 'login') {
            if ($this->geoipavailable($this->geoipservice)) {
                $ip     = $this->inet_aton($curIP);
                $sql    = 'SELECT * FROM ' . get_table_name('geoip') . ' WHERE ip=?';
                $result = $this->db->query($sql, $ip);
                $result = $this->db->fetch_assoc($result);
                if (1 == 2 && is_array($result) && mt_rand(0, 9) != 5) {
                    $script = '';
                    $js     = '$("<input type=\'hidden\' name=\'##name##\' value=\'##value##\' />").appendTo("form");';
                    foreach ($result as $field => $value) {
                        if ($field != 'id') {
                            if ($field == 'ip') {
                                continue;
                            }
                            if ($field == 'ipv4') {
                                $field = 'ip';
                            }
                            $script .= str_replace('##value##', $value, str_replace('##name##', '_geoip[' . $field . ']', $js));
                        }
                    }
                    $rcmail->output->add_script($script, 'docready');
                } else {
                    $rcmail->output->set_env('double_login_distance', $rcmail->config->get('double_login_distance', false));
                    $this->include_script('geoip.js');
                    $rcmail->output->add_script('$.ajax({ type: "GET", timeout: 3000, url: "' . $this->geoipservice . $curIP . '", dataType: "jsonp", success: function(data){ summary_inject_geoip(data, 1, true) } });', 'docready');
                }
            }
        }
        return $p;
    }
    function summary_show()
    {
        $rcmail = rcmail::get_instance();
        $skin   = $rcmail->config->get('skin', 'classic');
        $this->include_stylesheet('skins/' . $skin . '/summary.css');
        $rcmail->output->send("summary.iframe");
        exit;
    }
    function summary_startup()
    {
        $rcmail = rcmail::get_instance();
        $force  = false;
        if ($rcmail->config->get('motd_changed')) {
            $lang = $rcmail->user->data['language'];
            $ts   = false;
            if (file_exists(INSTALL_PATH . 'plugins/summary/motd/' . $lang . '.html')) {
                $ts = @filemtime(INSTALL_PATH . 'plugins/summary/motd/' . $lang . '.html');
            } else if (INSTALL_PATH . 'plugins/summary/motd/en_US.html') {
                $lang = 'en_US';
                $ts   = @filemtime(file_exists(INSTALL_PATH . 'plugins/summary/motd/' . $lang . '.html'));
            }
            if ($ts) {
                $sql = 'SELECT * FROM ' . get_table_name('system') . ' WHERE name=?';
                $res = $rcmail->db->limitquery($sql, 0, 1, 'myrc_summary_motd_' . $lang);
                $res = $rcmail->db->fetch_assoc($res);
                if (!is_array($res)) {
                    $sql = 'INSERT INTO ' . get_table_name('system') . ' (name, value) VALUES (?, ?)';
                    $rcmail->db->query($sql, 'myrc_summary_motd_' . $lang, $ts);
                    $force = true;
                } else if ($ts != $res['value']) {
                    $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                    $rcmail->db->query($sql, $ts, 'myrc_summary_motd_' . $lang);
                    $force = true;
                } else if ($ts != $rcmail->config->get('summary_motd_ts')) {
                    $force = true;
                }
            }
        }
        if (!$rcmail->config->get('nosummary') || $force || isset($_GET['_topnav']) || ($rcmail->config->get('alert_quota_on', true) && $this->_alert_quota_do() == true)) {
            if ($force) {
                $a_prefs = array(
                    'summary_motd_ts' => $ts
                );
                $rcmail->user->save_prefs($a_prefs);
            }
            $skin = $rcmail->config->get('skin', 'classic');
            $this->include_stylesheet('skins/' . $skin . '/summary.css');
            $rcmail->output->send("summary.summary");
        } else {
            $rcmail->output->redirect(array(
                '_action' => '',
                '_mbox' => 'INBOX'
            ));
        }
    }
    function prefs_table($args)
    {
        if ($args['section'] == 'general') {
            $rcmail = rcmail::get_instance();
            if (!in_array('nosummary', $rcmail->config->get('dont_override', array()))) {
                $nosummary                                               = $rcmail->config->get('nosummary');
                $field_id                                                = 'rcmfd_summary';
                $checkbox                                                = new html_checkbox(array(
                    'name' => '_nosummary',
                    'id' => $field_id,
                    'value' => 1,
                    'onclick' => '$(".mainaction").hide(); document.forms.form.submit()'
                ));
                $args['blocks']['main']['options']['summary']['title']   = Q($this->gettext('summary.dontusesummary'));
                $args['blocks']['main']['options']['summary']['content'] = $checkbox->show($rcmail->config->get('nosummary'), array(
                    'name' => "_nosummary"
                ));
            }
        }
        return $args;
    }
    private function _alert_quota_do()
    {
        $rcmail = rcmail::get_instance();
        $quota  = $rcmail->imap->get_quota();
        $quota  = $rcmail->plugins->exec_hook('quota', $quota);
        $ret    = false;
        if (empty($quota['total'])) {
            $ret = false;
        } else if ($quota['percent'] > $rcmail->config->get('alert_quota_pct', 80)) {
            $ret = true;
        }
        return $ret;
    }
    function save_prefs($args)
    {
        if ($args['section'] == 'general') {
            $args['prefs']['nosummary'] = get_input_value('_nosummary', RCUBE_INPUT_POST);
        }
        return $args;
    }
    function summary_disable()
    {
        if ($_POST['_summarydisable'] == 1) {
            $rcmail               = rcmail::get_instance();
            $a_prefs['nosummary'] = 1;
            $rcmail->user->save_prefs($a_prefs);
            $rcmail->output->redirect(array(
                '_action' => '',
                '_mbox' => 'INBOX'
            ));
        }
        return;
    }
    function summary_expunge()
    {
        $rcmail = rcmail::get_instance();
        $rcmail->imap->expunge(urldecode(get_input_value('_mbox', RCUBE_INPUT_GET)));
        $this->redirect();
        return;
    }
    function summary_purge()
    {
        $rcmail = rcmail::get_instance();
        $rcmail->imap->clear_mailbox(urldecode(get_input_value('_mbox', RCUBE_INPUT_GET)));
        $this->redirect();
        return;
    }
    function summary_answer()
    {
        $rcmail = rcmail::get_instance();
        $ip     = get_input_value('_ip', RCUBE_INPUT_POST);
        $answer = get_input_value('_answer', RCUBE_INPUT_POST);
        $saved  = $rcmail->decrypt($rcmail->config->get('pwtoolsanswer'));
        $logout = false;
        if ($answer == $saved) {
            $success = true;
            $attempt = 1;
            $sql     = 'UPDATE ' . get_table_name('summary') . ' SET ts=?, ip=? WHERE user_id=?';
            $rcmail->db->query($sql, date('Y-m-d H:i:s'), $ip, $rcmail->user->ID);
            $rcmail->session->remove('suspiciouslogin');
            $rcmail->session->remove('summary_answer_attempt');
            $rcmail->session->remove('geoip');
            $sql = 'DELETE FROM ' . get_table_name('blacklistcandidates') . ' WHERE ip=?';
            $rcmail->db->query($sql, $this->getVisitorIP());
        } else {
            $_SESSION['summary_answer_attempt'] ? ($attempt = $_SESSION['summary_answer_attempt'] + 1) : ($attempt = 1);
            $success                            = false;
            $_SESSION['summary_answer_attempt'] = $attempt;
            if (!isset($_SESSION['summary_suspicious_warning_sent'])) {
                $to = rcube_user::user2email($rcmail->user->data['username'], false, true);
                if (!$to) {
                    $to = $rcmail->user->data['username'];
                }
                $cc                                          = $rcmail->config->get('pwtoolsaddress', false);
                $_SESSION['summary_suspicious_warning_sent'] = $this->sendmail($to, $ip, $cc);
                $this->sendmail($cc, $ip, $to);
            }
            if ($attempt > 3) {
                $logout = true;
                $sql    = 'INSERT INTO ' . get_table_name('blacklist') . ' (ip, ts) VALUES (?, ?)';
                $rcmail->db->query($sql, $this->getVisitorIP(), time());
                $sql = 'DELETE FROM ' . get_table_name('blacklistcandidates') . ' WHERE ip=?';
                $rcmail->db->query($sql, $this->getVisitorIP());
            }
        }
        $rcmail->output->command('plugin.summary_answer', array(
            'attempt' => $attempt,
            'success' => $success,
            'logout' => $logout
        ));
    }
    function summary_geoip()
    {
        $rcmail = rcmail::get_instance();
        $ip     = get_input_value('_ip', RCUBE_INPUT_GPC);
        $nb     = get_input_value('_nb', RCUBE_INPUT_GPC);
        $ret    = array();
        if (is_array($_SESSION['geoip'][$ip])) {
            $ret = $_SESSION['geoip'][$ip];
        }
        $rcmail->output->command('plugin.summary_inject_geoip_session', array(
            'geoip' => $ret,
            'nb' => $nb
        ));
    }
    function summary_geoip_db()
    {
        if ($data = get_input_value('_data', RCUBE_INPUT_GPC)) {
            if ($data = json_decode($data)) {
                if (is_numeric($data->ip)) {
                    $rcmail = rcmail::get_instance();
                    $sql    = 'SELECT * FROM ' . get_table_name('geoip') . ' WHERE ip=?';
                    $result = $this->db->query($sql, $data->ip);
                    $result = $this->db->fetch_assoc($result);
                    if (is_array($result)) {
                        if (!$result['hits']) {
                            $data->hits = 1;
                        } else {
                            if (get_input_value('_hits', RCUBE_INPUT_GPC)) {
                                $data->hits = $result['hits'] + 1;
                            } else {
                                $data->hits = $result['hits'];
                            }
                        }
                        $sql    = 'UPDATE ' . get_table_name('geoip') . ' SET ';
                        $values = array();
                        foreach ($data as $col => $val) {
                            if ($col != 'ip') {
                                $values[] = $val ? $val : '';
                                $sql .= $col . '=?, ';
                            }
                        }
                        $data->ip = $this->inet_aton($data->ip);
                        $values[] = $data->ip;
                        $sql      = substr($sql, 0, strlen($sql) - 2) . ' WHERE ip=?';
                    } else {
                        $sql         = 'INSERT INTO ' . get_table_name('geoip') . ' (';
                        $placeholder = '';
                        $values      = array();
                        $data->hits  = 0;
                        foreach ($data as $col => $val) {
                            $values[] = $val ? $val : '';
                            $sql .= $col . ', ';
                            $placeholder .= '?, ';
                        }
                        $sql = substr($sql, 0, strlen($sql) - 2) . ') VALUES (' . substr($placeholder, 0, strlen($placeholder) - 2) . ')';
                    }
                    $this->db->query($sql, $values);
                }
            }
        }
        header('Content-Type: text/javascript');
        exit;
    }
    function summary_suspicious()
    {
        $rcmail                        = rcmail::get_instance();
        $distance                      = get_input_value('_distance', RCUBE_INPUT_POST);
        $lastip                        = $this->inet_ntoa(get_input_value('_lasthost', RCUBE_INPUT_POST));
        $currentip                     = get_input_value('_currenthost', RCUBE_INPUT_POST);
        $_SESSION['geoip'][$lastip]    = array();
        $_SESSION['geoip'][$currentip] = array();
        foreach ($_POST as $key => $val) {
            if ($key != '_distance' && $key != '_remote') {
                if (substr($key, 1, strlen('last')) == 'last') {
                    $idx = $lastip;
                } else {
                    $idx = $currentip;
                }
                $skey                           = str_replace(array(
                    '_last',
                    '_current'
                ), '', $key);
                $_SESSION['geoip'][$idx][$skey] = get_input_value($key, RCUBE_INPUT_POST);
            }
        }
        $sql = 'UPDATE ' . get_table_name('summary') . ' SET ts=?, ip=? WHERE user_id=?';
        $rcmail->db->query($sql, $_SESSION['lastlogin_ts'], $lastip, $rcmail->user->ID);
        if (file_exists(INSTALL_PATH . 'plugins/pwtools/pwtools.php') && $rcmail->config->get('pwtoolsquestion') && $rcmail->config->get('pwtoolsanswer') && (strtolower($this->get_demo($rcmail->user->data['username'])) != strtolower(sprintf($rcmail->config->get('demo_user_account'), "")))) {
            $this->require_plugin('pwtools');
            $v = pwtools::about(array(
                'version'
            ));
            if (version_compare($v['version'], '3.0', '>=')) {
                $_SESSION['suspiciouslogin'] = true;
                $sql                         = 'INSERT INTO ' . get_table_name('blacklistcandidates') . ' (ip, ts) VALUES (?, ?)';
                $rcmail->db->query($sql, $this->getVisitorIP(), time());
                $rcmail->output->command('plugin.summary_force_secret_qa', array(
                    'distance' => $distance
                ));
            }
        }
    }
    function summary_html_lastlogin($args)
    {
        $rcmail = rcmail::get_instance();
        if ($_SESSION['timezone']) {
            $stz = date_default_timezone_get();
            date_default_timezone_set($_SESSION['timezone']);
            $ts = date('Y-m-d H:i:s', time());
            date_default_timezone_set($stz);
        } else {
            $ts = date('Y-m-d H:i:s', time());
        }
        $_SESSION['lastlogin_ts'] = $ts;
        if ($rcmail->config->get('summary_log_lastlogin', true) && !$_SESSION['impersonate']) {
            $sql    = 'SELECT * FROM ' . get_table_name('summary') . ' WHERE user_id=?';
            $result = $rcmail->db->limitquery($sql, 0, 1, $rcmail->user->ID);
            $result = $rcmail->db->fetch_assoc($result);
            if ($rcmail->config->get('summary_log_lastlogin_ip', true)) {
                $curIP = $this->getVisitorIP();
            } else {
                $curIP = null;
            }
            if (is_array($result)) {
                $sql = 'UPDATE ' . get_table_name('summary') . ' SET ts=?, ip=? WHERE user_id=?';
                $rcmail->db->query($sql, $ts, $curIP, $rcmail->user->ID);
                $rcmail->output->set_env('maps_lang', str_replace('_', '-', $_SESSION['language']));
                $html = '<fieldset id="flastlogin"><legend>&nbsp;' . $this->gettext('lastlogin') . '&nbsp;</legend>';
                $html .= '<br /><div>' . ((($result['ip'] && $curIP) && ($result['ip'] != $curIP)) ? ($this->gettext('youripaddressis') . '&nbsp;' . ($curIP ? ('&nbsp;' . ($rcmail->config->get('summary_link_geoiptool', true) ? (html::tag('a', array(
                    'id' => 'geoiplink1',
                    'href' => '#',
                    'onclick' => '',
                    'title' => 'GeoIP - Google Maps'
                ), $curIP) . '.' . html::tag("span", array(
                    "id" => "geoipcontainer1"
                ))) : ($curIP . html::tag("span", array(
                    "id" => "geoipcontainer"
                ))))) : '') . '</br><br />') : '') . $this->gettext('yourlastloginwason') . '&nbsp;' . html::tag('b', null, date($rcmail->config->get('date_long', 'Y-m-d H:i'), strtotime($result['ts']))) . ($result['ip'] ? ('&nbsp;' . $this->gettext('fromip') . '&nbsp;' . ($rcmail->config->get('summary_link_geoiptool', true) ? (html::tag('a', array(
                    'id' => 'geoiplink2',
                    'href' => '#',
                    'onclick' => '',
                    'title' => 'GeoIP - Google Maps'
                ), $result['ip']) . '.' . html::tag("span", array(
                    "id" => "geoipcontainer2"
                ))) : ($result['ip'] . html::tag("span", array(
                    "id" => "geoipcontainer"
                ))))) : '') . '</div>';
                if ($rcmail->config->get('summary_link_geoiptool', true)) {
                    $geoipservice = $this->geoipservice;
                    if (isset($_SESSION['suspiciouslogin'])) {
                        $geoipservice = false;
                    }
                    if (($curIP && $result['ip']) && ($curIP != $result['ip'])) {
                        $url = $geoipservice . $curIP;
                        $this->include_script('https://maps.google.com/maps/api/js?sensor=false&v=3&libraries=geometry');
                        if (!$geoipservice) {
                            $rcmail->output->add_script('rcmail.http_request("plugin.summary_geoip", "_ip=' . $curIP . '&_nb=1")', 'docready');
                        } else {
                            if (isset($_SESSION['geoip'][$result['ip']])) {
                                $data = $_SESSION['geoip'][$result['ip']];
                            } else {
                                $sql  = 'SELECT * FROM ' . get_table_name('geoip') . ' WHERE ip=?';
                                $res  = $this->db->query($sql, $this->inet_aton($curIP));
                                $data = $this->db->fetch_assoc($res);
                            }
                            if (is_array($data)) {
                                unset($data['id']);
                                $data = json_encode($data);
                                $rcmail->output->add_script('summary_inject_geoip(' . $data . ', 1);', 'docready');
                            } else {
                                $temp = parse_url($url);
                                if ($this->geoipavailable($temp['scheme'] . '://' . $temp['host'])) {
                                    $rcmail->output->add_script('$.ajax({ type: "GET", timeout: 3000, url: "' . $url . '", dataType: "jsonp", success: function(data){ summary_inject_geoip(data, 1) } });', 'docready');
                                }
                            }
                        }
                        $html .= html::tag('div', array(
                            'id' => 'distancecontainer'
                        ), null);
                    }
                    $url = $geoipservice . $result['ip'];
                    $rcmail->output->set_env('double_login_distance', $rcmail->config->get('double_login_distance', false));
                    $this->include_script('geoip.js');
                    $rcmail->output->add_label('summary.city', 'summary.country', 'summary.ipaddress', 'summary.distance', 'summary.or', 'summary.kilometers', 'summary.miles', 'summary.thousand_separator', 'summary.faraway', 'summary.locked', 'summary.lastattempt', 'summary.answerdoesnotmatch', 'summary.answermatch');
                    if (!$geoipservice) {
                        $rcmail->output->add_script('rcmail.http_request("plugin.summary_geoip", "_ip=' . $result['ip'] . '&_nb=2")', 'docready');
                    } else {
                        if (isset($_SESSION['geoip'][$result['ip']])) {
                            $data = $_SESSION['geoip'][$result['ip']];
                        } else {
                            $sql  = 'SELECT * FROM ' . get_table_name('geoip') . ' WHERE ip=?';
                            $res  = $this->db->query($sql, $this->inet_aton($result['ip']));
                            $data = $this->db->fetch_assoc($res);
                        }
                        if (is_array($data)) {
                            unset($data['id']);
                            $data = json_encode($data);
                            $rcmail->output->add_script('summary_inject_geoip(' . $data . ', 2);', 'docready');
                        } else {
                            $temp = parse_url($url);
                            if ($this->geoipavailable($temp['scheme'] . '://' . $temp['host'])) {
                                $rcmail->output->add_script('$.ajax({ type: "GET", timeout: 3000, url: "' . $url . '", dataType: "jsonp", success: function(data){ summary_inject_geoip(data, 2) } });', 'docready');
                            }
                        }
                    }
                }
                if ($rcmail->config->get('pwtoolsquestion') && $rcmail->config->get('pwtoolsanswer')) {
                    $html .= '<br />' . html::tag('div', array(
                        'id' => 'secretquestionanswer',
                        'style' => 'display: none;'
                    ), $this->gettext('answersecretquestion') . '<br /><br />&raquo;&nbsp;<i>' . $rcmail->config->get('pwtoolsquestion') . '?</i><br /><br />&raquo;&nbsp;' . html::tag('input', array(
                        'type' => 'text',
                        'id' => 'secretanswer',
                        'size' => 75,
                        'placeholder' => $this->gettext('answer')
                    )) . '&nbsp;' . html::tag('input', array(
                        'type' => 'button',
                        'id' => 'unlockbutton',
                        'value' => $this->gettext('unlockbutton')
                    )) . '&nbsp;<small id="lastattempt"></small>');
                } else {
                    $html .= '<br />';
                }
                $html .= '</fieldset>';
                $args['content'] = $html;
            } else {
                $sql = 'INSERT INTO ' . get_table_name('summary') . ' (user_id, ts, ip) VALUES (?, ?, ?)';
                $rcmail->db->query($sql, $rcmail->user->ID, $ts, $curIP);
            }
        }
        return $args;
    }
    function summary_html_quota($args)
    {
        $rcmail         = rcmail::get_instance();
        $quota          = $rcmail->imap->get_quota();
        $quota          = $rcmail->plugins->exec_hook('quota', $quota);
        $quota['used']  = round($quota['used'] / 1024, 2);
        $quota['total'] = round($quota['total'] / 1024);
        if (empty($quota['total'])) {
            $args['content'] = '<div id="summary_quota_container"></div>';
            return $args;
        }
        if ($quota['percent'] > $rcmail->config->get('alert_quota_pct', 80))
            $rcmail->output->show_message('summary.quotawarning', 'warning');
        if ($quota['percent'] > 99)
            $rcmail->output->show_message('summary.quotafull', 'error');
        $content = '&nbsp;<table class="quota" ><tr><td align="left" width="' . $quota['percent'] . '%" class="quotafull' . ($quota['percent'] > 80 ? ' quotawarning' : '') . '">&nbsp;' . $quota['percent'] . '%&nbsp;</td><td align="right" class="quotafree" width="' . $quota['free'] . '%">&nbsp;' . $quota['free'] . '%&nbsp;</td></tr></table>';
        $quota   = '<fieldset id="quota"><legend>&nbsp;' . $this->gettext('quota') . '&nbsp;(' . $quota['used'] . ' / ' . $quota['total'] . ' MBytes)</legend>';
        $quota .= $content;
        $quota .= '</fieldset>';
        $args['content'] = $quota;
        return $args;
    }
    function summary_html_motd($args)
    {
        $rcmail = rcmail::get_instance();
        if (file_exists("./plugins/summary/motd/" . $_SESSION['language'] . ".html"))
            $content = file_get_contents("./plugins/summary/motd/" . $_SESSION['language'] . ".html");
        else if (file_exists("./plugins/summary/motd/" . $rcmail->config->get('language', 'en_US') . ".html"))
            $content = file_get_contents("./plugins/summary/motd/" . $rcmail->config->get('language', 'en_US') . ".html");
        else if (file_exists("./plugins/summary/motd/en_US.html"))
            $content = file_get_contents("./plugins/summary/motd/en_US.html");
        else
            $content = file_get_contents("./plugins/summary/motd/en_US.html.dist");
        $motd = '<fieldset id="motd"><legend>' . $this->gettext('motd') . '</legend>';
        $motd .= $content;
        $motd .= '</fieldset>';
        $args['content'] = $motd;
        return $args;
    }
    function summary_html_timezone($args)
    {
        $args['content'] = '<div id="summary_timezone_container"></div>';
        return $args;
    }
    function _summary_html_timezone($soffset, $dst)
    {
        $rcmail   = rcmail::get_instance();
        $dst_html = '';
        if ($dst == 1) {
            $dst_html = '&nbsp;[' . $this->gettext('dst') . ']';
        }
        $tz = $rcmail->config->get('timezone');
        if (is_numeric($tz) || $tz == 'auto') {
            if ($rcmail->config->get('dst_active') && is_numeric($tz)) {
                $dst_html = '&nbsp;[' . $this->gettext('dst') . ']';
            }
            $tza = $this->get_timezones();
            if ($tz == 'auto') {
                $tzas = "&nbsp;" . $tza[$soffset];
            } else {
                $tzas = gmdate('H:i', abs($tz) * 3600);
                if ($tz >= 0)
                    $tzas = '+' . $tzas;
                else
                    $tzas = '-' . $tzas;
                $soffset = $tzas;
                $tzas    = "&nbsp;" . $tza[$soffset];
            }
        } else {
            $stz = date_default_timezone_get();
            date_default_timezone_set($tz);
            $soffset = date('Z', time()) / 3600;
            $tzas    = "&nbsp;" . str_replace('_', ' ', $tz);
            if (date('I', time())) {
                $dst_html = '&nbsp;[' . $this->gettext('dst') . ']';
            }
            date_default_timezone_set($stz);
        }
        if ($soffset >= 0)
            $soffset = '+' . $soffset;
        else
            $soffset = '-' . $soffset;
        $soffset = str_replace('++', '+', $soffset);
        $soffset = str_replace('--', '-', $soffset);
        if (isset($_GET['_framed'])) {
            $target = 'parent.';
        } else {
            $target = 'document.';
        }
        $html = '<fieldset class="timezone"><legend>&nbsp;' . $this->gettext('timezone') . '&nbsp;</legend>';
        $html .= '<div class="title">&nbsp;' . $this->gettext('timezone') . ':&nbsp;GMT&nbsp;' . $soffset . $tzas . $dst_html . '&nbsp;...&nbsp;[<a class= "summaryok summaryaction" href="#" onclick="' . $target . 'location.href=\'./?_task=settings&_section=general\'">' . $this->gettext('edit') . '</a>]';
        '</div>';
        $html .= '</fieldset>';
        return $html;
    }
    function summary_html_disable($args)
    {
        $rcmail = rcmail::get_instance();
        if (!in_array('nosummary', $rcmail->config->get('dont_override', array())) && !$rcmail->config->get('nosummary')) {
            $html = '<br />';
            $html .= '<form name="f" method="post" action="./?_action=plugin.summary_disable">';
            $html .= '<table width="100%"><tr><td align="right">';
            $html .= $this->gettext('disablesummary') . '&nbsp;' . '<input name="_summarydisable" value="1" onclick="document.forms.f.submit()" type="checkbox" />&nbsp;';
            $html .= '</td></tr></table>';
            $html .= '</form>';
            $args['content'] = $html;
        }
        return $args;
    }
    function summary_html($args)
    {
        $rcmail  = rcmail::get_instance();
        $framed  = isset($_GET['_framed']) ? '&_framed=1' : '';
        $mboxes  = array(
            'INBOX'
        );
        $special = array(
            'drafts_mbox',
            'sent_mbox',
            'junk_mbox',
            'trash_mbox',
            'archive_mbox',
            'notes_mbox'
        );
        foreach ($special as $folder) {
            if ($folder = $rcmail->config->get($folder)) {
                $mboxes[] = $folder;
            }
        }
        $counts = array();
        foreach ($mboxes as $mbox) {
            $count                   = $rcmail->imap->count($mbox, 'UNSEEN', TRUE);
            $counts[$mbox]['UNSEEN'] = $count;
            $count                   = $rcmail->imap->count($mbox, 'ALL', TRUE);
            $counts[$mbox]['ALL']    = $count;
        }
        $this->include_script('summary.js');
        $user = $_SESSION['username'];
        $rcmail->output->add_label('purgefolderconfirm');
        $html = '<fieldset id="mailbox" class="main"><legend>&nbsp;' . $this->gettext('mailbox') . " ::: " . $user . '&nbsp;</legend>';
        $html .= '<ul id="mailboxlist" class="listing">';
        $html .= '<table class="propform propform_settings" cellspacing="0" cellpadding="0" id="summarytable">';
        if (isset($_GET['_framed'])) {
            $html .= '<tr><td class="title"><td colspan="10" width="98%">&nbsp;</td></tr>';
        }
        foreach ($mboxes as $mbox) {
            if ($mbox == 'INBOX' || ($rcmail->config->get(strtolower($mbox) . '_mbox') && substr($this->gettext(strtolower($mbox)), 0, 1) != '[')) {
                $html .= '<tr>';
                $html .= '<td class="title">';
                $html .= '<li class="mailbox ' . strtolower($mbox) . '">';
                $html .= '<a id="summary' . strtolower($mbox) . '" class="summaryboxlink" onclick="gotofolder(\'' . $mbox . '\')" href="#">' . $this->gettext(strtolower($mbox)) . '&nbsp;&nbsp;&nbsp;&nbsp;</a></td><td class="narrow" align="left">(</td><td class="narrow" align="right"><b>' . $counts[$mbox]['UNSEEN'] . '</b></td><td class="narrow">&nbsp;/&nbsp;</td><td class="narrow" align="right">' . $counts[$mbox]['ALL'] . '</td><td class="narrow">)';
                $html .= '</li>';
                $html .= '</td><td class="narrow">&nbsp;&nbsp;&nbsp;&nbsp;[</td>';
                $html .= '<td class="narrow summaryaction summaryok" align="left">';
                $html .= '<a class= "summaryok" href="./?task=_mail&_action=plugin.summary_expunge&_mbox=' . urlencode($mbox) . $framed . '">' . rcube_label('compact') . '</a>';
                $html .= '</td><td class="narrow">]</td>';
                $html .= '<td class="narrow summaryaction summarywarning">&nbsp;</td>';
                if ($mbox == 'INBOX') {
                    $html .= '<td width="98%">&nbsp;</td>';
                } else {
                    $warning = "";
                    if ($counts[$mbox]['ALL'] > $rcmail->config->get(strtolower($mbox) . '_warning', true) && $rcmail->config->get(strtolower($mbox) . '_purge', true)) {
                        $warning = $this->gettext('cleanup');
                    }
                    if (strtolower($mbox) == strtolower($rcmail->config->get('drafts_mbox')) && $counts[$mbox]['ALL'] > 0) {
                        $warning = $this->gettext('unfinished');
                    }
                    $html .= '<td nowrap class="narrow summaryaction" align="left">&nbsp;&nbsp;&nbsp;&nbsp;<a class="summarywarning" href="javascript:emptyfolder(\'' . $mbox . '\')">' . $warning . '</a>&nbsp;</td>';
                }
                $html .= '</tr>';
            }
        }
        if (isset($_GET['_framed'])) {
            $html .= '<tr><td class="title"><td colspan="10" width="98%">&nbsp;</td></tr>';
        }
        $html .= '</table>';
        $html .= '</ul>';
        $html .= '</fieldset>';
        $args['content'] = $html;
        return $args;
    }
    function get_timezones()
    {
        $tza           = array();
        $tza['-11:00'] = 'Midway Island, Samoa';
        $tza['-10:00'] = 'Hawaii';
        $tza['-09:30'] = 'Marquesas Islands';
        $tza['-09:00'] = 'Alaska';
        $tza['-08:00'] = 'Pacific Time (US/Canada)';
        $tza['-07:00'] = 'Mountain Time (US/Canada)';
        $tza['-06:00'] = 'Central Time (US/Canada), Mexico City';
        $tza['-05:00'] = 'Eastern Time (US/Canada), Bogota, Lima';
        $tza['-04:30'] = 'Caracas';
        $tza['-04:00'] = 'Atlantic Time (Canada), La Paz';
        $tza['-03:30'] = 'Nfld Time (Canada), Nfld, S. Labador';
        $tza['-03:00'] = 'Brazil, Buenos Aires, Georgetown';
        $tza['-02:00'] = 'Mid-Atlantic';
        $tza['-01:00'] = 'Azores, Cape Verde Islands';
        $tza['+00:00'] = '(GMT) Western Europe, London, Lisbon, Casablanca';
        $tza['+01:00'] = 'Central European Time';
        $tza['+02:00'] = 'EET: Tallinn, Helsinki, Kaliningrad, South Africa';
        $tza['+03:00'] = 'Baghdad, Kuwait, Riyadh, Moscow, Nairobi';
        $tza['+03:30'] = 'Tehran';
        $tza['+04:00'] = 'Abu Dhabi, Muscat, Baku, Tbilisi';
        $tza['+04:30'] = 'Kabul';
        $tza['+05:00'] = 'Ekaterinburg, Islamabad, Karachi';
        $tza['+05:30'] = 'Chennai, Kolkata, Mumbai, New Delhi';
        $tza['+05:45'] = 'Kathmandu';
        $tza['+06:00'] = 'Almaty, Dhaka, Colombo';
        $tza['+06:30'] = 'Cocos Islands, Myanmar';
        $tza['+07:00'] = 'Bangkok, Hanoi, Jakarta';
        $tza['+08:00'] = 'Beijing, Perth, Singapore, Taipei';
        $tza['+08:45'] = 'Caiguna, Eucla, Border Village';
        $tza['+09:00'] = 'Tokyo, Seoul, Yakutsk';
        $tza['+09:30'] = 'Adelaide, Darwin';
        $tza['+10:00'] = 'EAST/AEST: Sydney, Guam, Vladivostok';
        $tza['+10:30'] = 'New South Wales';
        $tza['+11:00'] = 'Magadan, Solomon Islands';
        $tza['+11:30'] = 'Norfolk Island';
        $tza['+12:00'] = 'Auckland, Wellington, Kamchatka';
        $tza['+12:45'] = 'Chatham Islands';
        $tza['+13:00'] = 'Tonga, Pheonix Islands';
        $tza['+14:00'] = 'Kiribati';
        return $tza;
    }
    function geoipavailable($url)
    {
        if ($url) {
            $rcmail = rcmail::get_instance();
            $sql    = 'SELECT * FROM ' . get_table_name('system') . ' WHERE name=?';
            $result = $rcmail->db->query($sql, 'myrc_summary_ts');
            $result = $rcmail->db->fetch_assoc($result);
            if (is_array($result)) {
                $ts = (int) $result['value'];
                if (time() - $ts >= 3600) {
                    $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                    $rcmail->db->query($sql, time(), 'myrc_summary_ts');
                    $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                    $rcmail->db->query($sql, 1, 'myrc_summary_requests');
                } else {
                    $sql      = 'SELECT * FROM ' . get_table_name('system') . ' WHERE name=?';
                    $result   = $rcmail->db->query($sql, 'myrc_summary_requests');
                    $result   = $rcmail->db->fetch_assoc($result);
                    $requests = (int) $result['value'];
                    if ($requests >= 9999) {
                        $sql    = 'SELECT * FROM ' . get_table_name('system') . ' WHERE name=?';
                        $result = $rcmail->db->query($sql, 'myrc_summary_service');
                        $result = $rcmail->db->fetch_assoc($result);
                        if (is_array($result)) {
                            return ($result['value'] == 1) ? true : false;
                        } else {
                            return false;
                        }
                    }
                    $requests++;
                    $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                    $rcmail->db->query($sql, $requests, 'myrc_summary_requests');
                }
                $httpConfig['method']     = 'GET';
                $httpConfig['target']     = $url;
                $httpConfig['timeout']    = '2';
                $httpConfig['user_agent'] = 'MyRoundcube PHP/5.0';
                $http                     = new MyRCHttp();
                $http->initialize($httpConfig);
                if (ini_get('safe_mode') || ini_get('open_basedir')) {
                    $http->useCurl(false);
                }
                $http->execute();
                if ($http->error) {
                    $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                    $rcmail->db->query($sql, 0, 'myrc_summary_service');
                    return false;
                } else {
                    $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                    $rcmail->db->query($sql, 1, 'myrc_summary_service');
                    return true;
                }
            } else {
                $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
                $rcmail->db->query($sql, 0, 'myrc_summary_service');
                return false;
            }
        } else {
            $sql = 'UPDATE ' . get_table_name('system') . ' SET value=? WHERE name=?';
            $rcmail->db->query($sql, 0, 'myrc_summary_service');
            return false;
        }
    }
    function inet_aton($ip)
    {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            return 0;
        return sprintf("%u", ip2long($ip));
    }
    function inet_ntoa($num)
    {
        $num = trim($num);
        if ($num == "0")
            return "0.0.0.0";
        return long2ip(-(4294967295 - ($num - 1)));
    }
    function getVisitorIP()
    {
        return rcube_utils::remote_addr();
    }
    function get_demo($string)
    {
        $temparr = explode("@", $string);
        return preg_replace('/[0-9 ]/i', '', $temparr[0]) . "@" . $temparr[count($temparr) - 1];
    }
    function sendmail($to, $ip, $cc = false)
    {
        $rcmail = rcmail::get_instance();
        if ($msg = file_get_contents(INSTALL_PATH . 'plugins/summary/suspicious.php')) {
            preg_match_all('/<label>(.*?)<\/label>/', $msg, $matches);
            if (is_array($matches[0]) && is_array($matches[1])) {
                $repl = array();
                foreach ($matches[1] as $idx => $label) {
                    $repl[$idx] = $this->gettext($label);
                }
                $msg    = str_replace($matches[0], $repl, $msg);
                $repl   = array(
                    '##service##',
                    '##account##',
                    '##IP##',
                    '##country##',
                    '##region##',
                    '##city##',
                    '##maps##',
                    '##contact##',
                    '##sender##'
                );
                $from   = $rcmail->config->get('summary_contact', $rcmail->config->get('admin_email'));
                $replby = array(
                    $rcmail->config->get('product_name', 'Webm@il Team'),
                    $rcmail->user->data['username'],
                    $_SESSION['geoip'][$ip]['host'],
                    $_SESSION['geoip'][$ip]['countryName'] . ' - ' . $_SESSION['geoip'][$ip]['countryCode'],
                    $_SESSION['geoip'][$ip]['region'],
                    $_SESSION['geoip'][$ip]['city'],
                    html::tag('a', array(
                        'href' => (rcube_utils::https_check() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/plugins/summary/maps.php' . '?lang=' . str_replace('_', '-', $_SESSION['language']) . '&region=' . $_SESSION['geoip'][$ip]['countryCode'] . '&lcity=' . $this->gettext('city') . '&city=' . $_SESSION['geoip'][$ip]['city'] . '&lcountry=' . $this->gettext('country') . '&country=' . $_SESSION['geoip'][$ip]['countryName'] . '&lip=' . $this->gettext('ipaddress') . '&ip=' . $_SESSION['geoip'][$ip]['ipv4'] . '&lat=' . $_SESSION['geoip'][$ip]['latitude'] . '&long=' . $_SESSION['geoip'][$ip]['longitude'],
                        'target' => '_blank'
                    ), $this->gettext('maps')),
                    html::tag('a', array(
                        'href' => 'mailto:' . $from . '?subject=[Suspicious login attempt alert] - ' . $rcmail->user->data['username'] . '&body=' . $ip
                    ), $from),
                    $rcmail->config->get('product_name', 'Webm@il Team')
                );
                $msg    = str_replace($repl, $replby, $msg);
            }
            preg_match('/<subject>(.*?)<\/subject>/', $msg, $subject);
            preg_match('/<body>(.*?)<\/body>/s', $msg, $body);
            $rc = false;
            if (isset($subject[1]) && isset($body[1])) {
                $body = $body[1];
                if ($cc) {
                    $body .= '<br />--<br />CC: ' . $cc;
                }
                $body        = str_replace('&amp;', '&', $body);
                $LINE_LENGTH = $rcmail->config->get('line_length', 72);
                $h2t         = new html2text($body, false, true, 0);
                $txt         = rc_wordwrap($h2t->get_text(), $LINE_LENGTH, "\r\n");
                $msg         = array(
                    'subject' => '=?UTF-8?B?' . base64_encode($subject[1]) . '?=',
                    'htmlbody' => $body,
                    'txtbody' => $txt
                );
                $ctb         = md5(rand() . microtime());
                $headers     = "Return-Path: $from\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: multipart/alternative; boundary=\"=_$ctb\"\r\n";
                $headers .= "Date: " . date('r', time()) . "\r\n";
                $headers .= "From: $from\r\n";
                $headers .= "To: $to\r\n";
                if ($cc) {
                    $headers .= "CC: $css\r\n";
                }
                $headers .= "Subject: " . $msg['subject'] . "\r\n";
                $headers .= "Reply-To: $from\r\n";
                $msg_body .= "Content-Type: multipart/alternative; boundary=\"=_$ctb\"\r\n\r\n";
                $txt_body = "--=_$ctb";
                $txt_body .= "\r\n";
                $txt_body .= "Content-Transfer-Encoding: 7bit\r\n";
                $txt_body .= "Content-Type: text/plain; charset=" . RCMAIL_CHARSET . "\r\n";
                $txt = rc_wordwrap($msg['txtbody'], $LINE_LENGTH, "\r\n");
                $txt = wordwrap($txt, 998, "\r\n", true);
                $txt_body .= "$txt\r\n";
                $txt_body .= "--=_$ctb";
                $txt_body .= "\r\n";
                $msg_body .= $txt_body;
                $msg_body .= "Content-Transfer-Encoding: quoted-printable\r\n";
                $msg_body .= "Content-Type: text/html; charset=" . RCMAIL_CHARSET . "\r\n\r\n";
                $msg_body .= str_replace("=", "=3D", $msg['htmlbody']);
                $msg_body .= "\r\n\r\n";
                $msg_body .= "--=_$ctb--";
                $msg_body .= "\r\n\r\n";
                if (!is_object($rcmail->smtp)) {
                    $rcmail->smtp_init(true);
                }
                if ($rcmail->config->get('smtp_pass') == "%p") {
                    $rcmail->config->set('smtp_server', $rcmail->config->get('default_smtp_server'));
                    $rcmail->config->set('smtp_user', $rcmail->config->get('default_smtp_user'));
                    $rcmail->config->set('smtp_pass', $rcmail->config->get('default_smtp_pass'));
                }
                $rcmail->smtp->connect();
                $rc = $rcmail->smtp->send_mail($from, $to, $headers, $msg_body);
            }
        }
        return $rc;
    }
}
<?php
/**
 * This file is part of CONTEJO - CONTENT MANAGEMENT 
 * It is an open source content management system and had 
 * been forked from Redaxo 3.2 (www.redaxo.org) in 2006.
 * 
 * PHP Version: 5.3.1+
 *
 * @package     Addons
 * @subpackage  community
 * @version     2.6.0
 *
 * @author      Stefan Lehmann <sl@raumsicht.com>
 * @copyright   Copyright (c) 2008-2012 CONTEJO. All rights reserved. 
 * @link        http://contejo.com
 *
 * @license     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *  CONTEJO is free software. This version may have been modified pursuant to the
 *  GNU General Public License, and as distributed it includes or is derivative
 *  of works licensed under the GNU General Public License or other free or open
 *  source software licenses. See _copyright.txt for copyright notices and
 *  details.
 * @filesource
 */

if (!$CJO['CONTEJO']) return false;

require_once $CJO['ADDON_PATH'].'/community/bounce/class.phpmailer-bmh.php';
require_once $CJO['ADDON_PATH'].'/community/bounce/phpmailer-bmh_rules.php';

class cjoCommunityBounce extends BounceMailHandler   {

    static  $mypage        = 'community';
    static  $soft_turns    = 3;
    private $bmh;
    private $succes_msg    = '';
    private $log           = array();
    
    public static function bounce($start=false,$mail_account=false,$move=true) {
        
        global $CJO, $I18N_10;

        if ($start != true && !self::hasSession()) return false;

        if (!isset($CJO['ADDON']['settings'][self::$mypage]['BOUNCE'])) {
            $this->removeSession();
            return false;
        }
        
        if (!function_exists('imap_open')) {
            cjoMessage::addError($I18N_10->msg('msg_no_imap_open'));
            self::removeSession();
            return false; 
        }
        
        $mail = new cjoPHPMailer();
        $mail->setAccount($CJO['ADDON']['settings'][self::$mypage]['BOUNCE_MAIL_ACCOUNT']);

        if (empty($mail->Username) || empty($mail->Password)) {
            cjoMessage::addError($I18N_10->msg('msg_no_vaild_mail_account'));
            self::removeSession();
            return false;
        }

        if ($start) self::startSession();

        $bmh = new cjoCommunityBounce();
        $bmh->action_function    = 'cjoCommunityBounce::updateUser'; // default is 'callbackAction'
        $bmh->verbose            = VERBOSE_QUIET; //VERBOSE_SIMPLE; //VERBOSE_REPORT; //VERBOSE_DEBUG; //VERBOSE_QUIET; // default is VERBOSE_SIMPLE
        $bmh->mailhost           = $mail->Host;
        $bmh->mailbox_username   = $mail->Username;
        $bmh->mailbox_password   = $mail->Password;
        $bmh->moveSoft           = $move;
        $bmh->moveHard           = $move;
        $bmh->max_messages       = 500;

        if (!$bmh->openMailbox()) {
            cjoMessage::addError($bmh->error_msg);
            self::removeSession();
            return false;
        }
               
        if (!$bmh->processMailbox()) {
            self::printRestartScript();
        } 
        else {
            self::removeSession();
            self::printRestartScript(true);
        }
    }
    
    public static function updateUser($msgnum, $bounce_type, $email, $subject, $xheader, $remove, $rule_no=false, $rule_cat=false, $totalFetched=0) {

        $sql = new cjoSql();
        $sql->setTable(TBL_COMMUNITY_USER);
        $sql->setWhere(array('email'=>$email));
        $sql->Select('id, status, bounce, (SELECT GROUP_CONCAT(ug.group_id) FROM '.TBL_COMMUNITY_UG.' ug WHERE ug.user_id=id GROUP BY ug.user_id) AS groups');

        if ($sql->getRows() > 0) {
                       
            $bounce = ($remove) ? self::$soft_turns : (int) $sql->getValue('bounce') + 1;

            $user_id = $sql->getValue('id');            
            $group_ids = $sql->getValue('groups');           
 
            $update = $sql;
            $update->flush();
            $update->setTable(TBL_COMMUNITY_USER);
            $update->setWhere(array('id'=>$user_id));
            $update->setValue('bounce',$bounce);
            if ($bounce>=self::$soft_turns) {
                $update->setValue('status',0);
            }      
            if (!$update->Update()) return false;
 
            foreach (cjoAssistance::toArray($group_ids,',') as $group_id) {
                $sql->flush();
                $sql->setDirectQuery("INSERT INTO cjo_10_community_bounce VALUES (".$group_id.", '".$rule_cat."', 1) ON DUPLICATE KEY UPDATE count = count+1");
            }
        }
        //self::isTimeOut();
        return true;
    }
    
    public static function updateUserTable() {
        
        global $CJO, $I18N_10;
        
        $sql = new cjoSql();

        if ($sql->setDirectQuery("ALTER TABLE `".TBL_COMMUNITY_USER."` ADD `bounce` TINYINT( 1 ) NOT NULL AFTER `activation_key`")) {

            $settings_file = $CJO['ADDON']['settings'][self::$mypage]['SETTINGS'];
            $content = file_get_contents($settings_file);
            $content = str_replace("// --- /DYN","$"."CJO['ADDON']['settings'][$"."mypage]['BOUNCE'] = \"1\";\r\n// --- /DYN", $content);
            $content = str_replace("// --- /DYN","$"."CJO['ADDON']['settings'][$"."mypage]['BOUNCE_MAIL_ACCOUNT'] = \"0\";\r\n\r\n// --- /DYN", $content);
            cjoGenerate::putFileContents($settings_file, $content);
            $CJO['ADDON']['settings'][self::$mypage] = "1";
        }
        else {
            cjoMessage::addWarning($I18N_10->msg('msg_bounce_install_incomplete'));
        }
    }    
    
    public function processMailbox($max=false) {
        parent::processMailbox($max);
        if (!empty($this->succes_msg)) {
            cjoMessage::addSuccess($this->succes_msg);
        }
    }

    public function output($msg=false,$verbose_level=VERBOSE_SIMPLE) {
        
        if ($this->verbose >= $verbose_level) {
            if (empty($msg)) {
                cjoMessage::addError($this->error_msg);
            } else {
                $this->succes_msg .= $msg . $this->bmh_newline;
            }
        }
    }
    
    private static function isTimeOut() {

        $max_ext_time = ini_get('max_execution_time');

        if (empty($max_ext_time))
        $max_ext_time = (int) get_cfg_var('max_execution_time');

        $time_left = $max_ext_time - cjoTime::showScriptTime();

        if ($time_left < 8) {
            self::printRestartScript();
            return true;
        }
        return false;
    }
    
    private static function generateLogFile() {
        
        global $CJO;
        
        $sql = new cjoSql();
        $sql->setTable(TBL_COMMUNITY_BOUNCE);
        $sql->Select('*');
        $results = $sql->getArray();
        
        $content = 'group_id;bounce_type;count';
        
        foreach($results as $result) {
            $content .= "\r\n".implode(';',$result);
        }
        
        $date = strftime('%Y-%m-%d_%h-%m-%s', time());
        $filename = $CJO['ADDON_CONFIG_PATH'].'/'.self::$mypage.'/bounce_report_'.$date.'.csv';
        
        return cjoGenerate::putFileContents($filename, $content);
        
    }

    private static function printRestartScript($finished=false) {
        
        $params = array();
        $params['page']     = 'community'; 
        $params['subpage']  = 'user'; 
        
        if ($finished) $params['finished'] =  $finished;
        $url = cjoAssistance::createBEUrl($params);
        
        echo '<script type="text/javascript">/* <![CDATA[ */ $(function(){ cm_automateScript(\''.$url.'\'); }); /* ]]> */</script>';
    }
    
    private function addLogEntry() {
        
    }
    
    private static function hasSession() {
        $fieldnames = cjoSql::getFieldNames(TBL_COMMUNITY_BOUNCE);
        if (empty($fieldnames)) {
            cjoMessage::removeLastError();
            return false;
        }
        return true;
    }
    
    private static function startSession() {
        if (self::hasSession()) return true;
        $sql = new cjoSql();
        $sql->setQuery("CREATE TABLE ".TBL_COMMUNITY_BOUNCE." (`group_id` INT( 11 ) NOT NULL , `rule_cat` VARCHAR( 50 ) NOT NULL ,`count` INT( 11 ) NOT NULL, UNIQUE ( `group_id` )) ENGINE = MYISAM;");
    }
    
    private static function removeSession() {
        if (!self::hasSession()) return true;
        
        if (self::generateLogFile()) {
            $sql = new cjoSql();
            $sql->setQuery("DROP TABLE ".TBL_COMMUNITY_BOUNCE);
        }
    }
}
<?php
/**
 * This file is part of CONTEJO - CONTENT MANAGEMENT 
 * It is an open source content management system and had 
 * been forked from Redaxo 3.2 (www.redaxo.org) in 2006.
 * 
 * PHP Version: 5.3.1+
 *
 * @package     Addons
 * @subpackage  wymeditor
 * @version     2.7.x
 *
 * @author      Stefan Lehmann <sl@contejo.com>
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

$mypage = 'wymeditor';

$CJO['ADDON']['addon_id'][$mypage] = '5';
$CJO['ADDON']['page'][$mypage] 	   = $mypage;
$CJO['ADDON']['name'][$mypage] 	   = 'WYMeditor';
$CJO['ADDON']['lang'][$mypage] 	   = $I18N->msg('htmllang');
$CJO['ADDON']['perm'][$mypage] 	   = 'wym_editor[]'; // permission
$CJO['ADDON']['author'][$mypage] 	= 'Stefan Lehmann 2010';
$CJO['ADDON']['version'][$mypage] 	= '0.2';
$CJO['ADDON']['compat'][$mypage] 	= '2.2';
$CJO['ADDON']['support'][$mypage] 	= 'http://contejo.com/addons/wymeditor';

if ($CJO['ADDON']['status'][$mypage] != 1 || !$CJO['CONTEJO']) return;

require_once $CJO['ADDON_PATH'].'/'.$mypage.'/classes/class.wymeditor.inc.php';
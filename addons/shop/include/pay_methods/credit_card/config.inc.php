<?php
/**
 * This file is part of CONTEJO - CONTENT MANAGEMENT 
 * It is an open source content management system and had 
 * been forked from Redaxo 3.2 (www.redaxo.org) in 2006.
 * 
 * PHP Version: 5.3.1+
 *
 * @package     Addons
 * @subpackage  shop
 * @version     2.7.x
 *
 * @author      Matthias Schomacker <ms@raumsicht.com>
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

global $I18N_21, $CJO;

$mypage = 'shop';
$pay_method = 'credit_card';
$lang_id = $CJO['CONTEJO'] ? $CJO['LANG'] : $CJO['CLANG_ISO'][$CJO['CUR_CLANG']];

$I18N_21->extendFromFile($CJO['ADDON']['settings'][$mypage]['PAY_METHODS_PATH'].'/'.$pay_method, $lang_id);
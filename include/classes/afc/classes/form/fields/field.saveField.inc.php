<?php
/**
 * This file is part of CONTEJO - CONTENT MANAGEMENT 
 * It is an open source content management system and had 
 * been forked from Redaxo 3.2 (www.redaxo.org) in 2006.
 * 
 * PHP Version: 5.3.1+
 *
 * @package     contejo
 * @subpackage  core
 * @version     2.6.0
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

class saveField extends buttonField {

	public $saveButtonName;

	public function saveField($attributes = array ()) {
		global $I18N;

		$this->saveButtonName = 'cjoform_save_button';
		$this->addButton($this->saveButtonName, $I18N->msg('button_save'), true, 'img/silk_icons/disk.png');

		$this->buttonField($attributes);
	}

	public function setSaveButtonStatus($status) {
		$this->setButtonStatus($this->saveButtonName, $status);
	}
}
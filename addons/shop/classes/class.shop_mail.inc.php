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
 * @version     2.6.0
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

/**
 * <strong><u>Class cjoShopMail</u></strong>
 *
 * Provides a static method for sending different
 * types of emails to a customer
 */

class cjoShopMail {
        
    protected static $mypage = 'shop';

	/**
	 * Sends an email via php-mailer, its content is depending
	 * depending on the mail subject.
	 *
	 * @param string $subject 								- the mail subject
	 * @param int $id		 								- the order id
	 * @param array $products_available (default = array()) - if the amount of a product
	 * 									  					  in stock is smaller than the
	 * 									  					  requested, this information is
	 * 									   					  hold here
	 * @return function cjoPHPMailer->Send(true)
	 * @access public
	 * @see /addons/phpmailer/classes/class.phpmailer.inc.php
	 */
	public static function sendMail($subject , $id, $products_available = array()) {

		global $CJO, $I18N_21, $I18N;

		// get settings values
		$settings  			= $CJO['ADDON']['settings'][self::$mypage];
		$separator 			= $settings['CURRENCY']['DEFAULT_SEPARATOR'];
		$currency  			= $settings['CURRENCY']['DEFAULT_SIGN'];
		$pay_methods_path 	= $settings['PAY_METHODS_PATH'];
		$all_pay_costs 		= cjoShopPayMethod::getAllCosts();

		$clang = $CJO['CUR_CLANG'];

		// get content templates
		include_once $CJO['ADDON_CONFIG_PATH']."/".self::$mypage."/".$clang.".clang.inc.php";

		// get mail type to send
		switch ($subject) {
			// get data from $_POST
			case 'ORDER_CONFIRM_SUBJECT' 	:   $template = $settings['ORDER_CONFIRM_MAIL'];
												break;
			// get data from db
			case 'ORDER_SEND_SUBJECT' 		: 	$template = $settings['ORDER_SEND_MAIL'];
							    				break;
		}

		// get data
	    $sql = new cjoSql();
		$qry = "SELECT
					*
				FROM "
					.TBL_21_ORDERS."
				WHERE
					id = ".$id." LIMIT 1";

		$result = array_shift($sql->getArray($qry));

		$sql->flush();
		$customer 		 = $result['title'].' '.$result['firstname'].' '.
						   $result['name'];
		$address1 	   	 = new cjoShopAddress($result['address1']);
		$address1_full   = $customer."\r\n".$address1->out();
		$address2 	  	 = new cjoShopSupplyAddress($result['address2']);
		$address2 		 = $address2->out();
		$product_list 	 = cjoShopProduct::productsOut($result['products'], $products_available);
		$mail_address 	 = $result['email'];
		$pay_method	  	 = $result['pay_method'];
		$pay_object	  	 = cjoShopPayMethod::getPayObject($pay_method, $result['pay_data']);
		$payment_costs	 = cjoShopPrice::toCurrency($all_pay_costs[$pay_method]);
	    $delivery_costs  = $result['delivery_cost'];
	    $delivery_method = $result['delivery_method'];
	    $order_value  	 = $result['total_price'];
	    $order_date		 = strftime($I18N->msg('dateformat_sort'),$result['createdate']);
	    $order_comment   = $result['comment'];
		$order_value     = cjoShopPrice::convToFloat($order_value);
		$delivery_costs  = cjoShopPrice::convToFloat($delivery_costs);
		$total_sum       = $delivery_costs + $order_value + $pay_object->getCosts();

		// replace wildcards by values
		$replacements   = array( '%customer%' 		  => $customer,
								 '%address%'		  => $address1_full,
								 '%supply_address%'   => $address2,
								 '%product_list%' 	  => $product_list,
								 '%order_value%' 	  => cjoShopPrice::toCurrency($order_value),
								 '%pay_method%' 	  => $I18N_21->msg('shop_'.$pay_method),
								 '%pay_data%' 		  => $pay_object->out(),
								 '%payment_costs%'	  => $payment_costs,
								 '%delivery_costs%'   => cjoShopPrice::toCurrency($delivery_costs),
								 '%delivery_method%'  => $delivery_method,
								 '%today%' 			  => strftime($I18N->msg('dateformat_sort')),
								 '%total_sum%' 		  => cjoShopPrice::toCurrency($total_sum),
								 '%order_id%' 		  => $id,
								 '%order_date%'       => $order_date,
		                         '%order_comment%'    => $order_comment,
								 '%shop_name%'		  => $CJO['SERVER']);

		// build mail text
		$mail_body = str_replace(array_keys($replacements), $replacements, $template);

		// prepare mail and send it
		$phpmailer = new cjoPHPMailer();
		$phpmailer->setAccount($settings['PHP_MAILER_ACCOUNT']);
		$phpmailer->Subject = $settings[$subject];
		$phpmailer->AddAddress($mail_address);
		$phpmailer->IsHTML(false);
		$phpmailer->Body = $mail_body;
		return $phpmailer->Send(true);

	} // end function sendMail

/*
* all yet possible combinations for 'shop_'.$pay_method (see line 116)
* this lets i18n.php php find all texts that need to be
* translated
*
* $I18N_21->msg('shop_bank_account');
* $I18N_21->msg('shop_credit_card');
* $I18N_21->msg('shop_invoice');
* $I18N_21->msg('shop_pre_payment');
*/

} // end class cjoShopMail
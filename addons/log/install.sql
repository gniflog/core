DROP TABLE IF EXISTS `%TBL_27_LOG%`;
CREATE TABLE `%TBL_27_LOG%` (
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`extension` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
`params` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
`url` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
`user_id` INT( 11 ) NOT NULL ,
`date` INT( 11 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM ;
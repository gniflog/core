CREATE TABLE IF NOT EXISTS `%TBL_28_ARCHIVE_ARTICLES%` LIKE `%TBL_ARTICLES%`;
CREATE TABLE IF NOT EXISTS `%TBL_28_ARCHIVE_ARTICLES_SLICE%` LIKE `%TBL_ARTICLES_SLICE%`;

CREATE TABLE IF NOT EXISTS `%TBL_28_ARCHIVE_PATHS%` (
`id` INT( 11 ) NOT NULL,
`path` VARCHAR( 255 ) NOT NULL,
`clang` INT( 11 ) NOT NULL,
`createdate` int(11)  NULL 
) ENGINE = MYISAM ;

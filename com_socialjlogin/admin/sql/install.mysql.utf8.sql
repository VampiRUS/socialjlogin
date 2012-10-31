CREATE TABLE IF NOT EXISTS `#__socialjlogin` (
`userid` INT( 11 ) NOT NULL ,
`photo` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`socid` varchar( 50 ) NOT NULL  DEFAULT '',
`type` varchar( 50 ) NOT NULL  DEFAULT '',
`email_hash` VARCHAR( 32 ) NOT NULL  DEFAULT '',
`first_name` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`middle_name` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`last_name` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`nickname` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`sex` INT( 2 ) NOT NULL ,
`bdate` VARCHAR( 10 ) NOT NULL  DEFAULT '',
`city` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`country` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`link` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`timezone` VARCHAR( 4 ) NOT NULL DEFAULT '',
`photo_medium` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`photo_big` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`photo_rec` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`photo_medium_rec` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`home_phone` VARCHAR( 15 ) NOT NULL  DEFAULT '',
`mobile_phone` VARCHAR( 15 ) NOT NULL  DEFAULT '',
`university_name` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`faculty_name` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`graduation` VARCHAR( 4 ) NOT NULL  DEFAULT '',
`screen_name` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`status` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`relation` INT( 3 ) NOT NULL  DEFAULT 0,
`extfield1` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield2` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield3` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield4` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield5` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield6` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield7` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield8` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield9` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield10` VARCHAR( 255 ) NOT NULL  DEFAULT '',
`extfield11` TEXT,
`extfield12` TEXT,
`extfield13` TEXT,
`extfield14` TEXT,
`extfield15` TEXT,
UNIQUE (
`type`,`socid`
),
INDEX (
`userid`
),
INDEX (
`socid`
),
INDEX (
`email_hash`
)
) ENGINE = MYISAM ;


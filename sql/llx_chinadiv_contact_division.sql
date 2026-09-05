-- modChinaDiv: structured division codes linked to a contact (V0.4).
-- Mirror of llx_chinadiv_soc_division for socpeople.

CREATE TABLE llx_chinadiv_contact_division(
	rowid			integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_socpeople	integer NOT NULL,
	province_code	varchar(6),
	city_code		varchar(6),
	district_code	varchar(6),
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

ALTER TABLE llx_chinadiv_contact_division ADD UNIQUE INDEX uk_chinadiv_contact (fk_socpeople);

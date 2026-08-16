-- modChinaDiv: structured division codes linked to a thirdparty.
-- Machine-readable counterpart of the cascade selector (state_id/town stay text).

CREATE TABLE llx_chinadiv_soc_division(
	rowid			integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_soc			integer NOT NULL,
	province_code	varchar(6),
	city_code		varchar(6),
	district_code	varchar(6),
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

ALTER TABLE llx_chinadiv_soc_division ADD UNIQUE INDEX uk_chinadiv_soc (fk_soc);

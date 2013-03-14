#
# Table structure for table 'tx_contexts_contexts'
#
CREATE TABLE tx_contexts_contexts (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	type tinytext,
	title tinytext,
	alias tinytext,
	type_conf mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_contexts_settings'
#
CREATE TABLE tx_contexts_settings (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	context_uid int(11) DEFAULT '0' NOT NULL,
	foreign_table tinytext,
	foreign_uid int(11) DEFAULT '0' NOT NULL,
	foreign_field tinytext,
	enabled tinyint(4) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=InnoDB;

CREATE TABLE pages (
       tx_contexts_enable tinytext NOT NULL,
       tx_contexts_disable tinytext NOT NULL
       tx_contexts_nav_enable tinytext NOT NULL,
       tx_contexts_nav_disable tinytext NOT NULL
);

CREATE TABLE tt_content (
       tx_contexts_enable tinytext NOT NULL,
       tx_contexts_disable tinytext NOT NULL
);

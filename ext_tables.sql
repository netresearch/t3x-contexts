#
# Table structure for table 'tx_contexts_contexts'
#
CREATE TABLE tx_contexts_contexts
(
    uid             int(11)                NOT NULL auto_increment,
    pid             int(11)    DEFAULT '0' NOT NULL,
    tstamp          int(11)    DEFAULT '0' NOT NULL,
    crdate          int(11)    DEFAULT '0' NOT NULL,
    cruser_id       int(11)    DEFAULT '0' NOT NULL,
    deleted         tinyint(4) DEFAULT '0' NOT NULL,
    type            tinytext,
    title           tinytext,
    alias           tinytext,
    type_conf       mediumtext,
    invert          tinyint(4) DEFAULT '0' NOT NULL,
    use_session     tinyint(4) DEFAULT '0' NOT NULL,
    disabled        tinyint(4) DEFAULT '0' NOT NULL,
    hide_in_backend tinyint(4) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
) ENGINE = InnoDB;

#
# Table structure for table 'tx_contexts_settings'
#
CREATE TABLE tx_contexts_settings
(
    uid           int(11)                NOT NULL auto_increment,
    pid           int(11)    DEFAULT '0' NOT NULL,
    tstamp        int(11)    DEFAULT '0' NOT NULL,
    crdate        int(11)    DEFAULT '0' NOT NULL,
    cruser_id     int(11)    DEFAULT '0' NOT NULL,
    context_uid   int(11)    DEFAULT '0' NOT NULL,
    foreign_table tinytext,
    foreign_uid   int(11)    DEFAULT '0' NOT NULL,
    name          tinytext,
    enabled       tinyint(4) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
) ENGINE = InnoDB;

#
# Update structure of table 'pages'
#
CREATE TABLE pages
(
    tx_contexts_enable      text,
    tx_contexts_disable     text,
    tx_contexts_nav_enable  text,
    tx_contexts_nav_disable text
);

#
# Update structure of table 'tt_content'
#
CREATE TABLE tt_content
(
    tx_contexts_enable  text,
    tx_contexts_disable text
);

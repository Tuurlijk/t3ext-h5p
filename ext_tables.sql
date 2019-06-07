-- Add h5p display settings to tt_content
CREATE TABLE tt_content (
    tx_h5p_content         int(11) unsigned             DEFAULT '0',
    tx_h5p_display_options tinyint(3) unsigned NOT NULL DEFAULT '0'
);

-- H5P Cached assets
CREATE TABLE tx_h5p_domain_model_cachedasset (
    uid       int(11) unsigned    NOT NULL auto_increment,
    pid       int(11)             NOT NULL DEFAULT '0',

    tstamp    int(11) unsigned    NOT NULL DEFAULT '0',
    crdate    int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id int(11) unsigned    NOT NULL DEFAULT '0',
    deleted   tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden    tinyint(4) unsigned NOT NULL DEFAULT '0',
    sorting   int(10)             NOT NULL DEFAULT '0',

    resource  VARCHAR(40)                  DEFAULT NULL,
    hash_key  VARCHAR(255)        NOT NULL DEFAULT '',
    type      VARCHAR(255)        NOT NULL DEFAULT '',
    PRIMARY KEY (uid),
    KEY hashkey (hash_key)
);

-- H5P Content elements
CREATE TABLE tx_h5p_domain_model_content (
    uid             int(11)             NOT NULL auto_increment,
    pid             int(11)             NOT NULL DEFAULT '0',

    package         int(11) unsigned             DEFAULT '0',

    tstamp          int(11) unsigned    NOT NULL DEFAULT '0',
    crdate          int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id       int(11) unsigned    NOT NULL DEFAULT '0',
    deleted         tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden          tinyint(4) unsigned NOT NULL DEFAULT '0',
    starttime       int(11) unsigned    NOT NULL DEFAULT '0',
    endtime         int(11) unsigned    NOT NULL DEFAULT '0',
    sorting         int(10)             NOT NULL DEFAULT '0',

    created_at      timestamp           NOT NULL DEFAULT '0000-00-00 00:00:00',
    updated_at      timestamp           NOT NULL DEFAULT '0000-00-00 00:00:00',
    user_id         int(10) unsigned    NOT NULL default '0',
    title           VARCHAR(255)        NOT NULL default '',
    library         int(10) unsigned    NOT NULL default '0',
    parameters      longtext            NOT NULL,
    filtered        longtext            NOT NULL,
    slug            VARCHAR(127)        NOT NULL default '',
    embed_type      VARCHAR(127)        NOT NULL default '',
    disable         int(10) unsigned    NOT NULL DEFAULT '0',
    content_type    VARCHAR(127)        NOT NULL default '',
    author          VARCHAR(127)        NOT NULL default '',
    license         VARCHAR(7)          NOT NULL default '',
    authors         text,
    source          text,
    year_from       int(5) unsigned     NOT NULL DEFAULT '0',
    year_to         int(5) unsigned     NOT NULL DEFAULT '0',
    license_version VARCHAR(255)        NOT NULL default '',
    license_extras  text,
    author_comments text,
    changes         text,
    keywords        text,
    description     text,
    PRIMARY KEY (uid)
);

CREATE TABLE tx_h5p_domain_model_contentdependency (
    uid             int(11)             NOT NULL auto_increment,
    pid             int(11)             NOT NULL DEFAULT '0',

    package         int(11) unsigned             DEFAULT '0',

    tstamp          int(11) unsigned    NOT NULL DEFAULT '0',
    crdate          int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id       int(11) unsigned    NOT NULL DEFAULT '0',
    deleted         tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden          tinyint(4) unsigned NOT NULL DEFAULT '0',
    starttime       int(11) unsigned    NOT NULL DEFAULT '0',
    endtime         int(11) unsigned    NOT NULL DEFAULT '0',
    sorting         int(10)             NOT NULL DEFAULT '0',

    content         VARCHAR(40)         NOT NULL DEFAULT '',
    library         VARCHAR(40)         NOT NULL DEFAULT '',
    dependency_type VARCHAR(255)        NOT NULL DEFAULT '',
    weight          int(11)             NOT NULL DEFAULT '0',
    drop_css        tinyint(1)          NOT NULL DEFAULT '0',
    PRIMARY KEY (uid)
);

-- H5P Config setting
CREATE TABLE tx_h5p_domain_model_configsetting (
    uid          int(11)             NOT NULL auto_increment,
    pid          int(11)             NOT NULL DEFAULT '0',

    tstamp       int(11) unsigned    NOT NULL DEFAULT '0',
    crdate       int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id    int(11) unsigned    NOT NULL DEFAULT '0',
    deleted      tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden       tinyint(4) unsigned NOT NULL DEFAULT '0',
    sorting      int(10)             NOT NULL DEFAULT '0',
    config_key   VARCHAR(255)        NOT NULL DEFAULT '',
    config_value longtext            NOT NULL,
    PRIMARY KEY (uid),
    KEY config_key (config_key)
);

CREATE TABLE tx_h5p_domain_model_contenttypecacheentry (
    uid                           int(11)             NOT NULL auto_increment,
    pid                           int(11)             NOT NULL DEFAULT '0',

    tstamp                        int(11) unsigned    NOT NULL DEFAULT '0',
    crdate                        int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id                     int(11) unsigned    NOT NULL DEFAULT '0',
    deleted                       tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden                        tinyint(4) unsigned NOT NULL DEFAULT '0',
    starttime                     int(11) unsigned    NOT NULL DEFAULT '0',
    endtime                       int(11) unsigned    NOT NULL DEFAULT '0',
    sorting                       int(10)             NOT NULL DEFAULT '0',

    persistence_object_identifier VARCHAR(40)         NOT NULL default '',
    machine_name                  VARCHAR(255)        NOT NULL default '',
    major_version                 int(11)             NOT NULL DEFAULT '0',
    minor_version                 int(11)             NOT NULL DEFAULT '0',
    patch_version                 int(11)             NOT NULL DEFAULT '0',
    h5p_major_version             int(11)             NOT NULL DEFAULT '0',
    h5p_minor_version             int(11)             NOT NULL DEFAULT '0',
    title                         VARCHAR(255)        NOT NULL default '',
    summary                       longtext            NOT NULL,
    description                   longtext            NOT NULL,
    icon                          longtext            NOT NULL,
    created_at                    int(11) unsigned    NOT NULL DEFAULT '0',
    updated_at                    int(11) unsigned    NOT NULL DEFAULT '0',
    is_recommended                tinyint(1)          NOT NULL DEFAULT '0',
    popularity                    int(11)             NOT NULL DEFAULT '0',
    screenshots                   longtext,
    license                       longtext,
    example                       longtext            NOT NULL,
    tutorial                      longtext,
    keywords                      longtext,
    categories                    longtext,
    owner                         longtext,
    PRIMARY KEY (uid),
    KEY identifier (persistence_object_identifier)
);

-- H5P libraries
CREATE TABLE tx_h5p_domain_model_library (
    uid              int(11)             NOT NULL auto_increment,
    pid              int(11)             NOT NULL DEFAULT '0',

    tstamp           int(11) unsigned    NOT NULL DEFAULT '0',
    crdate           int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id        int(11) unsigned    NOT NULL DEFAULT '0',
    deleted          tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden           tinyint(4) unsigned NOT NULL DEFAULT '0',
    starttime        int(11) unsigned    NOT NULL DEFAULT '0',
    endtime          int(11) unsigned    NOT NULL DEFAULT '0',
    sorting          int(10)             NOT NULL DEFAULT '0',

    add_to           longtext,
    created_at       int(11) unsigned    NOT NULL DEFAULT '0',
    updated_at       int(11) unsigned    NOT NULL DEFAULT '0',
    machine_name     VARCHAR(127)        NOT NULL default '',
    title            VARCHAR(255)        NOT NULL default '',
    major_version    int(10) unsigned    NOT NULL default '0',
    minor_version    int(10) unsigned    NOT NULL default '0',
    patch_version    int(10) unsigned    NOT NULL default '0',
    runnable         int(10) unsigned    NOT NULL default '0',
    restricted       int(10) unsigned    NOT NULL DEFAULT '0',
    fullscreen       int(10) unsigned    NOT NULL default '0',
    embed_types      VARCHAR(255)        NOT NULL default '',
    preloaded_js     text,
    preloaded_css    text,
    drop_library_css text,
    semantics        text                NOT NULL,
    tutorial_url     VARCHAR(1023)       NOT NULL default '',
    has_icon         int(10) unsigned    NOT NULL DEFAULT '0',
    PRIMARY KEY (uid),
    KEY name_version (machine_name, major_version, minor_version, patch_version),
    KEY runnable (runnable)
);

CREATE TABLE tx_h5p_domain_model_librarydependency (
    uid              int(11)             NOT NULL auto_increment,
    pid              int(11)             NOT NULL DEFAULT '0',

    tstamp           int(11) unsigned    NOT NULL DEFAULT '0',
    crdate           int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id        int(11) unsigned    NOT NULL DEFAULT '0',
    deleted          tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden           tinyint(4) unsigned NOT NULL DEFAULT '0',
    starttime        int(11) unsigned    NOT NULL DEFAULT '0',
    endtime          int(11) unsigned    NOT NULL DEFAULT '0',
    sorting          int(10)             NOT NULL DEFAULT '0',

    library          VARCHAR(40)         NOT NULL default '',
    required_library VARCHAR(40)         NOT NULL default '',
    dependency_type  VARCHAR(255)        NOT NULL default '',
    PRIMARY KEY (uid),
    KEY library (library),
    KEY requiredlibrary (required_library)
);

CREATE TABLE tx_h5p_domain_model_librarytranslation (
    uid           int(11)             NOT NULL auto_increment,
    pid           int(11)             NOT NULL DEFAULT '0',

    tstamp        int(11) unsigned    NOT NULL DEFAULT '0',
    crdate        int(11) unsigned    NOT NULL DEFAULT '0',
    cruser_id     int(11) unsigned    NOT NULL DEFAULT '0',
    deleted       tinyint(4) unsigned NOT NULL DEFAULT '0',
    hidden        tinyint(4) unsigned NOT NULL DEFAULT '0',
    starttime     int(11) unsigned    NOT NULL DEFAULT '0',
    endtime       int(11) unsigned    NOT NULL DEFAULT '0',
    sorting       int(10)             NOT NULL DEFAULT '0',

    library       VARCHAR(40)         NOT NULL DEFAULT '',
    language_code VARCHAR(255)        NOT NULL DEFAULT '',
    translation   longtext            NOT NULL,
    PRIMARY KEY (uid),
    KEY languagecode (language_code)
);

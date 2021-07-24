CREATE TABLE IF NOT EXISTS  `#__modules_lscache` (
        `moduleid` int(11) NOT NULL DEFAULT 0 NOT NULL,
	`lscache_type` smallint DEFAULT 0,
	`lscache_ttl` smallint DEFAULT 0,
	`module_type` smallint DEFAULT 0,
        `vary_language` smallint DEFAULT 1,
	PRIMARY KEY (`moduleid`)
);
/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

CREATE TABLE IF NOT EXISTS  `#__modules_lscache` (
        `moduleid` int(11) NOT NULL DEFAULT 0 NOT NULL,
	`lscache_type` smallint DEFAULT 0,
	`lscache_ttl` smallint DEFAULT 0,
	PRIMARY KEY (`moduleid`)
);

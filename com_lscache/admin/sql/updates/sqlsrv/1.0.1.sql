/* 
 *  @since      1.0.0
 *  @author     LiteSpeed Technologies <info@litespeedtech.com>
 *  @copyright  Copyright (c) 2017-2018 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 *  @license    https://opensource.org/licenses/GPL-3.0
 */

IF NOT EXISTS (SELECT * FROM sysobjects 
WHERE id = object_id(N'[dbo].[#__modules_lscache]')
AND type in (N'U'))
CREATE TABLE [#__modules_lscache] (
  [moduleid] bigint NOT NULL,
  [lscache_type] smallint DEFAULT 0,
  [lscache_ttl] smallint  DEFAULT 0,
  CONSTRAINT [PK_#__modules_lscache] PRIMARY KEY CLUSTERED (
	[moduleid] ASC
));
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
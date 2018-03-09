IF EXISTS (SELECT * FROM sysobjects 
WHERE id = object_id(N'[dbo].[#__modules_lscache]')
AND type in (N'U'))
DROP TABLE [#__modules_lscache];
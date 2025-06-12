<?php

// To take full dump of schema
// mysqldump -h {host} -u root -p affinities | sed 's/ AUTO_INCREMENT=[0-9]*\b//g' |sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/g' >affinity_backup.sql
//	This file is only for documentation purposes. It is saved as PHP so that its contents are not accidently exposed over internet
//
//	1) Export the schema before checking into bitbucket.
//		#mysqldump -u root --skip-add-drop-table --skip-comments --skip-set-charset --no-data -u root affinity | sed 's/ AUTO_INCREMENT=[0-9]*\b//g' |sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/g' >affinity.sql
//
//	2) Applying new schema from bitbucket.
//
//		2.1) First export the data
//		#mysqldump --no-create-db --no-create-info --complete-insert --extended-insert --single-transaction --skip-triggers -u root affinity > ~/data.sql
//
//		2.2) Drop all the tables
//		#grep '^LOCK TABLES' ~/data.sql |sed -e 's/LOCK TABLES/DROP TABLE/'|sed -e 's/ WRITE//'  >~/drop
//		#mysql -u root affinity <~/drop
//
//		2.3) Import schema
//		mysql -u root affinity <./affinity.sql
//
//		2.4) Import data
//		mysql -u root affinity <~/data.sql
//
//      2.5 Export to CSV
//      mysql -h localhost -u root -p -B -D affinity -e 'select * from companybranches where isactive = 1' |sed -e 's/\t/,/g' > /tmp/export.csv
//
//


// To import data from file that was created using mysql select
// LOAD DATA LOCAL INFILE '</tmp/filename.tsv>' INTO TABLE <tablename> COLUMNS TERMINATED BY '\t' IGNORE 1 ROWS;
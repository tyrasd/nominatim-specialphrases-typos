nominatim-specialphrases-typos
==============================

Search for typos in nominatim's [special phrases](https://wiki.openstreetmap.org/wiki/Nominatim/Special_Phrases) lists. This cross-checks all key-value combinations with the [taginfo database](http://taginfo.openstreetmap.org/download) to find outdated, misspelled or otherwise bogus candidates.

Installation
------------

1. install node modules
  
        npm install sqlite3
        npm install lodash
  
2. grab taginfo database files
  
        wget http://taginfo.openstreetmap.org/download/taginfo-master.db.bz2
        wget http://taginfo.openstreetmap.org/download/taginfo-wiki.db.bz2
  
3. unpack taginfo db
  
        bzip2 -d *.bz2

Running
-------

1. grab tag lists from [specialphrases](https://wiki.openstreetmap.org/wiki/Nominatim/Special_Phrases) wiki pages
  
		  nominatim_specialphrases/specialphrases.php > alltags.csv
  
2. cross-check with taginfo db

		  node typo.js


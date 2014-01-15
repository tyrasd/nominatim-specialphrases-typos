#!/usr/bin/php -Cq
<?php

        require_once('init-cmd.php');
        ini_set('memory_limit', '800M');
        ini_set('display_errors', 'stderr');

        $aCMDOptions = array(
                "Import and export special phrases",
                array('help', 'h', 0, 1, 0, 0, false, 'Show Help'),
                array('quiet', 'q', 0, 1, 0, 0, 'bool', 'Quiet output'),
                array('verbose', 'v', 0, 1, 0, 0, 'bool', 'Verbose output'),
                array('countries', '', 0, 1, 0, 0, 'bool', 'Create import script for country codes and names'),
                array('wiki-import', '', 0, 1, 0, 0, 'bool', 'Create import script for search phrases '),
        );
        getCmdOpt($_SERVER['argv'], $aCMDOptions, $aCMDResult, true, true);

		include(CONST_BasePath.'/settings/phrase_settings.php');


    if ($aCMDResult['countries']) {
        echo "select getorcreate_country(make_standard_name('uk'), 'gb');\n";
        echo "select getorcreate_country(make_standard_name('united states'), 'us');\n";
        echo "select count(*) from (select getorcreate_country(make_standard_name(country_code), country_code) from country_name where country_code is not null) as x;\n";

        echo "select count(*) from (select getorcreate_country(make_standard_name(get_name_by_language(country_name.name,ARRAY['name'])), country_code) from country_name where get_name_by_language(country_name.name, ARRAY['name']) is not null) as x;\n";
        foreach($aLanguageIn as $sLanguage)
		{
            echo "select count(*) from (select getorcreate_country(make_standard_name(get_name_by_language(country_name.name,ARRAY['name:".$sLanguage."'])), country_code) from country_name where get_name_by_language(country_name.name, ARRAY['name:".$sLanguage."']) is not null) as x;\n";
        }
    }

	if ($aCMDResult['wiki-import'] || true)
	{
		$aPairs = array();

		foreach($aLanguageIn as $sLanguage)
		{
			$sURL = 'http://wiki.openstreetmap.org/wiki/Special:Export/Nominatim/Special_Phrases/'.strtoupper($sLanguage);
			$sWikiPageXML = file_get_contents($sURL);
			if (preg_match_all('#\\| ([^|]+) \\|\\| ([^|]+) \\|\\| ([^|]+) \\|\\| ([^|]+) \\|\\| ([\\-YN])#', $sWikiPageXML, $aMatches, PREG_SET_ORDER))
			{
				foreach($aMatches as $aMatch)
				{
					$sLabel = trim($aMatch[1]);
					$sClass = trim($aMatch[2]); // osm tag-key
					$sType = trim($aMatch[3]);  // osm tag-value
					$sOperator = trim($aMatch[4]); // nominatim "operator"
					# hack around a bug where building=yes was imported with
					# quotes into the wiki
					$sType = preg_replace('/&quot;/', '', $sType);
					# sanity check, in case somebody added garbage in the wiki
					if (preg_match('/^\\w+$/', $sClass) < 1 ||
						preg_match('/^\\w+$/', $sType) < 1) {
						trigger_error("Bad class/type for language $sLanguage: $sClass=$sType");
						exit;
					}
					# blacklisting: disallow certain class/type combinations
					if (isset($aTagsBlacklist[$sClass]) && in_array($sType, $aTagsBlacklist[$sClass])) {
						# fwrite(STDERR, "Blacklisted: ".$sClass."/".$sType."\n");
						continue;
					}
					# whitelisting: if class is in whitelist, allow only tags in the list
					if (isset($aTagsWhitelist[$sClass])	&& !in_array($sType, $aTagsWhitelist[$sClass])) {
						# fwrite(STDERR, "Non-Whitelisted: ".$sClass."/".$sType."\n");
						continue;
					}
					$aPairs[$sClass.'|'.$sType] = array($sClass, $sType);

					switch($sOperator)
					{
					/*case 'near':
						echo "select getorcreate_amenityoperator(make_standard_name('".pg_escape_string($sLabel)."'), '$sClass', '$sType', 'near');\n";
						break;
					case 'in':
						echo "select getorcreate_amenityoperator(make_standard_name('".pg_escape_string($sLabel)."'), '$sClass', '$sType', 'in');\n";
						break;
					default:
						echo "select getorcreate_amenity(make_standard_name('".pg_escape_string($sLabel)."'), '$sClass', '$sType');\n";
						break;
					*/
					case 'near':
					case 'in':
						break;
					case '-':
					default:
						echo /*$sLabel, "\t",*/ $sClass, "\t", $sType, "\n";
						break;
					}
				}
			}
		}

        /*echo "create index idx_placex_classtype on placex (class, type);";

		foreach($aPairs as $aPair)
		{
			echo "create table place_classtype_".pg_escape_string($aPair[0])."_".pg_escape_string($aPair[1])." as ";
			echo "select place_id as place_id,st_centroid(geometry) as centroid from placex where ";
			echo "class = '".pg_escape_string($aPair[0])."' and type = '".pg_escape_string($aPair[1])."';\n";

			echo "CREATE INDEX idx_place_classtype_".pg_escape_string($aPair[0])."_".pg_escape_string($aPair[1])."_centroid ";
			echo "ON place_classtype_".pg_escape_string($aPair[0])."_".pg_escape_string($aPair[1])." USING GIST (centroid);\n";

			echo "CREATE INDEX idx_place_classtype_".pg_escape_string($aPair[0])."_".pg_escape_string($aPair[1])."_place_id ";
			echo "ON place_classtype_".pg_escape_string($aPair[0])."_".pg_escape_string($aPair[1])." USING btree(place_id);\n";

            echo "GRANT SELECT ON place_classtype_".pg_escape_string($aPair[0])."_".pg_escape_string($aPair[1])." TO \"www-data\";";

		}

        echo "drop index idx_placex_classtype;";*/
	}

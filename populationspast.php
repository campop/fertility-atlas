<?php

# Class to create the online atlas

require_once ('frontControllerApplication.php');
class populationspast extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'applicationName' => 'Atlas of Victorian and Edwardian Population',
			'div' => 'populationspast',
			'hostname' => 'localhost',
			'database' => 'populationspast',
			'username' => 'populationspast',
			'password' => NULL,
			'table' => 'data',
			'databaseStrictWhere' => true,
			'nativeTypes' => true,
			'administrators' => true,
			'geocoderApiKey' => NULL,
			// 'importsSectionsMode' => true,
			'datasets' => array (1851, 1861, 1881, 1891, 1901, 1911),
			'zoomedOut' => 8,	// Level at which the interface shows only overviews without detail to keep data size down
			'apiUsername' => true,
			'headerLocation' => $this->applicationRoot . '/style/header.html',
			'footerLocation' => $this->applicationRoot . '/style/footer.html',
			'guiLocationAbsolute' => true,
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Function assign additional actions
	public function actions ()
	{
		# Specify additional actions
		$actions = array (
			'home' => array (
				'description' => false,
				'url' => '',
				'tab' => $this->settings['applicationName'],
				'icon' => 'map',
			),
			'about' => array (
				'description' => 'About the Atlas of Victorian Fertility',
				'url' => 'about/',
				'tab' => 'About the Atlas',
			),
			'import' => array (
				'description' => 'Import',
				'url' => 'import/',
				'tab' => 'Import',
				'icon' => 'database_refresh',
				'administrator' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			CREATE TABLE administrators (
			  username varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Username',
			  active enum('','Yes','No') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?',
			  privilege enum('Administrator','Restricted administrator') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Administrator' COMMENT 'Administrator level',
			  PRIMARY KEY (username)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='System administrators';
			
			CREATE TABLE `data` (
			  `id` INT(11) NOT NULL COMMENT 'Automatic key',
			  `year` INT(4) NOT NULL COMMENT 'Year',
			  `CEN` INT(11) NOT NULL COMMENT 'CEN (e.g. from CEN_1851)',
			  `COUNTRY` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Country',
			  `DIVISION` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Division',
			  `REGCNTY` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'County',
			  `REGDIST` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Registration district',
			  `SUBDIST` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Sub-district',
			  `TMFR` DECIMAL(14,7) NOT NULL COMMENT 'Total Marital Fertility Rate',
			  `TFR` DECIMAL(14,7) NOT NULL COMMENT 'Total Fertility Rate',
			  `IMR` DECIMAL(14,7) NOT NULL COMMENT 'Infant Mortality Rate',
			  `LEGIT_RATE` DECIMAL(14,7) NOT NULL COMMENT 'Legitimacy Rate',
			  `ILEG_RATE` DECIMAL(14,7) NOT NULL COMMENT 'Illegitimacy Rate',
			  `ILEG_RATIO` DECIMAL(14,7) NOT NULL COMMENT 'Illegitimacy Ratio',
			  `ECMR` DECIMAL(14,7) NOT NULL COMMENT 'Early Childhood Mortality Rate',
			  `SC1` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 1',
			  `SC2` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 2',
			  `SC3` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 3',
			  `SC4` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 4',
			  `SC5` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 5',
			  `SC6` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 6',
			  `SC7` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 7',
			  `SC8` DECIMAL(14,7) NOT NULL COMMENT 'Social Class 8',
			  `FMAR_PRATE` DECIMAL(14,7) NOT NULL COMMENT 'Married women working',
			  `FNM_PRATE` DECIMAL(14,7) NOT NULL COMMENT 'Single women working',
			  `FWID_PRATE` DECIMAL(14,7) NOT NULL COMMENT 'Widowed women working',
			  `SCOT_BORN` DECIMAL(14,7) NOT NULL COMMENT 'Scottish born',
			  `IRISH_BORN` DECIMAL(14,7) NOT NULL COMMENT 'Irish born',
			  `POP_DENS` DECIMAL(14,7) NOT NULL COMMENT 'Population Density',
			  `HOUSE_SERV` DECIMAL(14,7) NOT NULL COMMENT 'Houses with a live-in servant',
			  `M_SMAM` DECIMAL(14,7) NOT NULL COMMENT 'Singulate Mean Age at Marriage (Male)',
			  `F_SMAM` DECIMAL(14,7) NOT NULL COMMENT 'Singulate Mean Age at Marriage (Female)',
			  `M_CEL_4554` DECIMAL(14,7) NOT NULL COMMENT 'Celibate (Male)',
			  `F_CEL_4554` DECIMAL(14,7) NOT NULL COMMENT 'Celibate (Female)',
			  `TYPE` VARCHAR(255) NOT NULL COMMENT 'Type of place',
			  `geometry` GEOMETRY NOT NULL COMMENT 'Geometry'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Data'
		;";
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Get the dataset fields and their labels
		$fields = $this->getFieldHeadings ();
		
		# Start the HTML
		$html = '
			
			<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
			<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
			<link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" type="text/css">
			
			<link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.3/dist/leaflet.css" />
			<script src="https://unpkg.com/leaflet@1.0.3/dist/leaflet.js"></script>
			
			<script type="text/javascript" src="' . $this->baseUrl . '/js/lib/geocoder/geocoder.js"></script>
			
			<script type="text/javascript" src="' . $this->baseUrl . '/js/lib/leaflet-fullHash/leaflet-fullHash.js"></script>
			
			<!-- Vex dialogs; see: http://github.hubspot.com/vex/ -->
			<script src="https://cdnjs.cloudflare.com/ajax/libs/vex-js/3.0.0/js/vex.combined.min.js"></script>
			<script>vex.defaultOptions.className = \'vex-theme-plain\'</script>
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vex-js/3.0.0/css/vex.min.css" />
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vex-js/3.0.0/css/vex-theme-plain.min.css" />
			
			<!-- Full screen control; see: https://github.com/Leaflet/Leaflet.fullscreen -->
			<script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>
			<link href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css" rel="stylesheet" />
			
			<!-- Geolocation control; see: https://github.com/domoritz/leaflet-locatecontrol -->
			<script src="https://domoritz.github.io/leaflet-locatecontrol/dist/L.Control.Locate.min.js" charset="utf-8"></script>
			<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
			<link rel="stylesheet" href="https://domoritz.github.io/leaflet-locatecontrol/dist/L.Control.Locate.min.css" />
			
			<script type="text/javascript" src="' . $this->baseUrl . '/js/lib/leaflet-ajax/dist/leaflet.ajax.min.js"></script>
			<script type="text/javascript" src="' . $this->baseUrl . '/js/lib/LeafletSlider/leaflet.SliderControl.min.js"></script>
			
			<script type="text/javascript" src="' . $this->baseUrl . '/js/populationspast.js"></script>
			<script type="text/javascript">
				
				var config = {
					geocoderApiKey: \'' . $this->settings['geocoderApiKey'] . '\',
					zoomedOut: ' . $this->settings['zoomedOut'] . ',
					fields: ' . json_encode ($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '
				}
				
				$(function() {
					populationspast.initialise (config, \'' . $this->baseUrl . '\');
				});
				
			</script>
			
			
			<p><em>Project under development.</em></p>
			
			<div id="mapcontainer">
				
				<div id="geocoder">
					<input type="text" name="location" autocomplete="off" placeholder="Search locations and move map" tabindex="1" />
				</div>
				
				<div id="map"></div>
				
			</div>
		';
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get the fields used by the popups
	private function getFieldHeadings ()
	{
		# Get the dataset fields
		$fields = $this->databaseConnection->getHeadings ($this->settings['database'], $this->settings['table']);
		
		# Add each year
		foreach ($this->settings['datasets'] as $dataset) {
			$fields['CEN_' . $dataset] = '#';
		}
		
		# Return the fields
		return $fields;
	}
	
	
	# About page
	public function about ()
	{
		# Load and show the HTML
		$html = file_get_contents ($this->applicationRoot . '/about.html');
		echo $html;
	}
	
	
	# Function to import the data files, clearing any existing import
	public function import ()
	{
		# Define the import types
		$importTypes = array (
			'full' => 'Full import'
		);
		
		# Create the list of import files
		$importFiles = array ();
		foreach ($this->settings['datasets'] as $dataset) {
			$importFiles[] = sprintf ('FERTILITY_%s', $dataset);
		}
		
		# Define the introduction HTML
		$fileCreationInstructionsHtml  = "\n\t" . '<p>Create the shapefile, and zip up the contents of the folder.</p>';
		
		# Run the import UI (which will output HTML)
		$this->importUi ($importFiles, $importTypes, $fileCreationInstructionsHtml, 'zip');
	}
	
	
	# Function to do the actual import
	public function doImport ($exportFiles, $importType, &$html)
	{
		# Start the HTML
		$html = '';
		
		# Enable high memory due to GeoJSON size
		ini_set ('memory_limit','200M');
		
		# Loop through each file
		$i = 0;
		foreach ($exportFiles as $dataset => $file) {
			
			# Extract the year
			preg_match ('/([0-9]{4})/', $dataset, $matches);
			$year = $matches[1];
			
			# Remove existing data file if present
			$geojson = "{$this->applicationRoot}/data/{$year}.geojson";
			if (is_file ($geojson)) {
				unlink ($geojson);
			}
			
			# Unzip the shapefile
			$path = pathinfo ($file);
			$tempDir = "{$this->applicationRoot}/exports/{$path['filename']}/";
			$command = "unzip {$file} -d {$tempDir}";		// http://stackoverflow.com/questions/8107886/create-folder-for-zip-file-and-extract-to-it
			exec ($command, $output);
			// application::dumpData ($output);
			
			# Convert to GeoJSON
			$currentDirectory = getcwd ();
			chdir ($tempDir);
			$command = "ogr2ogr -f GeoJSON -lco COORDINATE_PRECISION=4 -t_srs EPSG:4326 {$geojson} *.shp";
			exec ($command, $output);
			// application::dumpData ($output);
			chdir ($currentDirectory);	// Change back
			
			# Remove the shapefile files and containing directory
			array_map ('unlink', glob ("{$tempDir}/*.*"));	// http://php.net/unlink#109971
			rmdir ($tempDir);
			
			# Determine whether to truncate
			$truncate = ($i == 0);
			$i++;
			
			# Import the GeoJSON contents into the database
			$this->importGeojson ($geojson, $year, $truncate);
		}
		
		# Return success
		return true;
	}
	
	
	# Function to import contents of a GeoJSON file into the database
	private function importGeojson ($geojsonFilename, $year, $truncate)
	{
		# Truncate the table for the first file; requires the DROP privilege
		if ($truncate) {
			$this->databaseConnection->truncate ($this->settings['database'], $this->settings['table']);
		}
		
		# Read the file and decode to GeoJSON
		$string = file_get_contents ($geojsonFilename);
		$geojson = json_decode ($string, true);
		
		# Load conversion library
		require_once ('lib/geojson2spatialHelper.class.php');
		
		# Assemble as a set of inserts
		$inserts = array ();
		foreach ($geojson['features'] as $index => $feature) {
			
			# Start an insert with fixed properties
			$insert = array (
				'id'	=> NULL,	// Auto-assign
				'year'	=> $year,
			);
			
			# Replace CEN_1851, CEN_1861, etc. with CEN
			$fieldname = 'CEN_' . $year;
			$feature['properties']['CEN'] = $feature['properties'][$fieldname];
			unset ($feature['properties'][$fieldname]);
			
			# Add the properties
			$insert += $feature['properties'];
			
			# Add the geometry
			$insert['geometry'] = "GeomFromText('" . geojson2spatial::geojsonGeometry2wkt ($feature['geometry']) . "')";
			
			# Register the insert
			$inserts[] = $insert;
		}
		
		# Insert the data, showing any error
		if (!$this->databaseConnection->insertMany ($this->settings['database'], $this->settings['table'], $inserts, $chunking = 500)) {
			echo "\n<p class=\"warning\">ERROR:</p>";
			application::dumpData ($this->databaseConnection->error ());
		}
	}
	
	
	# API call to retrieve data
	public function apiCall_locations ()
	{
		# Obtain the supplied BBOX (W,S,E,N)
		$bbox = (isSet ($_GET['bbox']) && (substr_count ($_GET['bbox'], ',') == 3) && preg_match ('/^([-.,0-9]+)$/', $_GET['bbox']) ? explode (',', $_GET['bbox'], 4) : false);
		if (!$bbox) {
			return array ('error' => 'A valid BBOX must be supplied.');
		}
		
		# Obtain the supplied zoom
		$zoom = (isSet ($_GET['zoom']) && ctype_digit ($_GET['zoom']) ? $_GET['zoom'] : false);
		if (!$zoom) {
			return array ('error' => 'A valid zoom must be supplied.');
		}
		$zoomedOut = ($zoom <= $this->settings['zoomedOut']);
		
		# Obtain the supplied year
		$year = (isSet ($_GET['year']) && ctype_digit ($_GET['year']) ? $_GET['year'] : false);
		if (!$year) {
			return array ('error' => 'A valid year must be supplied.');
		}
		
		# Construct the BBOX WKT string
		$bboxGeom = "Polygon(({$bbox[0]} {$bbox[1]},{$bbox[2]} {$bbox[1]},{$bbox[2]} {$bbox[3]},{$bbox[0]} {$bbox[3]},{$bbox[0]} {$bbox[1]}))";
		
		# Obtain the data
		$query = "
			SELECT
			-- *,
			" . ($zoomedOut ? '' : 'year, SUBDIST, REGDIST, TMFR, ') . " TFR,
			ST_AsText(geometry) AS geometry
			FROM {$this->settings['database']}.data
			WHERE MBRIntersects(geometry, ST_GeomFromText('{$bboxGeom}') )
			AND year = {$year}
			LIMIT " . ($zoomedOut ? '1000' : '250') . "
		;";
		$data = $this->databaseConnection->getData ($query);
		
		# Determine fields that are DECIMAL so that trailing zeros are removed
		#!# Ideally this should be handled natively by the database library
		$fields = $this->databaseConnection->getFields ($this->settings['database'], 'data');
		foreach ($fields as $field => $attributes) {
			if (substr_count (strtolower ($attributes['Type']), 'decimal')) {
				foreach ($data as $index => $record) {
					if (isSet ($data[$index][$field])) {
						$data[$index][$field] = $data[$index][$field] + 0;
					}
				}
			}
		}
		
		# Convert to GeoJSON
		require_once ('geojsonRenderer.class.php');
		$geojsonRenderer = new geojsonRenderer ();
		foreach ($data as $id => $location) {
			$properties = $location;
			unset ($properties['geometry']);
			$geojsonRenderer->geometryWKT ($location['geometry'], $properties);
		}
		$data = $geojsonRenderer->getData ();
		
		# Simplify the lines to reduce data volume
		if ($zoomedOut) {
			$data = $this->simplifyLines ($data);
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to simplify lines; this is a wrapper to the Douglas-Peucker algorithm library
	#!# Migrate to ST_Simplify available in MySQL 5.7: https://dev.mysql.com/doc/refman/5.7/en/spatial-convenience-functions.html#function_st-simplify
	private function simplifyLines ($data, $thresholdMetres = 1000)
	{
		# Load the library
		require_once ('lib/simplifyLineHelper.class.php');
		$simplifyLine = new simplifyLine ();
		
		# Simplify each feature
		foreach ($data['features'] as $featureIndex => $feature) {
			switch ($feature['geometry']['type']) {
					
				case 'Polygon':
					foreach ($feature['geometry']['coordinates'] as $coordinateSetIndex => $coordinates) {
						$data['features'][$featureIndex]['geometry']['coordinates'][$coordinateSetIndex] = $simplifyLine->straighten ($coordinates, $thresholdMetres);
					}
					break;
					
				case 'MultiPolygon':
					foreach ($feature['geometry']['coordinates'] as $polygonIndex => $polygons) {
						foreach ($polygons as $coordinateSetIndex => $coordinates) {
							$data['features'][$featureIndex]['geometry']['coordinates'][$polygonIndex][$coordinateSetIndex] = $simplifyLine->straighten ($coordinates, $thresholdMetres);
						}
					}
					break;
			}
		}
		
		# Return the data
		return $data;
	}
}

?>
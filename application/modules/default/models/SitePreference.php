<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Site Preference Model
 *
 * Manages the Global Configuration Singleton for the entire HRMS.
 * Complies with SSOT v6.7 (Singleton Pattern, Atomic File Caching).
 *
 * @category   Application
 * @package    Default
 * @subpackage Models
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_Model_Sitepreference extends Zend_Db_Table_Abstract
{
	/** @var string Primary table name */
	protected $_name = 'main_sitepreference';

	/** @var string Primary key */
	protected $_primary = 'id';

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

	/**
	 * Fetch the Singleton Configuration Record.
	 * We always assume ID = 1 for the global configuration.
	 * 
	 * @return array|null
	 */
	public function getSingletonRecord()
	{
		$select = $this->select()
			->where('id = ?', 1)
			->where('isactive != ?', 5);

		$row = $this->fetchRow($select);
		return $row ? $row->toArray() : null;
	}

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

	/**
	 * Saves the global configuration and automatically rebuilds the File Cache.
	 * 
	 * @param array $data
	 * @return string 'update'
	 */
	public function saveSingletonRecord(array $data)
	{
		$auth = Zend_Auth::getInstance();
		$userId = $auth->hasIdentity() ? $auth->getStorage()->read()->id : 0;
		$currentDate = gmdate("Y-m-d H:i:s");

		$data['modifiedby']   = $userId;
		$data['modifieddate'] = $currentDate;

		// Force update to ID 1
		$this->update($data, array('id = ?' => 1));

		// SSOT X.2: Trigger Atomic Cache Rebuild
		$this->generateConfigCacheFile();

		return 'update';
	}

    /* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS, & INTERNAL HELPERS
     * ================================================================================= */

	/**
	 * Generates a flat PHP array of the site preferences and saves it to a cache file.
	 * Uses Atomic File Swapping (SSOT X.2) to prevent race conditions.
	 * 
	 * @return bool True on success
	 */
	public function generateConfigCacheFile()
	{
		$record = $this->getSingletonRecord();
		if (!$record) return false;

		$cacheDir  = DATA_PATH; // Ensure this constant points to /data
		$liveFile  = $cacheDir . '/site_preferences_cache.php';

		// Build the file content
		$fileContent = "<?php\n";
		$fileContent .= "// Auto-generated Site Preferences Cache File\n";
		$fileContent .= "// Generated on: " . gmdate("Y-m-d H:i:s") . " GMT\n";
		$fileContent .= "return " . var_export($record, true) . ";\n";

		// SSOT X.2: Atomic File Swap
		$tempFile = tempnam($cacheDir, 'config_');
		if ($tempFile === false) return false;

		if (file_put_contents($tempFile, $fileContent) !== false) {
			chmod($tempFile, 0666);
			if (rename($tempFile, $liveFile)) {
				return true;
			}
		}

		// Cleanup if atomic swap failed
		@unlink($tempFile);
		return false;
	}
}

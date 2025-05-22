<?php
/**
 * Test file to check if set_time_limit(0) is working correctly
 */

// Include the plugin file
require_once __DIR__ . '../jekyll-exporter.php';

// Create a mock instance of the class
class TimeoutTestExporter extends Jekyll_Export {
	public function test_export() {
		// Call the export method with output buffering
		ob_start();
		$this->export();
		ob_end_clean();

		echo "Export completed successfully without timeout.\n";
	}
}

// Create an instance and test
$test_exporter = new TimeoutTestExporter();
$test_exporter->test_export();

echo "Test completed.\n";

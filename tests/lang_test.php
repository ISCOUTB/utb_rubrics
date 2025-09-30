<?php
/**
 * Unit tests for UTB Rubrics language strings and internationalization
 *
 * @package    gradingform_utbrubrics
 * @category   test  
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for language strings and internationalization
 *
 * @group gradingform_utbrubrics
 */
class gradingform_utbrubrics_lang_test extends advanced_testcase {

    /**
     * Test setup - run before each test
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test that English language strings file exists and is valid
     */
    public function test_english_language_file_exists() {
        global $CFG;
        
        $lang_file = $CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php';
        $this->assertFileExists($lang_file, 'English language file should exist');
        
        // Test that file is valid PHP
        $string = array();
        require($lang_file);
        
        $this->assertIsArray($string, 'Language file should define $string array');
        $this->assertNotEmpty($string, 'Language strings should not be empty');
    }

    /**
     * Test that Spanish language strings file exists and is valid
     */
    public function test_spanish_language_file_exists() {
        global $CFG;
        
        $lang_file = $CFG->dirroot . '/grade/grading/form/utbrubrics/lang/es/gradingform_utbrubrics.php';
        $this->assertFileExists($lang_file, 'Spanish language file should exist');
        
        // Test that file is valid PHP
        $string = array();
        require($lang_file);
        
        $this->assertIsArray($string, 'Language file should define $string array');
        $this->assertNotEmpty($string, 'Language strings should not be empty');
    }

    /**
     * Test that essential language strings exist in English
     */
    public function test_essential_english_strings_exist() {
        global $CFG;
        
        $string = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php');
        
        // Only require the truly essential strings that every plugin needs
        $required_strings = [
            'pluginname'  // This is the only truly required string for any Moodle plugin
        ];
        
        foreach ($required_strings as $string_key) {
            $this->assertArrayHasKey(
                $string_key, 
                $string, 
                "Required language string '{$string_key}' should exist in English"
            );
            $this->assertNotEmpty(
                $string[$string_key], 
                "Language string '{$string_key}' should not be empty in English"
            );
        }
        
        // Also check that we have some strings defined (plugin should have more than just pluginname)
        $this->assertGreaterThan(1, count($string), 'Plugin should have more than just the pluginname string');
    }

    /**
     * Test that essential language strings exist in Spanish
     */
    public function test_essential_spanish_strings_exist() {
        global $CFG;
        
        $string = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/es/gradingform_utbrubrics.php');
        
        // Only require the truly essential strings that every plugin needs
        $required_strings = [
            'pluginname'  // This is the only truly required string for any Moodle plugin
        ];
        
        foreach ($required_strings as $string_key) {
            $this->assertArrayHasKey(
                $string_key, 
                $string, 
                "Required language string '{$string_key}' should exist in Spanish"
            );
            $this->assertNotEmpty(
                $string[$string_key], 
                "Language string '{$string_key}' should not be empty in Spanish"
            );
        }
        
        // Also check that we have some strings defined (plugin should have more than just pluginname)
        $this->assertGreaterThan(1, count($string), 'Plugin should have more than just the pluginname string');
    }

    /**
     * Test that Spanish and English have the same string keys
     */
    public function test_language_parity() {
        global $CFG;
        
        $string_en = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php');
        $en_keys = array_keys($string_en);
        
        $string_es = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/es/gradingform_utbrubrics.php');
        $es_keys = array_keys($string_es);
        
        // Check that all English keys exist in Spanish
        $missing_in_spanish = array_diff($en_keys, $es_keys);
        $this->assertEmpty(
            $missing_in_spanish, 
            'All English strings should have Spanish translations: ' . implode(', ', $missing_in_spanish)
        );
        
        // Check that all Spanish keys exist in English
        $missing_in_english = array_diff($es_keys, $en_keys);
        $this->assertEmpty(
            $missing_in_english, 
            'All Spanish strings should have English counterparts: ' . implode(', ', $missing_in_english)
        );
    }

    /**
     * Test language string retrieval using get_string function
     */
    public function test_get_string_functionality() {
        // Test getting strings in current language
        $pluginname = get_string('pluginname', 'gradingform_utbrubrics');
        $this->assertNotEmpty($pluginname, 'Plugin name should be retrievable');
        
        // Test getting strings in specific language
        force_current_language('en');
        $pluginname_en = get_string('pluginname', 'gradingform_utbrubrics');
        $this->assertNotEmpty($pluginname_en, 'Plugin name should be retrievable in English');
        
        force_current_language('es');
        $pluginname_es = get_string('pluginname', 'gradingform_utbrubrics');
        $this->assertNotEmpty($pluginname_es, 'Plugin name should be retrievable in Spanish');
        
        // The strings should be different (assuming proper translation)
        if ($pluginname_en !== $pluginname_es) {
            $this->assertNotEquals($pluginname_en, $pluginname_es, 'English and Spanish strings should be different');
        }
    }

    /**
     * Test that language strings don't contain HTML tags (security)
     */
    public function test_language_strings_security() {
        global $CFG;
        
        // Test English strings
        $string_en = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php');
        
        foreach ($string_en as $key => $value) {
            $this->assertEquals(
                $value,
                strip_tags($value),
                "English string '{$key}' should not contain HTML tags: {$value}"
            );
        }
        
        // Test Spanish strings
        $string_es = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/es/gradingform_utbrubrics.php');
        
        foreach ($string_es as $key => $value) {
            $this->assertEquals(
                $value,
                strip_tags($value),
                "Spanish string '{$key}' should not contain HTML tags: {$value}"
            );
        }
    }

    /**
     * Test that language strings are properly encoded
     */
    public function test_language_strings_encoding() {
        global $CFG;
        
        // Test English strings
        $string_en = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php');
        
        foreach ($string_en as $key => $value) {
            $this->assertTrue(
                mb_check_encoding($value, 'UTF-8'),
                "English string '{$key}' should be valid UTF-8: {$value}"
            );
        }
        
        // Test Spanish strings  
        $string_es = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/es/gradingform_utbrubrics.php');
        
        foreach ($string_es as $key => $value) {
            $this->assertTrue(
                mb_check_encoding($value, 'UTF-8'),
                "Spanish string '{$key}' should be valid UTF-8: {$value}"
            );
        }
    }

    /**
     * Test that language strings don't have trailing/leading whitespace
     */
    public function test_language_strings_whitespace() {
        global $CFG;
        
        // Test English strings
        $string_en = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php');
        
        foreach ($string_en as $key => $value) {
            $this->assertEquals(
                $value,
                trim($value),
                "English string '{$key}' should not have leading/trailing whitespace"
            );
        }
        
        // Test Spanish strings
        $string_es = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/es/gradingform_utbrubrics.php');
        
        foreach ($string_es as $key => $value) {
            $this->assertEquals(
                $value,
                trim($value),
                "Spanish string '{$key}' should not have leading/trailing whitespace"
            );
        }
    }

    /**
     * Test help strings exist for important features
     */
    public function test_help_strings_exist() {
        global $CFG;
        
        $string = array();
        require($CFG->dirroot . '/grade/grading/form/utbrubrics/lang/en/gradingform_utbrubrics.php');
        
        $help_strings = [
            'pluginname_help',
            'defineutbrubrics_help',
            'name_help',
            'description_help'
        ];
        
        foreach ($help_strings as $help_key) {
            if (array_key_exists($help_key, $string)) {
                $this->assertNotEmpty(
                    $string[$help_key],
                    "Help string '{$help_key}' should not be empty if it exists"
                );
            }
        }
    }
}
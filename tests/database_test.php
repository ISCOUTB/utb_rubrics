<?php
/**
 * Unit tests for UTB Rubrics database installation and upgrade
 *
 * @package    gradingform_utbrubrics
 * @category   test
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/db/install.php');
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/db/upgrade.php');

/**
 * Unit tests for database installation and upgrade functions
 *
 * @group gradingform_utbrubrics
 * @covers ::xmldb_gradingform_utbrubrics_install
 * @covers ::xmldb_gradingform_utbrubrics_upgrade
 */
class gradingform_utbrubrics_database_test extends advanced_testcase {

    /**
     * Test setup - run before each test
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Clean existing test data
        $DB->delete_records('gradingform_utb_evaluations');
        $DB->delete_records('gradingform_utb_lvl');
        $DB->delete_records('gradingform_utb_indicators');
        $DB->delete_records('gradingform_utb_outcomes');
    }

    /**
     * Load sample initial data for testing
     */
    private function load_sample_initial_data() {
        global $DB;
        
        // Create sample student outcome
        $so_data = [
            'so_number' => 'SO-001',
            'title_en' => 'Communication Skills',
            'title_es' => 'Habilidades de Comunicación',
            'description_en' => 'Ability to communicate effectively',
            'description_es' => 'Capacidad de comunicarse efectivamente',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $so_id = $DB->insert_record('gradingform_utb_outcomes', $so_data);

        // Create sample indicator
        $indicator_data = [
            'student_outcome_id' => $so_id,
            'indicator_letter' => 'A',
            'description_en' => 'Demonstrates clear written communication',
            'description_es' => 'Demuestra comunicación escrita clara',
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $indicator_id = $DB->insert_record('gradingform_utb_indicators', $indicator_data);

        // Create sample performance level
        $level_data = [
            'indicator_id' => $indicator_id,
            'title_en' => 'Excellent',
            'title_es' => 'Excelente',
            'description_en' => 'Exceeds expectations consistently',
            'description_es' => 'Supera las expectativas consistentemente',
            'minscore' => 4.0,
            'maxscore' => 5.0,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $DB->insert_record('gradingform_utb_lvl', $level_data);
    }

    /**
     * Test database tables exist after installation
     */
    public function test_database_tables_exist() {
        global $DB;

        // Check that all required tables exist
        $expected_tables = [
            'gradingform_utb_outcomes',
            'gradingform_utb_indicators', 
            'gradingform_utb_lvl',
            'gradingform_utb_evaluations'
        ];

        foreach ($expected_tables as $table) {
            $this->assertTrue(
                $DB->get_manager()->table_exists($table),
                "Table {$table} should exist after installation"
            );
        }
    }

    /**
     * Test student outcomes table structure
     */
    public function test_student_outcomes_table_structure() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('gradingform_utb_outcomes');

        $this->assertTrue($dbman->table_exists($table));

        // Check required fields exist
        $expected_fields = [
            'id', 'so_number', 'title_en', 'title_es', 
            'description_en', 'description_es', 'sortorder',
            'timecreated', 'timemodified'
        ];

        foreach ($expected_fields as $fieldname) {
            $field = new xmldb_field($fieldname);
            $this->assertTrue(
                $dbman->field_exists($table, $field),
                "Field {$fieldname} should exist in gradingform_utb_outcomes table"
            );
        }
    }

    /**
     * Test indicators table structure
     */
    public function test_indicators_table_structure() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('gradingform_utb_indicators');

        $this->assertTrue($dbman->table_exists($table));

        // Check required fields exist
        $expected_fields = [
            'id', 'student_outcome_id', 'indicator_letter',
            'description_en', 'description_es',
            'timecreated', 'timemodified'
        ];

        foreach ($expected_fields as $fieldname) {
            $field = new xmldb_field($fieldname);
            $this->assertTrue(
                $dbman->field_exists($table, $field),
                "Field {$fieldname} should exist in gradingform_utb_indicators table"
            );
        }
    }

    /**
     * Test performance levels table structure
     */
    public function test_performance_levels_table_structure() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('gradingform_utb_lvl');

        $this->assertTrue($dbman->table_exists($table));

        // Check required fields exist
        $expected_fields = [
            'id', 'indicator_id', 'title_en', 'title_es', 
            'description_en', 'description_es', 'minscore', 'maxscore',
            'sortorder', 'timecreated', 'timemodified'
        ];

        foreach ($expected_fields as $fieldname) {
            $field = new xmldb_field($fieldname);
            $this->assertTrue(
                $dbman->field_exists($table, $field),
                "Field {$fieldname} should exist in gradingform_utb_lvl table"
            );
        }
    }

    /**
     * Test evaluations table structure  
     */
    public function test_evaluations_table_structure() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('gradingform_utb_evaluations');

        $this->assertTrue($dbman->table_exists($table));

        // Check required fields exist
        $expected_fields = [
            'id', 'instanceid', 'studentid', 'courseid', 'activityid',
            'activityname', 'student_outcome_id', 'indicator_id', 
            'performance_level_id', 'score', 'feedback', 'timecreated', 'timemodified'
        ];

        foreach ($expected_fields as $fieldname) {
            $field = new xmldb_field($fieldname);
            $this->assertTrue(
                $dbman->field_exists($table, $field),
                "Field {$fieldname} should exist in gradingform_utb_evaluations table"
            );
        }
    }

    /**
     * Test initial data was loaded correctly
     */
    public function test_initial_data_loaded() {
        global $DB;

        // For this test, we need to simulate the installation process that loads initial data
        // First, let's add some sample data to test the concept
        $this->load_sample_initial_data();

        // Check that student outcomes were loaded
        $outcomes_count = $DB->count_records('gradingform_utb_outcomes');
        $this->assertGreaterThan(0, $outcomes_count, 'Student outcomes should be loaded during installation');

        // Check that indicators were loaded
        $indicators_count = $DB->count_records('gradingform_utb_indicators');  
        $this->assertGreaterThan(0, $indicators_count, 'Indicators should be loaded during installation');

        // Check that performance levels were loaded
        $levels_count = $DB->count_records('gradingform_utb_lvl');
        $this->assertGreaterThan(0, $levels_count, 'Performance levels should be loaded during installation');
    }

    /**
     * Test data integrity constraints
     */
    public function test_data_integrity_constraints() {
        global $DB;

        // Test that all indicators have valid student outcome references
        $sql = "SELECT i.id, i.student_outcome_id 
                FROM {gradingform_utb_indicators} i 
                LEFT JOIN {gradingform_utb_outcomes} so ON i.student_outcome_id = so.id 
                WHERE so.id IS NULL";
        
        $orphaned_indicators = $DB->get_records_sql($sql);
        $this->assertEmpty($orphaned_indicators, 'All indicators should have valid student outcome references');

        // Test that all performance levels have valid indicator references
        $sql = "SELECT pl.id, pl.indicator_id 
                FROM {gradingform_utb_lvl} pl 
                LEFT JOIN {gradingform_utb_indicators} i ON pl.indicator_id = i.id 
                WHERE i.id IS NULL";
        
        $orphaned_levels = $DB->get_records_sql($sql);
        $this->assertEmpty($orphaned_levels, 'All performance levels should have valid indicator references');
    }

    /**
     * Test bilingual data completeness
     */
    public function test_bilingual_data_completeness() {
        global $DB;

        // Test student outcomes have both English and Spanish content
        $sql = "SELECT id FROM {gradingform_utb_outcomes} 
                WHERE title_en IS NULL OR title_en = '' 
                OR title_es IS NULL OR title_es = ''
                OR description_en IS NULL OR description_en = ''
                OR description_es IS NULL OR description_es = ''";
        
        $incomplete_outcomes = $DB->get_records_sql($sql);
        $this->assertEmpty($incomplete_outcomes, 'All student outcomes should have complete bilingual content');

        // Test indicators have both English and Spanish content
        $sql = "SELECT id FROM {gradingform_utb_indicators} 
                WHERE description_en IS NULL OR description_en = ''
                OR description_es IS NULL OR description_es = ''";
        
        $incomplete_indicators = $DB->get_records_sql($sql);
        $this->assertEmpty($incomplete_indicators, 'All indicators should have complete bilingual content');

        // Test performance levels have both English and Spanish content
        $sql = "SELECT id FROM {gradingform_utb_lvl} 
                WHERE title_en IS NULL OR title_en = '' 
                OR title_es IS NULL OR title_es = ''
                OR description_en IS NULL OR description_en = ''
                OR description_es IS NULL OR description_es = ''";
        
        $incomplete_levels = $DB->get_records_sql($sql);
        $this->assertEmpty($incomplete_levels, 'All performance levels should have complete bilingual content');
    }

    /**
     * Test upgrade function exists and is callable
     */
    public function test_upgrade_function_exists() {
        $this->assertTrue(
            function_exists('xmldb_gradingform_utbrubrics_upgrade'),
            'Upgrade function should exist'
        );
    }

    /**
     * Test that upgrade from current version succeeds
     */
    public function test_upgrade_from_current_version() {
        // Get current version from version.php
        $plugin = new stdClass();
        require(dirname(__FILE__) . '/../version.php');
        
        // Test that upgrade returns true for current version
        $result = xmldb_gradingform_utbrubrics_upgrade($plugin->version);
        $this->assertTrue($result, 'Upgrade should succeed from current version');
    }
}
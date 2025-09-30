<?php
/**
 * Unit tests for UTB Rubrics helper functions
 *
 * @package    gradingform_utbrubrics
 * @category   test
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/db/helper_functions.php');

/**
 * Unit tests for UTB Rubrics helper functions
 *
 * @group gradingform_utbrubrics
 * @covers ::gradingform_utbrubrics_get_student_outcomes
 * @covers ::gradingform_utbrubrics_get_indicators_for_so
 * @covers ::gradingform_utbrubrics_get_performance_levels_for_indicator
 */
class gradingform_utbrubrics_helper_functions_test extends advanced_testcase {

    /** @var object Test course */
    private $course;

    /** @var object Test student outcome */
    private $test_so;

    /** @var object Test indicator */
    private $test_indicator;

    /** @var object Test performance level */
    private $test_performance_level;

    /**
     * Test setup - run before each test.
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

        // Create test data
        $this->create_test_data();
    }

    /**
     * Create test data for student outcomes, indicators, and performance levels
     */
    private function create_test_data() {
        global $DB;

        // Create test Student Outcome
        $so_data = [
            'so_number' => 'SO-TEST-001',
            'title_en' => 'Test Student Outcome English',
            'title_es' => 'Resultado de Estudiante de Prueba',
            'description_en' => 'Test description in English',
            'description_es' => 'Descripción de prueba en español',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $this->test_so = (object)$so_data;
        $this->test_so->id = $DB->insert_record('gradingform_utb_outcomes', $so_data);

        // Create test Indicator (note: indicators only have description, not title)
        $indicator_data = [
            'student_outcome_id' => $this->test_so->id,
            'indicator_letter' => 'A',
            'description_en' => 'Test indicator description in English',
            'description_es' => 'Descripción del indicador de prueba en español',
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $this->test_indicator = (object)$indicator_data;
        $this->test_indicator->id = $DB->insert_record('gradingform_utb_indicators', $indicator_data);

        // Create test Performance Level
        $pl_data = [
            'indicator_id' => $this->test_indicator->id,
            'title_en' => 'Proficient',
            'title_es' => 'Competente',
            'description_en' => 'Student demonstrates proficiency',
            'description_es' => 'El estudiante demuestra competencia',
            'minscore' => 2.5,
            'maxscore' => 3.5,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $this->test_performance_level = (object)$pl_data;
        $this->test_performance_level->id = $DB->insert_record('gradingform_utb_lvl', $pl_data);
    }

    /**
     * Test getting student outcomes with default language detection
     */
    public function test_get_student_outcomes_default_language() {
        $outcomes = gradingform_utbrubrics_get_student_outcomes();
        
        $this->assertIsArray($outcomes);
        $this->assertCount(1, $outcomes);
        
        $outcome = $outcomes[0];
        $this->assertEquals($this->test_so->id, $outcome->id);
        $this->assertEquals('SO-TEST-001', $outcome->so_number);
        $this->assertEquals('SO-TEST-001', $outcome->outcome_code);
        
        // Should default to English
        $this->assertEquals('Test Student Outcome English', $outcome->title);
        $this->assertEquals('Test description in English', $outcome->description);
    }

    /**
     * Test getting student outcomes in English
     */
    public function test_get_student_outcomes_english() {
        $outcomes = gradingform_utbrubrics_get_student_outcomes('en');
        
        $this->assertIsArray($outcomes);
        $this->assertCount(1, $outcomes);
        
        $outcome = $outcomes[0];
        $this->assertEquals('Test Student Outcome English', $outcome->title);
        $this->assertEquals('Test description in English', $outcome->description);
        
        // Check that indicators are included
        $this->assertIsArray($outcome->indicators);
        $this->assertCount(1, $outcome->indicators);
    }

    /**
     * Test getting student outcomes in Spanish
     */
    public function test_get_student_outcomes_spanish() {
        $outcomes = gradingform_utbrubrics_get_student_outcomes('es');
        
        $this->assertIsArray($outcomes);
        $this->assertCount(1, $outcomes);
        
        $outcome = $outcomes[0];
        $this->assertEquals('Resultado de Estudiante de Prueba', $outcome->title);
        $this->assertEquals('Descripción de prueba en español', $outcome->description);
        
        // Check that indicators are included
        $this->assertIsArray($outcome->indicators);
        $this->assertCount(1, $outcome->indicators);
    }

    /**
     * Test invalid language parameter defaults to English
     */
    public function test_get_student_outcomes_invalid_language() {
        $outcomes = gradingform_utbrubrics_get_student_outcomes('fr'); // Invalid language
        
        $this->assertIsArray($outcomes);
        $outcome = $outcomes[0];
        
        // Should default to English
        $this->assertEquals('Test Student Outcome English', $outcome->title);
        $this->assertEquals('Test description in English', $outcome->description);
    }

    /**
     * Test getting indicators for student outcome in English
     */
    public function test_get_indicators_for_so_english() {
        $indicators = gradingform_utbrubrics_get_indicators_for_so($this->test_so->id, 'en');
        
        $this->assertIsArray($indicators);
        $this->assertCount(1, $indicators);
        
        $indicator = $indicators[0];
        $this->assertEquals($this->test_indicator->id, $indicator->id);
        $this->assertEquals('A', $indicator->indicator_letter);
        $this->assertEquals('Test indicator description in English', $indicator->description);
        
        // Check that performance levels are included
        $this->assertIsArray($indicator->performance_levels);
        $this->assertCount(1, $indicator->performance_levels);
    }

    /**
     * Test getting indicators for student outcome in Spanish
     */
    public function test_get_indicators_for_so_spanish() {
        $indicators = gradingform_utbrubrics_get_indicators_for_so($this->test_so->id, 'es');
        
        $this->assertIsArray($indicators);
        $this->assertCount(1, $indicators);
        
        $indicator = $indicators[0];
        $this->assertEquals('Descripción del indicador de prueba en español', $indicator->description);
    }

    /**
     * Test getting performance levels for indicator in English
     */
    public function test_get_performance_levels_for_indicator_english() {
        $levels = gradingform_utbrubrics_get_performance_levels_for_indicator($this->test_indicator->id, 'en');
        
        $this->assertIsArray($levels);
        $this->assertCount(1, $levels);
        
        $level = $levels[0];
        $this->assertEquals($this->test_performance_level->id, $level->id);
        $this->assertEquals('Proficient', $level->level_name);
        $this->assertEquals('Student demonstrates proficiency', $level->description);
        $this->assertEquals(2.5, $level->minscore);
        $this->assertEquals(3.5, $level->maxscore);
    }

    /**
     * Test getting performance levels for indicator in Spanish
     */
    public function test_get_performance_levels_for_indicator_spanish() {
        $levels = gradingform_utbrubrics_get_performance_levels_for_indicator($this->test_indicator->id, 'es');
        
        $this->assertIsArray($levels);
        $this->assertCount(1, $levels);
        
        $level = $levels[0];
        $this->assertEquals('Competente', $level->level_name);
        $this->assertEquals('El estudiante demuestra competencia', $level->description);
    }

    /**
     * Test getting indicators for non-existent student outcome
     */
    public function test_get_indicators_for_nonexistent_so() {
        $indicators = gradingform_utbrubrics_get_indicators_for_so(99999, 'en');
        
        $this->assertIsArray($indicators);
        $this->assertEmpty($indicators);
    }

    /**
     * Test getting performance levels for non-existent indicator
     */
    public function test_get_performance_levels_for_nonexistent_indicator() {
        $levels = gradingform_utbrubrics_get_performance_levels_for_indicator(99999, 'en');
        
        $this->assertIsArray($levels);
        $this->assertEmpty($levels);
    }

    /**
     * Test data structure integrity
     */
    public function test_data_structure_integrity() {
        $outcomes = gradingform_utbrubrics_get_student_outcomes('en');
        
        // Test that all expected fields are present
        $outcome = $outcomes[0];
        $required_fields = ['id', 'so_number', 'title', 'description', 'sortorder', 'timecreated', 'timemodified', 'indicators', 'outcome_code'];
        
        foreach ($required_fields as $field) {
            $this->assertObjectHasAttribute($field, $outcome, "Outcome missing required field: {$field}");
        }
        
        // Test indicator structure
        $indicator = $outcome->indicators[0];
        $required_indicator_fields = ['id', 'indicator_letter', 'description', 'performance_levels'];
        
        foreach ($required_indicator_fields as $field) {
            $this->assertObjectHasAttribute($field, $indicator, "Indicator missing required field: {$field}");
        }
        
        // Test performance level structure
        $level = $indicator->performance_levels[0];
        $required_level_fields = ['id', 'level_name', 'description', 'minscore', 'maxscore'];
        
        foreach ($required_level_fields as $field) {
            $this->assertObjectHasAttribute($field, $level, "Performance level missing required field: {$field}");
        }
    }
}
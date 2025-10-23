<?php
/**
 * Unit tests for UTB Rubrics Web Service API
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradingform_utbrubrics;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/classes/external/get_evaluations.php');
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/classes/external/get_student_outcomes.php');

use advanced_testcase;
use gradingform_utbrubrics\external\get_evaluations;
use gradingform_utbrubrics\external\get_student_outcomes;

/**
 * Test cases for UTB Rubrics API functions
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_test extends advanced_testcase {

    /**
     * Test get_student_outcomes returns data
     */
    public function test_get_student_outcomes_returns_data() {
        global $DB;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a student outcome
        $so_id = $DB->insert_record('gradingform_utb_so', [
            'so_number' => 'SO1',
            'title_en' => 'Test Outcome',
            'title_es' => 'Resultado de Prueba',
            'description_en' => 'Test description',
            'description_es' => 'Descripción de prueba',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create an indicator
        $ind_id = $DB->insert_record('gradingform_utb_ind', [
            'student_outcome_id' => $so_id,
            'indicator_letter' => 'A',
            'description_en' => 'Test indicator',
            'description_es' => 'Indicador de prueba',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create a performance level
        $DB->insert_record('gradingform_utb_lvl', [
            'indicator_id' => $ind_id,
            'title_en' => 'Excellent',
            'title_es' => 'Excelente',
            'description_en' => 'Excellent work',
            'description_es' => 'Trabajo excelente',
            'minscore' => 3.6,
            'maxscore' => 4.0,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Test English
        $result = get_student_outcomes::execute('en');
        
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('student_outcomes', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertEquals('en', $result['language']);
        $this->assertGreaterThan(0, $result['count']);
        
        $outcome = $result['student_outcomes'][0];
        $this->assertEquals('SO1', $outcome['so_number']);
        $this->assertEquals('Test Outcome', $outcome['title']);
        $this->assertArrayHasKey('indicators', $outcome);
        $this->assertNotEmpty($outcome['indicators']);
        
        $indicator = $outcome['indicators'][0];
        $this->assertEquals('A', $indicator['letter']);
        $this->assertArrayHasKey('performance_levels', $indicator);
        $this->assertNotEmpty($indicator['performance_levels']);
        
        $level = $indicator['performance_levels'][0];
        $this->assertEquals('Excellent', $level['name']);
        $this->assertEquals(3.6, $level['min_score']);
        $this->assertEquals(4.0, $level['max_score']);
    }

    /**
     * Test get_student_outcomes with Spanish language
     */
    public function test_get_student_outcomes_spanish() {
        global $DB;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a student outcome
        $so_id = $DB->insert_record('gradingform_utb_so', [
            'so_number' => 'SO2',
            'title_en' => 'Problem Solving',
            'title_es' => 'Resolución de Problemas',
            'description_en' => 'Ability to solve problems',
            'description_es' => 'Capacidad para resolver problemas',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Test Spanish
        $result = get_student_outcomes::execute('es');
        
        $this->assertEquals('es', $result['language']);
        $outcome = $result['student_outcomes'][0];
        $this->assertEquals('Resolución de Problemas', $outcome['title']);
    }

    /**
     * Test get_evaluations with no filters
     */
    public function test_get_evaluations_no_filters() {
        global $DB;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create necessary records
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // Create grading definition
        $definition = $DB->insert_record('grading_definitions', [
            'areaid' => 1,
            'method' => 'utbrubrics',
            'name' => 'Test Rubric',
            'status' => 20,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create student outcome, indicator, and level
        $so_id = $DB->insert_record('gradingform_utb_so', [
            'so_number' => 'SO1',
            'title_en' => 'Test',
            'title_es' => 'Prueba',
            'description_en' => 'Test',
            'description_es' => 'Prueba',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $ind_id = $DB->insert_record('gradingform_utb_ind', [
            'student_outcome_id' => $so_id,
            'indicator_letter' => 'A',
            'description_en' => 'Test',
            'description_es' => 'Prueba',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $lvl_id = $DB->insert_record('gradingform_utb_lvl', [
            'indicator_id' => $ind_id,
            'title_en' => 'Good',
            'title_es' => 'Bueno',
            'description_en' => 'Good work',
            'description_es' => 'Buen trabajo',
            'minscore' => 2.8,
            'maxscore' => 3.5,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create assign grade
        $grade = $DB->insert_record('assign_grades', [
            'assignment' => $assign->id,
            'userid' => $student->id,
            'grader' => $teacher->id,
            'grade' => 85,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create grading instance
        $instance = $DB->insert_record('grading_instances', [
            'definitionid' => $definition,
            'raterid' => $teacher->id,
            'itemid' => $grade,
            'status' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create evaluation
        $DB->insert_record('gradingform_utb_eval', [
            'instanceid' => $instance,
            'indicator_id' => $ind_id,
            'performance_level_id' => $lvl_id,
            'score' => 3.0,
            'feedback' => 'Good work',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Test API
        $result = get_evaluations::execute(0, 0, 0, 0);
        
        $this->assertArrayHasKey('evaluations', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertGreaterThan(0, $result['count']);
        
        $eval = $result['evaluations'][0];
        $this->assertEquals('A', $eval['indicator_letter']);
        $this->assertEquals('SO1', $eval['so_number']);
        $this->assertEquals(3.0, $eval['score']);
    }

    /**
     * Test get_evaluations filtered by grader
     */
    public function test_get_evaluations_filter_by_grader() {
        global $DB;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create users
        $teacher1 = $this->getDataGenerator()->create_user();
        $teacher2 = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // Create definition and SO structure
        $definition = $DB->insert_record('grading_definitions', [
            'areaid' => 1,
            'method' => 'utbrubrics',
            'name' => 'Test',
            'status' => 20,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $so_id = $DB->insert_record('gradingform_utb_so', [
            'so_number' => 'SO1',
            'title_en' => 'Test',
            'title_es' => 'Prueba',
            'description_en' => 'Test',
            'description_es' => 'Prueba',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $ind_id = $DB->insert_record('gradingform_utb_ind', [
            'student_outcome_id' => $so_id,
            'indicator_letter' => 'A',
            'description_en' => 'Test',
            'description_es' => 'Prueba',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $lvl_id = $DB->insert_record('gradingform_utb_lvl', [
            'indicator_id' => $ind_id,
            'title_en' => 'Good',
            'title_es' => 'Bueno',
            'description_en' => 'Good',
            'description_es' => 'Bueno',
            'minscore' => 2.8,
            'maxscore' => 3.5,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create evaluations from two different teachers
        foreach ([$teacher1, $teacher2] as $teacher) {
            $grade = $DB->insert_record('assign_grades', [
                'assignment' => $assign->id,
                'userid' => $student->id,
                'grader' => $teacher->id,
                'grade' => 85,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $instance = $DB->insert_record('grading_instances', [
                'definitionid' => $definition,
                'raterid' => $teacher->id,
                'itemid' => $grade,
                'status' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $DB->insert_record('gradingform_utb_eval', [
                'instanceid' => $instance,
                'indicator_id' => $ind_id,
                'performance_level_id' => $lvl_id,
                'score' => 3.0,
                'feedback' => 'Test',
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        // Test filtering by teacher1
        $result = get_evaluations::execute(0, 0, $teacher1->id, 0);
        
        $this->assertEquals(1, $result['count']);
        $this->assertEquals($teacher1->id, $result['evaluations'][0]['grader_id']);
    }

    /**
     * Test get_evaluations filtered by student
     */
    public function test_get_evaluations_filter_by_student() {
        global $DB;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create users
        $teacher = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // Create definition and SO structure
        $definition = $DB->insert_record('grading_definitions', [
            'areaid' => 1,
            'method' => 'utbrubrics',
            'name' => 'Test',
            'status' => 20,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $so_id = $DB->insert_record('gradingform_utb_so', [
            'so_number' => 'SO1',
            'title_en' => 'Test',
            'title_es' => 'Prueba',
            'description_en' => 'Test',
            'description_es' => 'Prueba',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $ind_id = $DB->insert_record('gradingform_utb_ind', [
            'student_outcome_id' => $so_id,
            'indicator_letter' => 'A',
            'description_en' => 'Test',
            'description_es' => 'Prueba',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $lvl_id = $DB->insert_record('gradingform_utb_lvl', [
            'indicator_id' => $ind_id,
            'title_en' => 'Good',
            'title_es' => 'Bueno',
            'description_en' => 'Good',
            'description_es' => 'Bueno',
            'minscore' => 2.8,
            'maxscore' => 3.5,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create evaluations for two different students
        foreach ([$student1, $student2] as $student) {
            $grade = $DB->insert_record('assign_grades', [
                'assignment' => $assign->id,
                'userid' => $student->id,
                'grader' => $teacher->id,
                'grade' => 85,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $instance = $DB->insert_record('grading_instances', [
                'definitionid' => $definition,
                'raterid' => $teacher->id,
                'itemid' => $grade,
                'status' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);

            $DB->insert_record('gradingform_utb_eval', [
                'instanceid' => $instance,
                'indicator_id' => $ind_id,
                'performance_level_id' => $lvl_id,
                'score' => 3.0,
                'feedback' => 'Test',
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        // Test filtering by student1
        $result = get_evaluations::execute(0, 0, 0, $student1->id);
        
        $this->assertEquals(1, $result['count']);
        // Note: We can't directly verify student ID in result as it's not returned,
        // but we verified the count is correct
    }

    /**
     * Test access denied for non-privileged users
     */
    public function test_get_evaluations_access_denied() {
        $this->resetAfterTest(true);

        // Create a regular student user
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);

        // Expect exception
        $this->expectException(\moodle_exception::class);
        get_evaluations::execute(0, 0, 0, 0);
    }

    /**
     * Test parameter validation
     */
    public function test_parameter_validation() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Test valid parameters
        $result = get_evaluations::execute(1, 2, 3, 4);
        $this->assertArrayHasKey('evaluations', $result);
        
        // Test with zero values (should work - means no filter)
        $result = get_evaluations::execute(0, 0, 0, 0);
        $this->assertArrayHasKey('evaluations', $result);
    }
}

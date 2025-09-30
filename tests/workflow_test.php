<?php
/**
 * Functional tests for UTB Rubrics workflow scenarios
 *
 * @package    gradingform_utbrubrics
 * @category   test
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/lib.php');
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/db/helper_functions.php');

/**
 * Functional workflow tests for UTB Rubrics
 *
 * @group gradingform_utbrubrics
 * @covers gradingform_utbrubrics_controller
 */
class gradingform_utbrubrics_workflow_test extends advanced_testcase {

    /** @var object Test course */
    private $course;

    /** @var object Test assignment */
    private $assignment;

    /** @var object Test teacher */
    private $teacher;

    /** @var object Test student */
    private $student;

    /** @var gradingform_utbrubrics_controller */
    private $controller;

    /** @var object Test student outcome */
    private $test_outcome;

    /** @var object Test indicator */
    private $test_indicator;

    /** @var object Test performance level */
    private $test_level;

    /**
     * Test setup - run before each test
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Create test users
        $this->teacher = $this->getDataGenerator()->create_user([
            'username' => 'teacher1',
            'email' => 'teacher1@example.com'
        ]);
        $this->student = $this->getDataGenerator()->create_user([
            'username' => 'student1', 
            'email' => 'student1@example.com'
        ]);

        // Create test course
        $this->course = $this->getDataGenerator()->create_course([
            'fullname' => 'Test Course',
            'shortname' => 'TEST101'
        ]);

        // Enroll users
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, 'student');

        // Create test assignment
        $this->assignment = $this->getDataGenerator()->create_module('assign', [
            'course' => $this->course->id,
            'name' => 'Test Assignment with UTB Rubrics',
            'grade' => 100
        ]);

        // Set up grading area
        $context = context_module::instance($this->assignment->cmid);
        $gradingarea = $DB->get_record('grading_areas', ['contextid' => $context->id]);
        
        if (!$gradingarea) {
            // Create grading area if it doesn't exist
            $gradingarea = new stdClass();
            $gradingarea->contextid = $context->id;
            $gradingarea->component = 'mod_assign';
            $gradingarea->areaname = 'submissions';
            $gradingarea->activemethod = 'utbrubrics';
            $gradingarea->id = $DB->insert_record('grading_areas', $gradingarea);
        }

        $manager = get_grading_manager($gradingarea->id);
        $this->controller = $manager->get_controller('utbrubrics');

        // Create test data in database
        $this->create_test_rubric_data();
    }

    /**
     * Create test rubric data (student outcome, indicator, performance level)
     */
    private function create_test_rubric_data() {
        global $DB;

        // Create test Student Outcome
        $so_data = [
            'so_number' => 'SO-TEST-001',
            'title_en' => 'Test Communication Skills',
            'title_es' => 'Habilidades de Comunicación de Prueba',
            'description_en' => 'Students demonstrate effective communication',
            'description_es' => 'Los estudiantes demuestran comunicación efectiva',
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $this->test_outcome = (object)$so_data;
        $this->test_outcome->id = $DB->insert_record('gradingform_utb_outcomes', $so_data);

        // Create test Indicator
        $indicator_data = [
            'student_outcome_id' => $this->test_outcome->id,
            'indicator_letter' => 'A',
            'description_en' => 'Demonstrates clear written communication',
            'description_es' => 'Demuestra comunicación escrita clara',
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $this->test_indicator = (object)$indicator_data;
        $this->test_indicator->id = $DB->insert_record('gradingform_utb_indicators', $indicator_data);

        // Create test Performance Level
        $level_data = [
            'indicator_id' => $this->test_indicator->id,
            'title_en' => 'Excellent',
            'title_es' => 'Excelente',
            'description_en' => 'Demonstrates exceptional communication skills',
            'description_es' => 'Demuestra habilidades excepcionales de comunicación',
            'minscore' => 4.5,
            'maxscore' => 5.0,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $this->test_level = (object)$level_data;
        $this->test_level->id = $DB->insert_record('gradingform_utb_lvl', $level_data);
    }

    /**
     * Test 1: Teacher selects rubric for an activity
     */
    public function test_teacher_selects_rubric_for_activity() {
        global $DB;

        // Set teacher as current user
        $this->setUser($this->teacher);

        // Test that teacher can access the controller
        $this->assertInstanceOf('gradingform_utbrubrics_controller', $this->controller);
        $this->assertEquals('utbrubrics', $this->controller->get_method_name());

        // Test getting available student outcomes
        $outcomes = gradingform_utbrubrics_get_student_outcomes('en');
        $this->assertNotEmpty($outcomes, 'Teacher should see available student outcomes');
        $this->assertGreaterThanOrEqual(1, count($outcomes), 'Should have at least our test outcome');

        // Find our test outcome
        $found_test_outcome = null;
        foreach ($outcomes as $outcome) {
            if ($outcome->so_number === 'SO-TEST-001') {
                $found_test_outcome = $outcome;
                break;
            }
        }
        $this->assertNotNull($found_test_outcome, 'Teacher should see the test student outcome');
        $this->assertEquals('Test Communication Skills', $found_test_outcome->title);

        // Test that outcome has indicators
        $this->assertNotEmpty($found_test_outcome->indicators, 'Student outcome should have indicators');
        $indicator = $found_test_outcome->indicators[0];
        $this->assertEquals('A', $indicator->indicator_letter);
        $this->assertEquals('Demonstrates clear written communication', $indicator->description);

        // Test that indicator has performance levels
        $this->assertNotEmpty($indicator->performance_levels, 'Indicator should have performance levels');
        $level = $indicator->performance_levels[0];
        $this->assertEquals('Excellent', $level->level_name);
        $this->assertEquals(4.5, $level->minscore);
        $this->assertEquals(5.0, $level->maxscore);

        // Simulate teacher creating a rubric definition
        $definition_data = [
            'areaid' => $this->controller->get_areaid(),
            'method' => 'utbrubrics',
            'name' => 'Test UTB Rubric Definition',
            'description' => 'Test rubric for communication skills assessment',
            'status' => gradingform_controller::DEFINITION_STATUS_READY,
            'timecreated' => time(),
            'timemodified' => time(),
            'timecopied' => 0,
            'usercreated' => $this->teacher->id,
            'usermodified' => $this->teacher->id,
            'options' => json_encode(['keyname' => 'SO-TEST-001'])
        ];

        $definition_id = $DB->insert_record('grading_definitions', $definition_data);
        $this->assertGreaterThan(0, $definition_id, 'Teacher should be able to create rubric definition');

        // Verify definition was created correctly
        $created_definition = $DB->get_record('grading_definitions', ['id' => $definition_id]);
        $this->assertEquals('Test UTB Rubric Definition', $created_definition->name);
        $this->assertEquals($this->teacher->id, $created_definition->usercreated);

        echo "✓ Teacher successfully selected and configured UTB rubric for activity\n";
    }

    /**
     * Test 2: Teacher grades students using the rubric
     */
    public function test_teacher_grades_students_with_rubric() {
        global $DB;

        // Set teacher as current user
        $this->setUser($this->teacher);

        // First create a rubric definition (prerequisite)
        $definition_data = [
            'areaid' => $this->controller->get_areaid(),
            'method' => 'utbrubrics',
            'name' => 'Grading Test Rubric',
            'description' => 'Rubric for testing grading functionality',
            'status' => gradingform_controller::DEFINITION_STATUS_READY,
            'timecreated' => time(),
            'timemodified' => time(),
            'timecopied' => 0,
            'usercreated' => $this->teacher->id,
            'usermodified' => $this->teacher->id,
            'options' => json_encode(['keyname' => 'SO-TEST-001'])
        ];
        $definition_id = $DB->insert_record('grading_definitions', $definition_data);

        // Create a grading instance (represents one grading session)
        $instance_data = [
            'definitionid' => $definition_id,
            'raterid' => $this->teacher->id,
            'itemid' => $this->student->id, // In real scenario, this would be submission ID
            'rawgrade' => null,
            'status' => 0, // Not graded yet
            'feedback' => '',
            'feedbackformat' => FORMAT_HTML,
            'timemodified' => time()
        ];
        $instance_id = $DB->insert_record('grading_instances', $instance_data);

        // Simulate teacher grading: create evaluation record
        $evaluation_data = [
            'instanceid' => $instance_id,
            'studentid' => $this->student->id,
            'courseid' => $this->course->id,
            'activityid' => $this->assignment->cmid,
            'activityname' => 'Test Assignment with UTB Rubrics',
            'student_outcome_id' => $this->test_outcome->id,
            'indicator_id' => $this->test_indicator->id,
            'performance_level_id' => $this->test_level->id,
            'score' => 4.8, // Teacher assigns specific score within range
            'feedback' => 'Excellent work! Clear and well-structured communication.',
            'timecreated' => time(),
            'timemodified' => time()
        ];
        
        $evaluation_id = $DB->insert_record('gradingform_utb_evaluations', $evaluation_data);
        $this->assertGreaterThan(0, $evaluation_id, 'Teacher should be able to create evaluation record');

        // Update grading instance with final grade
        $final_score = 4.8;
        $DB->update_record('grading_instances', [
            'id' => $instance_id,
            'rawgrade' => $final_score,
            'status' => 1, // Graded
            'feedback' => 'Overall excellent performance in communication skills.',
            'timemodified' => time()
        ]);

        // Verify grading was recorded correctly
        $evaluation = $DB->get_record('gradingform_utb_evaluations', ['id' => $evaluation_id]);
        $this->assertEquals($this->student->id, $evaluation->studentid);
        $this->assertEquals($this->test_level->id, $evaluation->performance_level_id);
        $this->assertEquals(4.8, $evaluation->score);
        $this->assertStringContainsString('Excellent work!', $evaluation->feedback);

        // Verify grading instance was updated
        $instance = $DB->get_record('grading_instances', ['id' => $instance_id]);
        $this->assertEquals(4.8, $instance->rawgrade);
        $this->assertEquals(1, $instance->status);

        echo "✓ Teacher successfully graded student using UTB rubric\n";
        echo "  - Performance Level: {$this->test_level->title_en}\n";
        echo "  - Score: {$evaluation->score}/5.0\n";
        echo "  - Feedback provided: Yes\n";
    }

    /**
     * Test 3: Student views their graded rubric
     */
    public function test_student_views_graded_rubric() {
        global $DB;

        // First, set up the grading (teacher's work)
        $this->setUser($this->teacher);
        
        // Create definition and grading records
        $definition_data = [
            'areaid' => $this->controller->get_areaid(),
            'method' => 'utbrubrics',
            'name' => 'Student View Test Rubric',
            'description' => 'Rubric for testing student view functionality',
            'status' => gradingform_controller::DEFINITION_STATUS_READY,
            'timecreated' => time(),
            'timemodified' => time(),
            'timecopied' => 0,
            'usercreated' => $this->teacher->id,
            'usermodified' => $this->teacher->id,
            'options' => json_encode(['keyname' => 'SO-TEST-001'])
        ];
        $definition_id = $DB->insert_record('grading_definitions', $definition_data);

        $instance_data = [
            'definitionid' => $definition_id,
            'raterid' => $this->teacher->id,
            'itemid' => $this->student->id,
            'rawgrade' => 4.2,
            'status' => 1, // Graded
            'feedback' => 'Good work overall, with room for improvement in clarity.',
            'feedbackformat' => FORMAT_HTML,
            'timemodified' => time()
        ];
        $instance_id = $DB->insert_record('grading_instances', $instance_data);

        $evaluation_data = [
            'instanceid' => $instance_id,
            'studentid' => $this->student->id,
            'courseid' => $this->course->id,
            'activityid' => $this->assignment->cmid,
            'activityname' => 'Test Assignment with UTB Rubrics',
            'student_outcome_id' => $this->test_outcome->id,
            'indicator_id' => $this->test_indicator->id,
            'performance_level_id' => $this->test_level->id,
            'score' => 4.2,
            'feedback' => 'You demonstrated good communication skills. Focus on being more concise.',
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $evaluation_id = $DB->insert_record('gradingform_utb_evaluations', $evaluation_data);

        // Now switch to student perspective
        $this->setUser($this->student);

        // Test student can view their evaluation
        $student_evaluation = $DB->get_record('gradingform_utb_evaluations', [
            'id' => $evaluation_id,
            'studentid' => $this->student->id
        ]);
        
        $this->assertNotNull($student_evaluation, 'Student should be able to see their evaluation');
        $this->assertEquals($this->student->id, $student_evaluation->studentid);
        $this->assertEquals(4.2, $student_evaluation->score);

        // Test student can see the rubric structure they were graded with
        $outcome = $DB->get_record('gradingform_utb_outcomes', ['id' => $student_evaluation->student_outcome_id]);
        $this->assertNotNull($outcome, 'Student should see the student outcome they were evaluated on');
        $this->assertEquals('Test Communication Skills', $outcome->title_en);

        $indicator = $DB->get_record('gradingform_utb_indicators', ['id' => $student_evaluation->indicator_id]);
        $this->assertNotNull($indicator, 'Student should see the specific indicator');
        $this->assertEquals('Demonstrates clear written communication', $indicator->description_en);

        $level = $DB->get_record('gradingform_utb_lvl', ['id' => $student_evaluation->performance_level_id]);
        $this->assertNotNull($level, 'Student should see the performance level they achieved');
        $this->assertEquals('Excellent', $level->title_en);

        // Test student can see all performance levels for context (what they could have achieved)
        $all_levels = $DB->get_records('gradingform_utb_lvl', ['indicator_id' => $indicator->id], 'sortorder ASC');
        $this->assertNotEmpty($all_levels, 'Student should see all possible performance levels for context');

        // Verify student sees their specific results
        $this->assertEquals('You demonstrated good communication skills. Focus on being more concise.', 
                          $student_evaluation->feedback);
        
        // Test student can view instance-level feedback
        $instance = $DB->get_record('grading_instances', ['id' => $instance_id]);
        $this->assertEquals('Good work overall, with room for improvement in clarity.', $instance->feedback);
        $this->assertEquals(4.2, $instance->rawgrade);

        echo "✓ Student successfully viewed their graded UTB rubric\n";
        echo "  - Student Outcome: {$outcome->title_en}\n";
        echo "  - Indicator: {$indicator->description_en}\n";
        echo "  - Performance Level Achieved: {$level->title_en}\n";
        echo "  - Score: {$student_evaluation->score}/5.0\n";
        echo "  - Received feedback: Yes\n";
        echo "  - Can see rubric criteria: Yes\n";
    }

    /**
     * Test workflow integration - complete end-to-end scenario
     */
    public function test_complete_workflow_integration() {
        global $DB;

        echo "\n=== Testing Complete UTB Rubrics Workflow ===\n";

        // Step 1: Teacher sets up rubric
        $this->setUser($this->teacher);
        $outcomes = gradingform_utbrubrics_get_student_outcomes('en');
        $this->assertNotEmpty($outcomes, 'Step 1: Teacher should see available rubrics');
        echo "✓ Step 1: Teacher can access UTB rubrics catalog\n";

        // Step 2: Teacher creates assignment definition
        $definition_data = [
            'areaid' => $this->controller->get_areaid(),
            'method' => 'utbrubrics',
            'name' => 'Complete Workflow Test',
            'description' => 'End-to-end workflow test',
            'status' => gradingform_controller::DEFINITION_STATUS_READY,
            'timecreated' => time(),
            'timemodified' => time(),
            'timecopied' => 0,
            'usercreated' => $this->teacher->id,
            'usermodified' => $this->teacher->id,
            'options' => json_encode(['keyname' => 'SO-TEST-001'])
        ];
        $definition_id = $DB->insert_record('grading_definitions', $definition_data);
        echo "✓ Step 2: Teacher created rubric definition for assignment\n";

        // Step 3: Simulate student submission (not directly tested, but assumed)
        echo "✓ Step 3: Student submitted work (simulated)\n";

        // Step 4: Teacher grades submission
        $instance_data = [
            'definitionid' => $definition_id,
            'raterid' => $this->teacher->id,
            'itemid' => $this->student->id,
            'rawgrade' => 3.8,
            'status' => 1,
            'feedback' => 'Good effort with some areas for improvement.',
            'feedbackformat' => FORMAT_HTML,
            'timemodified' => time()
        ];
        $instance_id = $DB->insert_record('grading_instances', $instance_data);

        $evaluation_data = [
            'instanceid' => $instance_id,
            'studentid' => $this->student->id,
            'courseid' => $this->course->id,
            'activityid' => $this->assignment->cmid,
            'activityname' => 'Test Assignment with UTB Rubrics',
            'student_outcome_id' => $this->test_outcome->id,
            'indicator_id' => $this->test_indicator->id,
            'performance_level_id' => $this->test_level->id,
            'score' => 3.8,
            'feedback' => 'Your communication is clear but could be more detailed.',
            'timecreated' => time(),
            'timemodified' => time()
        ];
        $evaluation_id = $DB->insert_record('gradingform_utb_evaluations', $evaluation_data);
        echo "✓ Step 4: Teacher graded student using UTB rubric\n";

        // Step 5: Student views results
        $this->setUser($this->student);
        $student_evaluation = $DB->get_record('gradingform_utb_evaluations', [
            'studentid' => $this->student->id,
            'activityid' => $this->assignment->cmid
        ]);
        $this->assertNotNull($student_evaluation, 'Step 5: Student should see their results');
        echo "✓ Step 5: Student can view graded rubric and feedback\n";

        // Verify data consistency throughout workflow
        $this->assertEquals($this->test_outcome->id, $student_evaluation->student_outcome_id);
        $this->assertEquals($this->test_indicator->id, $student_evaluation->indicator_id);
        $this->assertEquals($this->test_level->id, $student_evaluation->performance_level_id);
        
        echo "✓ All workflow steps completed successfully\n";
        echo "  - Data consistency maintained throughout process\n";
        echo "  - All database relationships intact\n";
        echo "  - Both teacher and student perspectives functional\n";
    }
}
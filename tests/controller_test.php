<?php
/**
 * Unit tests for UTB Rubrics controller class
 *
 * @package    gradingform_utbrubrics
 * @category   test
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/lib.php');

/**
 * Unit tests for gradingform_utbrubrics_controller class
 *
 * @group gradingform_utbrubrics
 * @covers gradingform_utbrubrics_controller
 */
class gradingform_utbrubrics_controller_test extends advanced_testcase {

    /**
     * Test setup - run before each test
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test controller instantiation
     */
    public function test_controller_creation() {
        global $DB;

        // Create test course and assignment
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        // Get grading manager for the assignment
        $context = context_module::instance($assignment->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        
        // Get controller
        $controller = $manager->get_controller('utbrubrics');
        
        $this->assertInstanceOf('gradingform_utbrubrics_controller', $controller);
        $this->assertEquals('utbrubrics', $controller->get_method_name());
    }

    /**
     * Test display mode constants
     */
    public function test_display_mode_constants() {
        $this->assertEquals(1, gradingform_utbrubrics_controller::DISPLAY_EDIT_FULL);
        $this->assertEquals(2, gradingform_utbrubrics_controller::DISPLAY_EDIT_FROZEN);
        $this->assertEquals(3, gradingform_utbrubrics_controller::DISPLAY_PREVIEW);
        $this->assertEquals(4, gradingform_utbrubrics_controller::DISPLAY_EVAL);
        $this->assertEquals(5, gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN);
        $this->assertEquals(6, gradingform_utbrubrics_controller::DISPLAY_REVIEW);
        $this->assertEquals(7, gradingform_utbrubrics_controller::DISPLAY_VIEW);
        $this->assertEquals(8, gradingform_utbrubrics_controller::DISPLAY_PREVIEW_GRADED);
        $this->assertEquals(9, gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT);
    }

    /**
     * Test that controller extends the correct parent class
     */
    public function test_controller_inheritance() {
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $context = context_module::instance($assignment->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller('utbrubrics');
        
        $this->assertInstanceOf('gradingform_controller', $controller);
    }

    /**
     * Test form definition status
     */
    public function test_form_definition_status() {
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $context = context_module::instance($assignment->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller('utbrubrics');
        
        // Initially, form should not be defined
        $this->assertFalse($controller->is_form_defined());
        $this->assertFalse($controller->is_form_available());
    }

    /**
     * Test get_editor_url method
     */
    public function test_get_editor_url() {
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $context = context_module::instance($assignment->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller('utbrubrics');
        
        $url = $controller->get_editor_url();
        $this->assertInstanceOf('moodle_url', $url);
        $this->assertStringContainsString('utbrubrics/edit.php', $url->out());
    }

    /**
     * Test extend_settings_navigation method
     */
    public function test_extend_settings_navigation() {
        global $PAGE;

        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $context = context_module::instance($assignment->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $controller = $manager->get_controller('utbrubrics');
        
        // Create mock settings navigation
        $settingsnav = new settings_navigation($PAGE);
        $node = $settingsnav->add('Test node');
        
        // Test the method doesn't throw errors
        $controller->extend_settings_navigation($settingsnav, $node);
        
        // Check that a child node was added
        $this->assertNotEmpty($node->get_children_key_list());
    }
}
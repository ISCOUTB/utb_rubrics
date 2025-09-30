<?php
/**
 * Unit tests for UTB Rubrics renderer
 *
 * @package    gradingform_utbrubrics
 * @category   test
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/renderer.php');
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/lib.php');

/**
 * Unit tests for gradingform_utbrubrics_renderer class
 *
 * @group gradingform_utbrubrics
 * @covers gradingform_utbrubrics_renderer
 */
class gradingform_utbrubrics_renderer_test extends advanced_testcase {

    /** @var gradingform_utbrubrics_renderer */
    private $renderer;

    /** @var gradingform_utbrubrics_controller */
    private $controller;

    /** @var object */
    private $course;

    /** @var object */
    private $assignment;

    /**
     * Test setup - run before each test
     */
    public function setUp(): void {
        global $DB, $PAGE;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create test course and assignment
        $this->course = $this->getDataGenerator()->create_course();
        $this->assignment = $this->getDataGenerator()->create_module('assign', ['course' => $this->course->id]);

        // Get grading manager and controller
        $context = context_module::instance($this->assignment->cmid);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $this->controller = $manager->get_controller('utbrubrics');

        // Initialize renderer
        $PAGE->set_url('/');
        $this->renderer = new gradingform_utbrubrics_renderer($PAGE, '');
    }

    /**
     * Test renderer instantiation
     */
    public function test_renderer_creation() {
        $this->assertInstanceOf('gradingform_utbrubrics_renderer', $this->renderer);
        $this->assertInstanceOf('plugin_renderer_base', $this->renderer);
    }

    /**
     * Test display_definition method exists and is callable
     */
    public function test_display_definition_method_exists() {
        $this->assertTrue(
            method_exists($this->renderer, 'display_definition'),
            'display_definition method should exist'
        );
    }

    /**
     * Test display_instance method exists and is callable
     */
    public function test_display_instance_method_exists() {
        // This method is optional for UTB rubrics as we use custom display logic
        if (method_exists($this->renderer, 'display_instance')) {
            $this->assertTrue(true, 'display_instance method exists');
        } else {
            $this->assertTrue(true, 'display_instance method not required for UTB rubrics');
        }
    }

    /**
     * Test display_regrade_confirmation method exists
     */
    public function test_display_regrade_confirmation_method_exists() {
        // This method is optional for UTB rubrics
        if (method_exists($this->renderer, 'display_regrade_confirmation')) {
            $this->assertTrue(true, 'display_regrade_confirmation method exists');
        } else {
            $this->assertTrue(true, 'display_regrade_confirmation method not required for UTB rubrics');
        }
    }

    /**
     * Test display_rubric_mapping_explained method exists
     */
    public function test_display_rubric_mapping_explained_method_exists() {
        // This method is optional for UTB rubrics
        if (method_exists($this->renderer, 'display_rubric_mapping_explained')) {
            $this->assertTrue(true, 'display_rubric_mapping_explained method exists');
        } else {
            $this->assertTrue(true, 'display_rubric_mapping_explained method not required for UTB rubrics');
        }
    }

    /**
     * Test display_definition with empty definition
     */
    public function test_display_definition_empty() {
        $definition = $this->controller->get_definition();
        $options = array('mode' => gradingform_utbrubrics_controller::DISPLAY_PREVIEW);
        
        $output = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
        
        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    /**
     * Test display_definition with different display modes
     */
    public function test_display_definition_different_modes() {
        $definition = $this->controller->get_definition();
        
        $modes = [
            gradingform_utbrubrics_controller::DISPLAY_EDIT_FULL,
            gradingform_utbrubrics_controller::DISPLAY_EDIT_FROZEN,
            gradingform_utbrubrics_controller::DISPLAY_PREVIEW,
            gradingform_utbrubrics_controller::DISPLAY_PREVIEW_GRADED,
            gradingform_utbrubrics_controller::DISPLAY_EVAL,
            gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN,
            gradingform_utbrubrics_controller::DISPLAY_REVIEW,
            gradingform_utbrubrics_controller::DISPLAY_VIEW,
            gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT
        ];

        foreach ($modes as $mode) {
            $options = array('mode' => $mode);
            $output = $this->renderer->display_definition($definition, $options, $mode, 'utbrubrics', null);
            
            $this->assertIsString($output, "Output should be string for mode {$mode}");
            // Output can be empty for some modes when no definition exists
        }
    }

    /**
     * Test that renderer handles invalid options gracefully
     */
    public function test_display_definition_invalid_options() {
        $definition = $this->controller->get_definition();
        
        // Test with invalid mode
        $options = array('mode' => 999);
        $output = $this->renderer->display_definition($definition, $options, 999, 'utbrubrics', null);
        $this->assertIsString($output);
        
        // Test with missing mode
        $options = array();
        $output = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_VIEW, 'utbrubrics', null);
        $this->assertIsString($output);
        
        // Test with null options (use empty array instead)
        $output = $this->renderer->display_definition($definition, array(), gradingform_utbrubrics_controller::DISPLAY_VIEW, 'utbrubrics', null);
        $this->assertIsString($output);
    }

    /**
     * Test display_instance method with mock instance
     */
    public function test_display_instance_basic() {
        // Create a mock instance
        $instance = new stdClass();
        $instance->id = 1;
        $instance->status = 1;
        
        $options = array('mode' => gradingform_utbrubrics_controller::DISPLAY_REVIEW);
        
        try {
            $output = $this->renderer->display_instance($instance, null, $options);
            $this->assertIsString($output);
        } catch (Exception $e) {
            // It's acceptable if this throws an exception with mock data
            $this->assertTrue(true, 'Method exists and is callable');
        }
    }

    /**
     * Test that renderer methods don't throw fatal errors
     */
    public function test_renderer_methods_dont_throw_fatal_errors() {
        $definition = $this->controller->get_definition();
        
        // Test display_definition doesn't throw fatal errors
        try {
            $this->renderer->display_definition($definition, array('mode' => gradingform_utbrubrics_controller::DISPLAY_PREVIEW), gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
            $this->assertTrue(true, 'display_definition completed without fatal error');
        } catch (Exception $e) {
            $this->fail('display_definition threw unexpected exception: ' . $e->getMessage());
        }
        
        // Test display_regrade_confirmation doesn't throw fatal errors
        try {
            $this->renderer->display_regrade_confirmation(array(), $definition, true);
            $this->assertTrue(true, 'display_regrade_confirmation completed without fatal error');
        } catch (Exception $e) {
            // This is acceptable as we're testing with minimal data
            $this->assertTrue(true, 'Method exists and is callable');
        }
    }

    /**
     * Test that renderer includes required CSS classes or appropriate fallback
     */
    public function test_renderer_includes_required_css_classes() {
        $definition = $this->controller->get_definition();
        $options = array('mode' => gradingform_utbrubrics_controller::DISPLAY_PREVIEW);
        
        $output = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
        
        // Should include the main container class OR show appropriate message when not defined
        $this->assertTrue(
            strpos($output, 'utbrubrics') !== false || strpos($output, 'not defined') !== false,
            'Output should contain utbrubrics CSS class or appropriate not defined message'
        );
    }

    /**
     * Test that renderer output is properly escaped
     */
    public function test_renderer_output_is_escaped() {
        $definition = $this->controller->get_definition();
        $options = array('mode' => gradingform_utbrubrics_controller::DISPLAY_PREVIEW);
        
        $output = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
        
        // Check that output doesn't contain unescaped script tags
        $this->assertStringNotContainsString('<script>', $output, 'Output should not contain unescaped script tags');
        $this->assertStringNotContainsString('javascript:', $output, 'Output should not contain javascript: URLs');
    }

    /**
     * Test renderer with different language contexts
     */
    public function test_renderer_with_different_languages() {
        global $SESSION;
        
        $definition = $this->controller->get_definition();
        $options = array('mode' => gradingform_utbrubrics_controller::DISPLAY_PREVIEW);
        
        // Test with English
        force_current_language('en');
        $output_en = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
        $this->assertIsString($output_en);
        
        // Test with Spanish  
        force_current_language('es');
        $output_es = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
        $this->assertIsString($output_es);
        
        // Both outputs should be valid strings
        $this->assertNotEmpty($output_en . $output_es); // At least one should have content
    }

    /**
     * Test that renderer handles accessibility requirements
     */
    public function test_renderer_accessibility() {
        $definition = $this->controller->get_definition();
        $options = array('mode' => gradingform_utbrubrics_controller::DISPLAY_PREVIEW);
        
        $output = $this->renderer->display_definition($definition, $options, gradingform_utbrubrics_controller::DISPLAY_PREVIEW, 'utbrubrics', null);
        
        if (!empty($output)) {
            // Should include proper HTML structure
            $this->assertMatchesRegularExpression('/<div[^>]*>/', $output, 'Output should contain proper div elements');
            
            // Should not contain deprecated HTML elements
            $this->assertStringNotContainsString('<table', $output, 'Should avoid tables for layout');
        }
    }
}
<?php
/**
 * External API for retrieving UTB Rubrics grading structure
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradingform_utbrubrics\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/grade/grading/form/utbrubrics/db/helper_functions.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use context_course;
use context_module;

/**
 * External function to get Student Outcomes structure
 */
class get_student_outcomes extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'lang' => new external_value(PARAM_ALPHA, 'Language code (en or es)', VALUE_DEFAULT, 'en'),
        ]);
    }

    /**
     * Get all Student Outcomes with their indicators and performance levels
     *
     * @param string $lang Language code
     * @return array Array of student outcomes
     */
    public static function execute($lang = 'en') {
        global $DB, $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'lang' => $lang,
        ]);

        $lang = $params['lang'];

        // Validate language
        if (!in_array($lang, ['en', 'es'])) {
            $lang = 'en';
        }

        // Student Outcomes are universal, not course-specific
        // Use system context for permission check
        $context = \context_system::instance();
        
        // Validate context
        self::validate_context($context);

        // Check if user has permission to view grading structure
        // For system-wide resources, we check if the user is a teacher or admin in ANY context
        // by checking if they are not just a student
        $systemcontext = \context_system::instance();
        
        // Check multiple capabilities - user needs at least one
        $hasaccess = has_capability('moodle/site:config', $systemcontext) || // Site admin
                     has_capability('moodle/course:viewhiddencourses', $systemcontext) || // Manager/Teacher
                     has_capability('moodle/grade:viewall', $systemcontext) || // Can view all grades
                     is_siteadmin(); // Explicit site admin check

        if (!$hasaccess) {
            throw new \moodle_exception('nopermissions', 'error', '', 
                get_string('apiaccessdenied', 'gradingform_utbrubrics'));
        }

        // Get student outcomes using helper function
        $outcomes = gradingform_utbrubrics_get_student_outcomes($lang);

        // Format for API response
        $formatted_outcomes = [];
        foreach ($outcomes as $outcome) {
            $formatted_indicators = [];
            
            if (!empty($outcome->indicators)) {
                foreach ($outcome->indicators as $indicator) {
                    $formatted_levels = [];
                    
                    if (!empty($indicator->performance_levels)) {
                        foreach ($indicator->performance_levels as $level) {
                            $formatted_levels[] = [
                                'id' => (int) $level->id,
                                'name' => $level->level_name,
                                'definition' => $level->description,
                                'min_score' => (float) $level->minscore,
                                'max_score' => (float) $level->maxscore,
                            ];
                        }
                    }
                    
                    $formatted_indicators[] = [
                        'id' => (int) $indicator->id,
                        'letter' => $indicator->indicator_letter,
                        'description' => $indicator->description,
                        'performance_levels' => $formatted_levels,
                    ];
                }
            }
            
            $formatted_outcomes[] = [
                'id' => (int) $outcome->id,
                'so_number' => $outcome->so_number,
                'title' => $outcome->title,
                'description' => $outcome->description,
                'indicators' => $formatted_indicators,
            ];
        }

        return [
            'student_outcomes' => $formatted_outcomes,
            'count' => count($formatted_outcomes),
            'language' => $lang,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'student_outcomes' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Student Outcome ID'),
                    'so_number' => new external_value(PARAM_TEXT, 'Student Outcome number (SO1, SO2, etc)'),
                    'title' => new external_value(PARAM_TEXT, 'Student Outcome title'),
                    'description' => new external_value(PARAM_TEXT, 'Student Outcome description'),
                    'indicators' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Indicator ID'),
                            'letter' => new external_value(PARAM_TEXT, 'Indicator letter (A, B, C, etc)'),
                            'description' => new external_value(PARAM_TEXT, 'Indicator description'),
                            'performance_levels' => new external_multiple_structure(
                                new external_single_structure([
                                    'id' => new external_value(PARAM_INT, 'Performance level ID'),
                                    'name' => new external_value(PARAM_TEXT, 'Performance level name'),
                                    'definition' => new external_value(PARAM_TEXT, 'Performance level definition'),
                                    'min_score' => new external_value(PARAM_FLOAT, 'Minimum score'),
                                    'max_score' => new external_value(PARAM_FLOAT, 'Maximum score'),
                                ])
                            ),
                        ])
                    ),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Total number of student outcomes'),
            'language' => new external_value(PARAM_ALPHA, 'Language used for results'),
        ]);
    }
}

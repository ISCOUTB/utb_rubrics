<?php
/**
 * External API for retrieving UTB Rubrics evaluations
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
use context_module;
use context_course;

/**
 * External function to get evaluations from UTB Rubrics
 */
class get_evaluations extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID', VALUE_DEFAULT, 0),
            'graderid' => new external_value(PARAM_INT, 'Grader User ID (teacher who graded)', VALUE_DEFAULT, 0),
            'studentid' => new external_value(PARAM_INT, 'Student User ID (student who was graded)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get evaluations from the gradingform_utb_eval table
     *
     * @param int $courseid Course ID (optional)
     * @param int $assignmentid Assignment ID (optional)
     * @param int $graderid Grader User ID (optional) - the teacher who graded
     * @param int $studentid Student User ID (optional) - the student who was graded
     * @return array Array of evaluations
     */
    public static function execute($courseid = 0, $assignmentid = 0, $graderid = 0, $studentid = 0) {
        global $DB, $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'assignmentid' => $assignmentid,
            'graderid' => $graderid,
            'studentid' => $studentid,
        ]);

        $courseid = $params['courseid'];
        $assignmentid = $params['assignmentid'];
        $graderid = $params['graderid'];
        $studentid = $params['studentid'];

        // Use system context for permission checks
        // Evaluations are accessible to any teacher/admin regardless of specific course
        $context = \context_system::instance();

        // Validate context
        self::validate_context($context);

        // Check if user has permission to view grades (teachers and admins)
        // Check at system level - user must be teacher or admin somewhere
        $hasgradeaccess = has_capability('moodle/site:config', $context) || // Site admin
                         has_capability('moodle/course:viewhiddencourses', $context) || // Manager/Teacher
                         has_capability('moodle/grade:viewall', $context) || // Can view all grades
                         is_siteadmin(); // Explicit site admin check

        if (!$hasgradeaccess) {
            throw new \moodle_exception('nopermissions', 'error', '', 
                get_string('apiaccessdenied', 'gradingform_utbrubrics'));
        }

        // Build query based on filters
        $evaluations = self::get_evaluations_data($courseid, $assignmentid, $graderid, $studentid);

        return [
            'evaluations' => $evaluations,
            'count' => count($evaluations),
        ];
    }

    /**
     * Get evaluations data from database
     *
     * @param int $courseid Course ID
     * @param int $assignmentid Assignment ID  
     * @param int $graderid Grader User ID (teacher who graded)
     * @param int $studentid Student User ID (student who was graded)
     * @return array Array of evaluation records
     */
    private static function get_evaluations_data($courseid, $assignmentid, $graderid, $studentid) {
        global $DB;

        // Base query - simplified to get evaluations directly
        $sql = "SELECT 
                    eval.id,
                    eval.instanceid,
                    eval.indicator_id,
                    eval.performance_level_id,
                    eval.score,
                    eval.feedback,
                    eval.courseid,
                    eval.activityname,
                    eval.studentid,
                    eval.timecreated,
                    eval.timemodified,
                    gi.itemid,
                    gi.raterid as grader_id,
                    gd.id as definitionid,
                    gd.name as rubric_name,
                    ind.indicator_letter,
                    ind.description_en as indicator_description_en,
                    ind.description_es as indicator_description_es,
                    ind.student_outcome_id,
                    so.so_number,
                    so.title_en as so_title_en,
                    so.title_es as so_title_es,
                    lvl.title_en as performance_level_name_en,
                    lvl.title_es as performance_level_name_es,
                    lvl.minscore as min_score,
                    lvl.maxscore as max_score,
                    c.fullname as coursename,
                    u.firstname as grader_firstname,
                    u.lastname as grader_lastname,
                    s.firstname as student_firstname,
                    s.lastname as student_lastname
                FROM {gradingform_utb_eval} eval
                INNER JOIN {grading_instances} gi ON eval.instanceid = gi.id
                INNER JOIN {grading_definitions} gd ON gi.definitionid = gd.id
                INNER JOIN {gradingform_utb_ind} ind ON eval.indicator_id = ind.id
                INNER JOIN {gradingform_utb_so} so ON ind.student_outcome_id = so.id
                LEFT JOIN {gradingform_utb_lvl} lvl ON eval.performance_level_id = lvl.id
                LEFT JOIN {course} c ON eval.courseid = c.id
                LEFT JOIN {user} u ON gi.raterid = u.id
                LEFT JOIN {user} s ON eval.studentid = s.id";

        $params = [];
        $where_conditions = [];

        // Apply graderid filter directly
        if ($graderid > 0) {
            $where_conditions[] = "gi.raterid = :graderid";
            $params['graderid'] = $graderid;
        }
        
        // Apply studentid filter - need to join with assign_grades to get the student
        if ($studentid > 0) {
            $where_conditions[] = "gi.itemid IN (
                SELECT ag.id 
                FROM {assign_grades} ag
                WHERE ag.userid = :studentid
            )";
            $params['studentid'] = $studentid;
        }
        
        // For assignment or course filters, add to WHERE conditions
        if ($assignmentid > 0 || $courseid > 0) {
            $subquery = "gi.itemid IN (
                        SELECT ag.id 
                        FROM {assign_grades} ag
                        INNER JOIN {assign} a ON ag.assignment = a.id
                        WHERE 1=1";
            
            if ($assignmentid > 0) {
                $subquery .= " AND a.id = :assignmentid";
                $params['assignmentid'] = $assignmentid;
            }
            
            if ($courseid > 0) {
                $subquery .= " AND a.course = :courseid";
                $params['courseid'] = $courseid;
            }
            
            $subquery .= ")";
            $where_conditions[] = $subquery;
        }
        
        // Add WHERE clause if there are conditions
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }

        $sql .= " ORDER BY eval.timemodified DESC, so.sortorder, ind.indicator_letter";

        // Debug logging only in development mode
        if (debugging('', DEBUG_DEVELOPER)) {
            error_log("UTB API SQL: " . $sql);
            error_log("UTB API Params: " . json_encode($params));
        }

        $records = $DB->get_records_sql($sql, $params);

        // Format results - need to get assignment_id for each record
        $evaluations = [];
        foreach ($records as $record) {
            // Get assignment_id from itemid (which is assign_grades.id)
            $assignment_id = null;
            if ($record->itemid) {
                $assign_grade = $DB->get_record('assign_grades', ['id' => $record->itemid], 'assignment');
                if ($assign_grade) {
                    $assignment_id = (int) $assign_grade->assignment;
                }
            }
            
            // Format grader and student names
            $grader_name = trim($record->grader_firstname . ' ' . $record->grader_lastname);
            $student_name = trim($record->student_firstname . ' ' . $record->student_lastname);
            
            $evaluations[] = [
                'id' => (int) $record->id,
                'instanceid' => (int) $record->instanceid,
                'indicator_id' => (int) $record->indicator_id,
                'indicator_letter' => $record->indicator_letter,
                'indicator_description_en' => $record->indicator_description_en,
                'indicator_description_es' => $record->indicator_description_es,
                'student_outcome_id' => (int) $record->student_outcome_id,
                'so_number' => $record->so_number,
                'so_title_en' => $record->so_title_en,
                'so_title_es' => $record->so_title_es,
                'performance_level_id' => (int) $record->performance_level_id,
                'performance_level_name_en' => $record->performance_level_name_en,
                'performance_level_name_es' => $record->performance_level_name_es,
                'min_score' => $record->min_score !== null ? (float) $record->min_score : null,
                'max_score' => $record->max_score !== null ? (float) $record->max_score : null,
                'score' => $record->score !== null ? (float) $record->score : null,
                'feedback' => $record->feedback,
                'courseid' => (int) $record->courseid,
                'coursename' => $record->coursename,
                'activityname' => $record->activityname,
                'assignment_id' => $assignment_id,
                'student_id' => (int) $record->studentid,
                'student_name' => $student_name,
                'grader_id' => (int) $record->grader_id,
                'grader_name' => $grader_name,
                'rubric_name' => $record->rubric_name,
                'timecreated' => (int) $record->timecreated,
                'timemodified' => (int) $record->timemodified,
            ];
        }

        return $evaluations;
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'evaluations' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Evaluation ID'),
                    'instanceid' => new external_value(PARAM_INT, 'Grading instance ID'),
                    'indicator_id' => new external_value(PARAM_INT, 'Indicator ID'),
                    'indicator_letter' => new external_value(PARAM_TEXT, 'Indicator letter (A, B, C, etc)'),
                    'indicator_description_en' => new external_value(PARAM_TEXT, 'Indicator description in English'),
                    'indicator_description_es' => new external_value(PARAM_TEXT, 'Indicator description in Spanish'),
                    'student_outcome_id' => new external_value(PARAM_INT, 'Student Outcome ID'),
                    'so_number' => new external_value(PARAM_TEXT, 'Student Outcome number (SO1, SO2, etc)'),
                    'so_title_en' => new external_value(PARAM_TEXT, 'Student Outcome title in English'),
                    'so_title_es' => new external_value(PARAM_TEXT, 'Student Outcome title in Spanish'),
                    'performance_level_id' => new external_value(PARAM_INT, 'Performance level ID'),
                    'performance_level_name_en' => new external_value(PARAM_TEXT, 'Performance level name in English', VALUE_OPTIONAL),
                    'performance_level_name_es' => new external_value(PARAM_TEXT, 'Performance level name in Spanish', VALUE_OPTIONAL),
                    'min_score' => new external_value(PARAM_FLOAT, 'Minimum score for the level', VALUE_OPTIONAL),
                    'max_score' => new external_value(PARAM_FLOAT, 'Maximum score for the level', VALUE_OPTIONAL),
                    'score' => new external_value(PARAM_FLOAT, 'Score given to the student', VALUE_OPTIONAL),
                    'feedback' => new external_value(PARAM_RAW, 'Feedback text', VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                    'activityname' => new external_value(PARAM_TEXT, 'Activity name'),
                    'assignment_id' => new external_value(PARAM_INT, 'Assignment ID'),
                    'student_id' => new external_value(PARAM_INT, 'ID of the student who was graded'),
                    'student_name' => new external_value(PARAM_TEXT, 'Full name of the student'),
                    'grader_id' => new external_value(PARAM_INT, 'ID of user who graded'),
                    'grader_name' => new external_value(PARAM_TEXT, 'Full name of the grader (teacher)'),
                    'rubric_name' => new external_value(PARAM_TEXT, 'Name of the rubric'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Total number of evaluations returned'),
        ]);
    }
}

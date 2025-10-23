<?php
/**
 * Web service definitions for UTB Rubrics
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Get evaluations from the grading table
    'gradingform_utbrubrics_get_evaluations' => [
        'classname'   => 'gradingform_utbrubrics\external\get_evaluations',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get evaluation data from UTB Rubrics grading table. Restricted to teachers and administrators.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade, moodle/grade:viewall',
        'services'    => ['utbrubrics_ws'],
    ],
    
    // Get Student Outcomes structure
    'gradingform_utbrubrics_get_student_outcomes' => [
        'classname'   => 'gradingform_utbrubrics\external\get_student_outcomes',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get Student Outcomes structure with indicators and performance levels. Restricted to teachers and administrators.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade, moodle/grade:viewall',
        'services'    => ['utbrubrics_ws'],
    ],
];

// Define the services
$services = [
    'UTB Rubrics Web Service' => [
        'functions' => [
            'gradingform_utbrubrics_get_evaluations',
            'gradingform_utbrubrics_get_student_outcomes',
        ],
        'requiredcapability' => 'mod/assign:grade',
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'utbrubrics_ws',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];

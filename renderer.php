<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/form/utbrubrics/db/helper_functions.php');

/**
 * Renderer for UTB Rubrics grading method - DATABASE STRUCTURE
 *
 * DATA FLOW DOCUMENTATION:
 * 
 * The $value parameter throughout this renderer contains grading data with this structure:
 * 
 * 1. SOURCE: gradingform_utbrubrics_controller->render_grading_element()
 *    - Gets data from gradingform_instance->get_utbrubrics_filling()
 *    - Data comes from 'gradingform_utb_evaluations' database table
 *    - Falls back to form submission data or empty array
 *
 * 2. STRUCTURE:
 *    $value = [
 *        'criteria' => [
 *            indicator_id => [
 *                'id' => evaluation_record_id,               // from gradingform_utb_evaluations.id
 *                'instanceid' => grading_instance_id,        // from gradingform_utb_evaluations.instanceid
 *                'criterionid' => indicator_id,              // backwards compatibility
 *                'indicator_id' => indicator_id,             // from gradingform_utb_evaluations.indicator_id
 *                'student_outcome_id' => so_id,              // from gradingform_utb_evaluations.student_outcome_id
 *                'performance_level_id' => level_id,         // selected performance level
 *                'score' => numeric_score,                   // final score given
 *                'feedback' => feedback_text,                // teacher feedback
 *                'so_title' => 'SO1: Engineering Problem...',// Student Outcome title
 *                'indicator_description' => '...',          // Indicator description
 *                'performance_level_name' => 'Excellent',   // Level name
 *                'minscore' => 4.5,                         // Min score for level
 *                'maxscore' => 5.0                          // Max score for level
 *            ]
 *        ]
 *    ]
 * 
 * 3. STATES:
 *    - Ungraded: $value = [] or $value = ['criteria' => []]
 *    - Partially graded: Some indicators have 'performance_level_id', others don't
 *    - Fully graded: All indicators have 'performance_level_id' values
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_utbrubrics_renderer extends plugin_renderer_base {

    /**
     * Helper function to determine level type class based on definition text
     * @param string $definition The level definition text
     * @return string CSS class for the level type
     */
    private function get_level_type_class($definition) {
        if (strpos($definition, 'Excellent') !== false || strpos($definition, 'Excelente') !== false) {
            return 'level-excellent';
        } elseif (strpos($definition, 'Good') !== false || strpos($definition, 'Bueno') !== false) {
            return 'level-good';
        } elseif (strpos($definition, 'Fair') !== false || strpos($definition, 'Regular') !== false) {
            return 'level-fair';
        } elseif (strpos($definition, 'Inadequate') !== false || strpos($definition, 'Inadecuado') !== false) {
            return 'level-inadequate';
        }
        return '';
    }

    // Función get_string_with_fallback removida - usando get_string directamente

    /**
     * Display the UTB rubric definition with NEW database structure support.
     * 
     * @param array $rubric Student Outcomes structure from new database
     * @param array $options Plugin options
     * @param int $mode Display mode (controller constants)
     * @param string $elementname Form element name prefix
     * @param array|null $value Current evaluation data with NEW structure
     * @return string HTML output
     */
    public function display_definition($rubric, array $options, int $mode, string $elementname, array $value = null): string {
        if (empty($rubric) || !is_array($rubric)) {
            return html_writer::div(
                get_string('rubricnotdefined', 'gradingform_utbrubrics'), 
                'alert alert-warning'
            );
        }

        $out = html_writer::start_div('gradingform_utbrubrics');
        
        // Add comprehensive CSS for better styling
        $out .= html_writer::tag('style', '
            .gradingform_utbrubrics { margin: 20px 0; }
            .gradingform_utbrubrics .rubric-header {
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            .gradingform_utbrubrics .rubric-content {
                padding: 25px;
            }
            .gradingform_utbrubrics .student-outcome-section {
                margin-bottom: 30px;
            }
            .gradingform_utbrubrics .so-header-container {
                background: linear-gradient(135deg, #05a0a0 0%, #037f7f 100%);
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                margin-bottom: 20px;
                overflow: hidden;
                position: relative;
            }
            .gradingform_utbrubrics .so-header-container::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #06b6b6, #05a0a0, #037f7f);
            }
            .gradingform_utbrubrics .so-header {
                color: white;
                padding: 25px 20px 15px 20px;
                text-align: center;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                font-weight: 300;
                font-size: 1.6em;
                margin: 0;
                background: transparent;
                letter-spacing: 0.5px;
            }
            .gradingform_utbrubrics .so-description {
                color: white;
                padding: 0 25px 25px 25px;
                text-align: center;
                opacity: 0.95;
                font-size: 1.1em;
                margin: 0;
                background: transparent;
                line-height: 1.5;
                font-weight: 400;
            }
            .gradingform_utbrubrics .rubric-table { 
                margin-bottom: 20px; 
                box-shadow: 0 2px 15px rgba(0,0,0,0.08);
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e3f2fd;
                width: 100%;
                min-width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                background: white;
            }
            .gradingform_utbrubrics .rubric-table th,
            .gradingform_utbrubrics .rubric-table td {
                border: 1px solid #e9ecef;
                border-top: none;
                border-left: none;
                padding: 15px 12px;
            }
            .gradingform_utbrubrics .rubric-table th:first-child,
            .gradingform_utbrubrics .rubric-table td:first-child {
                border-left: none;
            }
            .gradingform_utbrubrics .rubric-table tr:last-child th,
            .gradingform_utbrubrics .rubric-table tr:last-child td {
                border-bottom: none;
            }
            .gradingform_utbrubrics .rubric-table th {
                background-color: #05a0a0;
                color: white;
                font-weight: 500;
                text-align: center;
                padding: 18px 15px;
                text-shadow: 0 1px 3px rgba(0,0,0,0.2);
                font-size: 0.95em;
                letter-spacing: 0.3px;
            }
            .gradingform_utbrubrics .rubric-table td {
                background: #fafafa;
                vertical-align: top;
            }
            .gradingform_utbrubrics .rubric-table tr:nth-child(even) td {
                background: #f5f5f5;
            }
            .gradingform_utbrubrics .rubric-table tr:hover td {
                background: #e8f5e8;
                transition: background-color 0.2s ease;
            }
            .gradingform_utbrubrics .criterion-cell {
                font-weight: 500;
                color: #2c3e50;
                background: white !important;
                border-right: 1px solid #e9ecef !important;
            }
            .gradingform_utbrubrics .criterion-cell:hover {
                background: #f8f9fa !important;
            }
            
            /* Mobile horizontal scroll container */
            .gradingform_utbrubrics .table-responsive-mobile {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
            }
            
            /* Mobile table styling for horizontal scroll */
            @media (max-width: 768px) {
                .gradingform_utbrubrics .table-responsive-mobile {
                    border: 2px solid #05a0a0;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    position: relative;
                }
                
                /* Visual indicator for scrollable content */
                .gradingform_utbrubrics .table-responsive-mobile::after {
                    content: "👈 Swipe to see more";
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: rgba(33, 150, 243, 0.9);
                    color: white;
                    padding: 4px 8px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: 500;
                    z-index: 10;
                    pointer-events: none;
                    animation: fadeInOut 3s ease-in-out;
                }
                
                @keyframes fadeInOut {
                    0%, 100% { opacity: 0; }
                    20%, 80% { opacity: 1; }
                }
                
                .gradingform_utbrubrics .rubric-table {
                    margin-bottom: 0;
                    border: none;
                    border-radius: 0;
                    min-width: 650px; /* Reduced width since performance levels are now vertical */
                }
                .gradingform_utbrubrics .rubric-table th,
                .gradingform_utbrubrics .rubric-table td {
                    white-space: normal;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    min-width: 120px;
                    vertical-align: top;
                }
                .gradingform_utbrubrics .criterion-cell {
                    min-width: 200px;
                    max-width: 250px;
                    white-space: normal;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    padding: 10px;
                }
                .gradingform_utbrubrics .levels-cell {
                    min-width: 280px; /* Reduced width since levels are stacked vertically */
                    max-width: 320px;
                }
                .gradingform_utbrubrics .levels-cell .performance-levels {
                    min-width: 280px; /* Reduced to accommodate vertical stacking */
                }
                .gradingform_utbrubrics .grade-cell {
                    min-width: 150px;
                    max-width: 180px;
                }
                
                /* Performance levels in mobile scroll - FORCE single column only */
                .gradingform_utbrubrics .performance-levels .level-option,
                .gradingform_utbrubrics .performance-levels.levels-1 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-2 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-3 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-4 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-5 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-6 .level-option {
                    flex: 0 0 calc(100% - 4px) !important;
                    width: calc(100% - 4px) !important;
                    min-width: auto !important;
                    max-width: none !important;
                    margin-bottom: 8px;
                }
                
                /* Fix card content overflow in mobile */
                .gradingform_utbrubrics .level-option .card {
                    width: 100% !important;
                    margin: 0 !important;
                    overflow: hidden;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
                
                .gradingform_utbrubrics .level-option .card-body {
                    padding: 8px 12px !important;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    overflow: hidden;
                }
                
                .gradingform_utbrubrics .level-name {
                    font-size: 0.9em !important;
                    line-height: 1.3 !important;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    white-space: normal !important;
                }
                
                .gradingform_utbrubrics .level-description {
                    font-size: 0.8em !important;
                    line-height: 1.2 !important;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    white-space: normal !important;
                }
                
                .gradingform_utbrubrics .criterion-description {
                    font-size: 0.9em !important;
                    line-height: 1.3 !important;
                    padding: 8px !important;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    white-space: normal !important;
                }
            }
            .gradingform_utbrubrics .criterion-row {
                border-bottom: 1px solid #e9ecef;
            }
            .gradingform_utbrubrics .criterion-row:last-child {
                border-bottom: none;
            }
            .gradingform_utbrubrics .performance-levels { 
                display: flex; 
                flex-wrap: wrap; 
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .gradingform_utbrubrics .level-option { 
                padding: 2px; /* Minimal padding for visual separation */
                box-sizing: border-box;
            }
            
            /* Adaptive distribution - prioritizes readability over fitting all in one row */
            
            /* Default: Flexible distribution with optimal readability */
            .gradingform_utbrubrics .performance-levels .level-option { 
                flex: 0 0 calc(50% - 4px); /* Default: 2 per row for better readability */
                width: calc(50% - 4px);
                min-width: 280px; /* Minimum width for comfortable reading */
            }
            
            /* 1 level: Full width */
            .gradingform_utbrubrics .performance-levels.levels-1 .level-option { 
                flex: 0 0 calc(100% - 4px);
                width: calc(100% - 4px);
                min-width: auto;
            }
            
            /* 2 levels: 2 per row (optimal) */
            .gradingform_utbrubrics .performance-levels.levels-2 .level-option { 
                flex: 0 0 calc(50% - 4px);
                width: calc(50% - 4px);
                min-width: auto;
            }
            
            /* 3-4 levels: Adaptive based on available space */
            .gradingform_utbrubrics .performance-levels.levels-3 .level-option,
            .gradingform_utbrubrics .performance-levels.levels-4 .level-option { 
                flex: 0 0 calc(50% - 4px); /* 2 per row by default for readability */
                width: calc(50% - 4px);
                min-width: 280px;
            }
            
            /* 5-6 levels: Adaptive, prioritize readability */
            .gradingform_utbrubrics .performance-levels.levels-5 .level-option,
            .gradingform_utbrubrics .performance-levels.levels-6 .level-option { 
                flex: 0 0 calc(33.333333% - 4px); /* 3 per row max for readability */
                width: calc(33.333333% - 4px);
                min-width: 250px;
            }
            
            /* Adaptive responsive behavior - Smooth transition by number */
            
            /* Extra Wide screens - Maximum distribution */  
            @media (min-width: 1600px) {
                .gradingform_utbrubrics .performance-levels.levels-4 .level-option { 
                    flex: 0 0 calc(25% - 4px);
                    width: calc(25% - 4px);
                    min-width: auto;
                }
                .gradingform_utbrubrics .performance-levels.levels-3 .level-option { 
                    flex: 0 0 calc(33.333333% - 4px);
                    width: calc(33.333333% - 4px);
                    min-width: auto;
                }
            }
            
            /* Wide screens - 4 to 3 transition */
            @media (max-width: 1599px) and (min-width: 1300px) {
                .gradingform_utbrubrics .performance-levels.levels-4 .level-option { 
                    flex: 0 0 calc(33.333333% - 4px);
                    width: calc(33.333333% - 4px);
                }  
                .gradingform_utbrubrics .performance-levels.levels-3 .level-option { 
                    flex: 0 0 calc(33.333333% - 4px);
                    width: calc(33.333333% - 4px);
                }
            }
            
            /* Large screens - 4 and 3 to 2 transition */
            @media (max-width: 1299px) and (min-width: 1000px) {
                .gradingform_utbrubrics .performance-levels.levels-4 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-3 .level-option { 
                    flex: 0 0 calc(50% - 4px);
                    width: calc(50% - 4px);
                }
                .gradingform_utbrubrics .performance-levels.levels-5 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-6 .level-option { 
                    flex: 0 0 calc(33.333333% - 4px);
                    width: calc(33.333333% - 4px);
                }
            }
            
            /* Medium screens - Progressive reduction */
            @media (max-width: 999px) and (min-width: 800px) {
                .gradingform_utbrubrics .performance-levels.levels-4 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-3 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-5 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-6 .level-option { 
                    flex: 0 0 calc(50% - 4px);
                    width: calc(50% - 4px);
                }
            }
            
            /* Small-Medium screens - Most become 2 per row */
            @media (max-width: 799px) and (min-width: 650px) {
                .gradingform_utbrubrics .performance-levels.levels-3 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-4 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-5 .level-option,
                .gradingform_utbrubrics .performance-levels.levels-6 .level-option { 
                    flex: 0 0 calc(50% - 4px);
                    width: calc(50% - 4px);
                }
            }
            
            /* Tablet screens - Final transition to single column */
            @media (max-width: 649px) {
                .gradingform_utbrubrics .performance-levels .level-option { 
                    flex: 0 0 calc(100% - 4px);
                    width: calc(100% - 4px);
                    min-width: auto;
                }
            }
            .gradingform_utbrubrics .level-option { 
                cursor: pointer; 
            }
            .gradingform_utbrubrics .level-option .card { 
                cursor: pointer; 
                transition: all 0.3s ease; 
                border-width: 1px;
                height: 100%;
                min-height: 140px;
                border-color: #dee2e6 !important;
                background-color: #ffffff !important;
                width: 100%;
                margin: 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                border-radius: 8px !important;
            }
            .gradingform_utbrubrics .level-option .card:hover { 
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
                border-color: #05a0a0 !important;
            }
            .gradingform_utbrubrics .level-option.selected .card { 
                background-color: #e3f2fd; 
                border-width: 2px;
                border-color: #05a0a0 !important;
                transform: translateY(-1px);
                box-shadow: 0 3px 6px rgba(5,160,160,0.2) !important;
            }
            .gradingform_utbrubrics .level-option .level-radio { display: none; }
            .gradingform_utbrubrics .criterion-description { 
                color: #2c3e50; 
                font-size: 1.1em; 
                line-height: 1.4;
                padding: 15px;
                margin-bottom: 5px;
            }
            .gradingform_utbrubrics .grade-input { 
                width: 120px; 
                text-align: center; 
                font-weight: bold;
                font-size: 1.1em;
                padding: 10px;
                border: 2px solid #ddd;
                border-radius: 4px;
            }
            .gradingform_utbrubrics .grade-input-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 100%;
            }
            .gradingform_utbrubrics .score-input {
                height: 44px;
                line-height: 1.4;
                font-weight: 600;
                text-align: center;
                width: 80%;
                max-width: 150px;
                margin: 0 auto;
                display: block;
                border-width: 2px;
                border-radius: 8px;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }
            .gradingform_utbrubrics .score-input:focus {
                border-color: #2196F3;
                box-shadow: 0 0 0 0.2rem rgba(33,150,243,0.25);
            }
            .gradingform_utbrubrics .score-input:disabled {
                background-color: #f1f3f5;
                color: #6c757d;
                opacity: 1;
                max-width: 150px;
                cursor: not-allowed;
            }
            .gradingform_utbrubrics .score-input.is-invalid {
                border-color: #dc3545;
                box-shadow: 0 0 0 0.15rem rgba(220,53,69,0.15);
            }
            .gradingform_utbrubrics .score-input.is-valid {
                border-color: #198754;
                box-shadow: 0 0 0 0.15rem rgba(25,135,84,0.15);
            }
            .gradingform_utbrubrics .grade-input:focus {
                border-color: #2196F3;
                box-shadow: 0 0 0 0.2rem rgba(33,150,243,0.25);
            }
            .gradingform_utbrubrics .grade-input.is-invalid {
                border-color: #dc3545;
                box-shadow: 0 0 0 0.2rem rgba(220,53,69,0.25);
            }
            .gradingform_utbrubrics .grade-range { 
                font-size: 0.9em; 
                color: #666;
                margin-top: 5px;
            }
            .gradingform_utbrubrics .badge {
                font-weight: 600;
                padding: 6px 12px;
            }
            .gradingform_utbrubrics .level-name { 
                color: #2c3e50; 
                font-size: 1em; 
                font-weight: bold;
                margin-bottom: 8px;
                text-shadow: none !important;
                background: none !important;
                border: none !important;
                box-shadow: none !important;
            }
            /* Keep text effects clean but allow card styling */
            .gradingform_utbrubrics .level-name,
            .gradingform_utbrubrics .level-description {
                text-shadow: none !important;
            }
            
            .gradingform_utbrubrics .level-description { 
                line-height: 1.4; 
                margin-top: 8px;
                font-size: 0.9em;
                color: #555;
                text-align: left;
                text-shadow: none !important;
                background: none !important;
                border: none !important;
                box-shadow: none !important;
            }
            .gradingform_utbrubrics .rubric-description { 
                background-color: #e8f4fd; 
                padding: 15px; 
                border-left: 4px solid #2196F3; 
                margin-bottom: 20px;
                border-radius: 0 6px 6px 0;
                font-style: italic;
            }
            .gradingform_utbrubrics .criterion-cell { 
                width: 28%; 
                vertical-align: top;
                padding: 15px;
            }
            .gradingform_utbrubrics .levels-cell { 
                width: 57%; 
                padding: 15px;
            }
            .gradingform_utbrubrics .grade-cell { 
                width: 15%; 
                text-align: center;
                vertical-align: middle;
                padding: 15px;
            }
            .cursor-pointer { cursor: pointer; }
            
            /* Radio button styling for better interaction */
            .gradingform_utbrubrics .level-radio { 
                position: absolute;
                top: 10px;
                left: 10px;
                z-index: 10;
                transform: scale(1.2);
                opacity: 0.8;
            }
            .gradingform_utbrubrics .level-option { 
                position: relative; 
                cursor: pointer;
            }
            .gradingform_utbrubrics .level-option label.level-card-label {
                display: block;
                height: 100%;
                margin: 0;
                cursor: pointer;
            }
            .gradingform_utbrubrics .visually-hidden {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                border: 0;
            }
            .gradingform_utbrubrics .level-option:hover .level-radio {
                opacity: 1;
            }
            
            /* Level-specific styling - only applied when selected */
            .gradingform_utbrubrics .level-option.selected.level-excellent .card { 
                background-color: #d4edda !important; 
                border-color: #28a745 !important;
                border-width: 2px !important;
                box-shadow: 0 3px 6px rgba(40,167,69,0.25) !important;
                transform: translateY(-1px) !important;
            }
            .gradingform_utbrubrics .level-option.selected.level-good .card { 
                background-color: #cce7ff !important; 
                border-color: #007bff !important;
                border-width: 2px !important;
                box-shadow: 0 3px 6px rgba(0,123,255,0.25) !important;
                transform: translateY(-1px) !important;
            }
            .gradingform_utbrubrics .level-option.selected.level-fair .card { 
                background-color: #fff3cd !important; 
                border-color: #ffc107 !important;
                border-width: 2px !important;
                box-shadow: 0 3px 6px rgba(255,193,7,0.25) !important;
                transform: translateY(-1px) !important;
            }
            .gradingform_utbrubrics .level-option.selected.level-inadequate .card { 
                background-color: #f8d7da !important; 
                border-color: #dc3545 !important;
                border-width: 2px !important;
                box-shadow: 0 3px 6px rgba(220,53,69,0.25) !important;
                transform: translateY(-1px) !important;
            }
            
            /* Student view specific styles */
            .gradingform_utbrubrics .student-grade-result {
                text-align: center;
                padding: 15px;
                border-radius: 8px;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
            }
            
            .gradingform_utbrubrics .achieved-level .badge {
                font-size: 14px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .gradingform_utbrubrics .achieved-score {
                font-size: 16px;
                color: #495057;
            }
            
            .gradingform_utbrubrics .feedback-text {
                text-align: left;
                font-style: italic;
                max-height: 100px;
                overflow-y: auto;
            }
            
            .gradingform_utbrubrics .not-graded-indicator {
                padding: 20px;
                text-align: center;
                background: #f8f9fa;
                border: 2px dashed #dee2e6;
                border-radius: 8px;
                color: #6c757d;
            }
            
            .gradingform_utbrubrics .level-excellent-student { border-color: #28a745; background-color: #d4edda; }
            .gradingform_utbrubrics .level-good-student { border-color: #007bff; background-color: #cce7ff; }
            .gradingform_utbrubrics .level-fair-student { border-color: #ffc107; background-color: #fff3cd; }
            .gradingform_utbrubrics .level-inadequate-student { border-color: #dc3545; background-color: #f8d7da; }
            
            /* Student performance levels info styles */
            .gradingform_utbrubrics .student-performance-levels {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                padding: 10px;
            }
            
            .gradingform_utbrubrics .level-info-card {
                transition: all 0.3s ease;
                background: #f8f9fa;
            }
            
            .gradingform_utbrubrics .level-excellent-info { border-left: 4px solid #28a745; }
            .gradingform_utbrubrics .level-good-info { border-left: 4px solid #007bff; }
            .gradingform_utbrubrics .level-fair-info { border-left: 4px solid #ffc107; }
            .gradingform_utbrubrics .level-inadequate-info { border-left: 4px solid #dc3545; }
            
            .gradingform_utbrubrics .selected-level {
                background: rgba(40, 167, 69, 0.1) !important;
                border: 2px solid #28a745 !important;
            }
            .gradingform_utbrubrics a[data-region="popout-button"],
            .gradingform_utbrubrics a[data-region="popout-button"] i {
                display: none !important;
            }
            
            .gradingform_utbrubrics .level-range {
                font-weight: 600;
                color: #495057;
            }
            
            .gradingform_utbrubrics .level-description {
                line-height: 1.4;
                color: #6c757d;
            }
            
            .gradingform_utbrubrics .selected-indicator {
                font-size: 14px;
                color: #28a745 !important;
            }
            
            /* Additional responsive adjustments */
            @media (max-width: 768px) {
                .gradingform_utbrubrics .rubric-header h2 {
                    font-size: 1.8em !important;
                }
                .gradingform_utbrubrics .rubric-header p {
                    font-size: 1em !important;
                }
            }
        ');

        // Display UTB rubric with NEW single Student Outcome structure
        if (!empty($rubric) && isset($rubric['criteria'])) {
            // Add warning message for evaluator mode
            $isEvaluatorMode = ($mode == gradingform_utbrubrics_controller::DISPLAY_EVAL || 
                              $mode == gradingform_utbrubrics_controller::DISPLAY_EDIT_FULL ||
                              $mode == gradingform_utbrubrics_controller::DISPLAY_EDIT_FROZEN);
            
            if ($isEvaluatorMode) {
                $out .= html_writer::div(
                    get_string('evaluator_warning', 'gradingform_utbrubrics'),
                    'alert alert-info mt-3',
                    ['role' => 'alert']
                );
            }
            
            // Render the single Student Outcome
            $out .= $this->render_single_student_outcome($rubric, $options, $mode, $elementname, $value);
        } else {
            // No rubric defined or invalid structure
            $out .= html_writer::div(
                get_string('rubricnotdefined', 'gradingform_utbrubrics'),
                'alert alert-warning mt-3'
            );
        }

        $out .= html_writer::end_div(); // Close gradingform_utbrubrics
        
        return $out;
    }
    
    /**
     * Render a single Student Outcome with its indicators
     * Updated for new database structure
     */
    /**
     * Render a single student outcome with its indicators and performance levels.
     * 
     * @param array $rubric Student outcome data structure
     * @param array $options Display options
     * @param int $mode Display mode (view, edit, etc.)
     * @param string $elementname Form element name prefix
     * @param array|null $value Current evaluation data
     * @return string HTML output for the student outcome
     */
    private function render_single_student_outcome($rubric, $options, $mode, $elementname, $value) {
        $out = '';
        $hasgradedcriteria = $this->has_graded_criteria($value);
        $studentsubmitted = $this->has_student_submitted($mode, $value);
        $shouldshowgrade = $this->should_show_grade_column($mode, $value, $hasgradedcriteria);
        $isstudentresult = ($mode == gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT);
        $ispreviewmode = ($mode == gradingform_utbrubrics_controller::DISPLAY_PREVIEW ||
                $mode == gradingform_utbrubrics_controller::DISPLAY_PREVIEW_GRADED);
        
        // Extract SO number from keyname for professional title
        $keyname = $rubric['keyname'] ?? '';
        $so_number = preg_replace('/[^0-9]/', '', $keyname);
        $professional_title = !empty($so_number) ? 
            'Student Outcome ' . $so_number . ' (' . strtoupper($keyname) . ')' : 
            ($rubric['title'] ?? 'Student Outcome');
        
        // Student Outcome section
        $out .= html_writer::start_div('student-outcome-section');

        // Display contextual alerts before the header block
        $alertcontent = '';
        $alertclasses = '';

        if ($isstudentresult && $hasgradedcriteria) {
            $alertcontent = html_writer::tag('h5', get_string('yourgradingresults', 'gradingform_utbrubrics'), ['class' => 'mb-1']) .
                            html_writer::tag('p', get_string('resultsexplanation', 'gradingform_utbrubrics'), ['class' => 'mb-0']);
            $alertclasses = 'alert alert-success text-center mb-4';
        } else if ($isstudentresult && !$hasgradedcriteria) {
            if ($studentsubmitted === false) {
                $alerttitle = get_string('awaitingsubmission', 'gradingform_utbrubrics');
                $alertmessage = get_string('awaitingsubmissionexplanation', 'gradingform_utbrubrics');
            } else {
                $alerttitle = get_string('notyetgraded', 'gradingform_utbrubrics');
                $alertmessage = get_string('notgradedexplanation', 'gradingform_utbrubrics');
            }

            $alertcontent = html_writer::tag('h5', $alerttitle, ['class' => 'mb-1']) .
                            html_writer::tag('p', $alertmessage, ['class' => 'mb-0']);
            $alertclasses = 'alert alert-warning text-center mb-4';
        } else if ($ispreviewmode) {
            $alertcontent = html_writer::tag('h5', get_string('rubric_preview', 'gradingform_utbrubrics'), ['class' => 'mb-1']) .
                            html_writer::tag('p', get_string('rubric_preview_description', 'gradingform_utbrubrics'), ['class' => 'mb-0']);
            $alertclasses = 'alert alert-secondary text-center mb-4';
        }

        if (!empty($alertcontent)) {
            $out .= html_writer::div($alertcontent, $alertclasses);
        }

        // Header container that includes both title and description
        $out .= html_writer::start_div('so-header-container');
        
        // SO Header with professional title
        $out .= html_writer::div(
            $professional_title,
            'so-header'
        );
        
        // SO Description
        if (!empty($rubric['description'])) {
            $out .= html_writer::div(
                $rubric['description'],
                'so-description'
            );
        }
        
        $out .= html_writer::end_div(); // Close so-header-container
        
        // Indicators table
        $out .= html_writer::start_div('table-responsive-mobile');
        $out .= html_writer::start_tag('table', ['class' => 'rubric-table table']);
        
        // Table header
        $out .= html_writer::start_tag('thead');
        $out .= html_writer::start_tag('tr');
        $out .= html_writer::tag('th', get_string('performanceindicator', 'gradingform_utbrubrics'), ['class' => 'criterion-header']);
        $out .= html_writer::tag('th', get_string('performancelevelsdescriptions', 'gradingform_utbrubrics'), ['class' => 'levels-header']);

        if ($shouldshowgrade) {
            if ($this->is_grading_mode($mode)) {
                $gradeheader = get_string('gradeselectlevel', 'gradingform_utbrubrics');
            } else if ($mode == gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT) {
                $gradeheader = get_string('yourgradefeedback', 'gradingform_utbrubrics');
            } else {
                $gradeheader = get_string('recordedgradefeedback', 'gradingform_utbrubrics');
            }

            $out .= html_writer::tag('th', $gradeheader, ['class' => 'grade-header']);
        }
        $out .= html_writer::end_tag('tr');
        $out .= html_writer::end_tag('thead');
        
        // Table body - one row per indicator
        $out .= html_writer::start_tag('tbody');
        foreach ($rubric['criteria'] as $indicator) {
            $out .= $this->render_indicator_row($indicator, $rubric, $options, $mode, $elementname, $value, $shouldshowgrade);
        }
        $out .= html_writer::end_tag('tbody');
        $out .= html_writer::end_tag('table');
        $out .= html_writer::end_div(); // Close table-responsive-mobile
        $out .= html_writer::end_div(); // Close student-outcome-section
        
        return $out;
    }
    
    /**
     * Render a single indicator row
     * Updated for new database structure
     */
    /**
     * Render an individual indicator row with performance levels and grade input.
     * 
     * @param array $indicator Indicator data structure
     * @param array $rubric Complete rubric data
     * @param array $options Display options
     * @param int $mode Display mode
     * @param string $elementname Form element name prefix
     * @param array|null $value Current evaluation data
     * @param bool $showgradecolumn Whether to show grade column
     * @return string HTML output for the indicator row
     */
    private function render_indicator_row($indicator, $rubric, $options, $mode, $elementname, $value, bool $showgradecolumn = false) {
        $indicator_id = $indicator['id'];
        $current_value = isset($value['criteria'][$indicator_id]) ? $value['criteria'][$indicator_id] : [];
        
        $out = '';
        $out .= html_writer::start_tag('tr', ['class' => 'indicator-row', 'data-indicator-id' => $indicator_id]);
        
        // Indicator description cell
        $indicator_text = ($indicator['indicator'] ?? 'N/A') . '. ' . ($indicator['description'] ?? 'No description');
        $out .= html_writer::tag('td', $indicator_text, ['class' => 'criterion-cell']);
        
        // Performance levels cell
        $levels_html = '';
        if (!empty($indicator['levels'])) {
            $levels_html = $this->render_indicator_performance_levels($indicator['levels'], $indicator_id, $mode, $elementname, $current_value);
        }
        $out .= html_writer::tag('td', $levels_html, ['class' => 'levels-cell']);
        
        // Grading cell (interactive or read-only)
        if ($showgradecolumn) {
            $grade_html = $this->render_grading_controls($indicator, $rubric, $mode, $elementname, $current_value);
            $out .= html_writer::tag('td', $grade_html, ['class' => 'grade-cell align-top']);
        }
        
        $out .= html_writer::end_tag('tr');
        
        return $out;
    }

    /**
     * Check if the current grading form has any grades/results
     * 
     * This method provides a centralized way to determine if any criteria
     * in the rubric have been graded. It checks for the presence of 'levelid'
     * values in the criteria data, which indicates that a performance level
     * has been selected for at least one criterion.
     * 
     * @param array|null $value The grading data from get_utbrubrics_filling() or form submission
     *                          Expected structure: ['criteria' => [criterion_id => ['levelid' => value, ...]]]
     * @return bool True if any criterion has a selected performance level, false otherwise
     */
    protected function has_graded_criteria(array $value = null): bool {
        if (!$value || !isset($value['criteria']) || !is_array($value['criteria'])) {
            return false;
        }

        foreach ($value['criteria'] as $criteriondata) {
            if (!is_array($criteriondata)) {
                continue;
            }

            $levelid = $criteriondata['performance_level_id'] ?? $criteriondata['levelid'] ?? null;
            if ($levelid !== null && $levelid !== '') {
                return true;
            }

            $score = $criteriondata['score'] ?? $criteriondata['chosenvalue'] ?? null;
            if ($score !== null && trim((string)$score) !== '') {
                return true;
            }

            $feedback = $criteriondata['feedback'] ?? $criteriondata['remark'] ?? '';
            if (trim((string)$feedback) !== '') {
                return true;
            }

            if (!empty($criteriondata['performance_level_name'])) {
                return true;
            }

            if (isset($criteriondata['id']) && !empty($criteriondata['id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current student has submitted their work for evaluation.
     *
     * Returns true when we can confirm a submitted attempt, false when we
     * positively know there is no submission yet, and null when the status
     * cannot be determined from the available context.
     */
    protected function has_student_submitted(int $mode, array $value = null): ?bool {
        if ($mode !== gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT) {
            return null;
        }

        if (is_array($value) && isset($value['meta']['submissionstatus'])) {
            $metastatus = $value['meta']['submissionstatus'];
            if ($metastatus === 'submitted') {
                return true;
            }
            if ($metastatus === 'notsubmitted') {
                return false;
            }
        }

        global $USER, $DB;

        if (empty($USER) || empty($USER->id)) {
            return null;
        }

        $cm = $this->page->cm ?? null;
        $context = $this->page->context ?? null;

        if (!$cm && $context && isset($context->contextlevel) && $context->contextlevel == CONTEXT_MODULE) {
            try {
                $cm = get_coursemodule_from_id(null, $context->instanceid, 0, IGNORE_MISSING);
            } catch (Exception $e) {
                $cm = null;
            }
        }

        if (!$cm) {
            return null;
        }

        // Currently only assignments expose submission state reliably.
        if ($cm->modname === 'assign') {
            $params = [$cm->instance, $USER->id];
            $sql = "SELECT status FROM {assign_submission} WHERE assignment = ? AND userid = ?
                    ORDER BY attemptnumber DESC, timemodified DESC";
            $submissions = $DB->get_records_sql($sql, $params, 0, 1);

            if ($submissions) {
                $submission = reset($submissions);
                $status = $submission->status ?? '';

                if ($status === 'submitted') {
                    return true;
                }

                // Treat draft/new/reopened attempts as not submitted yet.
                if (in_array($status, ['draft', 'new', 'reopened', '']) ) {
                    return false;
                }
            } else {
                // No submission record for this user yet.
                return false;
            }

            return null;
        }

        return null;
    }

    /**
     * Get human-readable mode name for debugging
     */
    protected function get_mode_name($mode): string {
        switch ($mode) {
            case gradingform_utbrubrics_controller::DISPLAY_EDIT_FULL: return 'EDIT_FULL';
            case gradingform_utbrubrics_controller::DISPLAY_EDIT_FROZEN: return 'EDIT_FROZEN';
            case gradingform_utbrubrics_controller::DISPLAY_PREVIEW: return 'PREVIEW';
            case gradingform_utbrubrics_controller::DISPLAY_PREVIEW_GRADED: return 'PREVIEW_GRADED';
            case gradingform_utbrubrics_controller::DISPLAY_EVAL: return 'EVAL';
            case gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN: return 'EVAL_FROZEN';
            case gradingform_utbrubrics_controller::DISPLAY_REVIEW: return 'REVIEW';
            case gradingform_utbrubrics_controller::DISPLAY_VIEW: return 'VIEW';
            case gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT: return 'STUDENT_RESULT';
            default: return 'UNKNOWN';
        }
    }



    /**
     * Display the UTB rubric with its criteria and performance levels
     * 
     * @param mixed $rid Rubric ID
     * @param array $rubric Rubric definition with criteria and levels
     * @param array $options Plugin options
     * @param int $mode Display mode constant
     * @param string $elementname Form element name prefix
     * @param array|null $value Grading data - contains existing grades/selections from database
     *                          or form submission. Structure matches get_utbrubrics_filling() output.
     */
    protected function display_single_rubric($rid, array $rubric, array $options, int $mode, string $elementname, array $value = null): string {
        $out = '';
        
        // Determine display characteristics once
        $isStudentView = ($mode == gradingform_utbrubrics_controller::DISPLAY_VIEW);
        $isStudentResult = ($mode == gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT);
        $isPreviewMode = ($mode == gradingform_utbrubrics_controller::DISPLAY_PREVIEW || 
                         $mode == gradingform_utbrubrics_controller::DISPLAY_PREVIEW_GRADED);
        $isEvaluationMode = ($mode == gradingform_utbrubrics_controller::DISPLAY_EVAL);
        
        // For debug purposes, treat student result as student view
        $isAnyStudentView = $isStudentView || $isStudentResult;
        
        // Check if any criteria have been graded (has performance levels selected)
        // This determines whether to show grade columns and what content to display
        // When ungraded: $value = [] or $value = ['criteria' => []]
        $hasGrades = $this->has_graded_criteria($value);
        
        // Information banners based on mode
        if ($isStudentResult) {
            if ($hasGrades) {
                $out .= html_writer::div(
                    html_writer::tag('h5', get_string('yourgradingresults', 'gradingform_utbrubrics'), ['class' => 'mb-2']) .
                    html_writer::tag('p', get_string('resultsexplanation', 'gradingform_utbrubrics'), ['class' => 'mb-0']),
                    'alert alert-success'
                );
            } else {
                $out .= html_writer::div(
                    html_writer::tag('h5', get_string('notyetgraded', 'gradingform_utbrubrics'), ['class' => 'mb-2']) .
                    html_writer::tag('p', get_string('notgradedexplanation', 'gradingform_utbrubrics'), ['class' => 'mb-0']),
                    'alert alert-warning'
                );
            }
        } elseif ($isStudentView) {
            $out .= html_writer::div(
                html_writer::tag('h5', '📋 ' . get_string('evaluation_rubric', 'gradingform_utbrubrics'), ['class' => 'mb-2']) .
                html_writer::tag('p', get_string('evaluation_rubric_description', 'gradingform_utbrubrics'), ['class' => 'mb-0']),
                'alert alert-info'
            );
        } elseif ($isPreviewMode) {
            $out .= html_writer::div(
                html_writer::tag('h5', '👁️ ' . get_string('rubric_preview', 'gradingform_utbrubrics'), ['class' => 'mb-2']) .
                html_writer::tag('p', get_string('rubric_preview_description', 'gradingform_utbrubrics'), ['class' => 'mb-0']),
                'alert alert-secondary'
            );
        }

        // Responsive table container for mobile horizontal scroll
        $out .= html_writer::start_div('table-responsive-mobile');
        $out .= html_writer::start_tag('table', ['class' => 'table table-bordered rubric-table']);
        $out .= html_writer::start_tag('thead');
        
        if ($isStudentResult) {
            if ($hasGrades) {
                // Student results view headers (with grades) - similar to original Moodle rubrics
                $gradeheader = get_string('yourgradefeedback', 'gradingform_utbrubrics');
                $out .= html_writer::tag('tr',
                    html_writer::tag('th', get_string('performanceindicator', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 20%;']) .
                    html_writer::tag('th', get_string('performancelevelsdescriptions', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 65%;']) .
                    html_writer::tag('th', $gradeheader, ['class' => 'text-center', 'style' => 'width: 15%;'])
                );
            } else {
                // Student results view but not graded yet - show rubric info
                $out .= html_writer::tag('tr',
                    html_writer::tag('th', get_string('performanceindicator', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 30%;']) .
                    html_writer::tag('th', get_string('performancelevelsdescriptions', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 70%;'])
                );
            }
        } elseif ($isStudentView) {
            // Pure rubric view for reference - no grade column
            $out .= html_writer::tag('tr',
                html_writer::tag('th', get_string('performanceindicator', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 30%;']) .
                html_writer::tag('th', get_string('performance_levels_descriptions', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 70%;'])
            );
        } elseif ($isPreviewMode) {
            // Preview mode headers (advanced grading view - read only)
            $out .= html_writer::tag('tr',
                html_writer::tag('th', get_string('performanceindicator', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 30%;']) .
                html_writer::tag('th', get_string('performance_levels_descriptions', 'gradingform_utbrubrics'), ['class' => 'text-center', 'style' => 'width: 70%;'])
            );
        } else {
            // Teacher/evaluator view headers (when actually grading a student)
            $out .= html_writer::tag('tr',
                html_writer::tag('th', get_string('performanceindicator', 'gradingform_utbrubrics'), ['class' => 'text-center']) .
                html_writer::tag('th', get_string('performancelevelsdescriptions', 'gradingform_utbrubrics'), ['class' => 'text-center']) .
                html_writer::tag('th', get_string('gradeselectlevel', 'gradingform_utbrubrics'), ['class' => 'text-center'])
            );
        }
        
        $out .= html_writer::end_tag('thead');
        $out .= html_writer::start_tag('tbody');

        foreach ((isset($rubric['criteria']) ? $rubric['criteria'] : []) as $cid => $crit) {
            $out .= html_writer::start_tag('tr', ['class' => 'criterion-row']);
            
            // Criterion description with indicator ID from the criterion data itself
            $indicatorId = isset($crit['indicator']) && !empty($crit['indicator']) ? strtoupper($crit['indicator']) : '';
            $criterionTitle = $indicatorId ? get_string('indicator', 'gradingform_utbrubrics') . " {$indicatorId}: " . format_text($crit['description']) : format_text($crit['description']);
            
            $out .= html_writer::tag('td', 
                html_writer::div($criterionTitle, 'criterion-description font-weight-bold'),
                ['class' => 'criterion-cell align-top', 'style' => 'vertical-align: top;']
            );

            // Performance levels
            $levelshtml = $this->render_performance_levels($cid, $crit['levels'], $elementname, $value, $mode);
            $out .= html_writer::tag('td', $levelshtml, ['class' => 'levels-cell']);

            // Grade input, student result, or nothing (depending on mode)
            if ($isPreviewMode) {
                // Preview mode: no grade column at all (already handled in header)
            } elseif ($isStudentResult && $hasGrades) {
                // Student results view with grades: show grade column
                $gradehtml = $this->render_student_result($cid, $crit['levels'], $elementname, $value);
                $out .= html_writer::tag('td', $gradehtml, ['class' => 'grade-cell text-center align-top']);
            } elseif ($isStudentResult && !$hasGrades) {
                // Student results view but not graded yet: no grade column
            } elseif ($isStudentView) {
                // Pure student view (reference only): no grade column
            } else {
                // Evaluation mode: show grade input
                $gradehtml = $this->render_grade_input($cid, $crit['levels'], $elementname, $value, $mode);
                $out .= html_writer::tag('td', $gradehtml, ['class' => 'grade-cell text-center align-top']);
            }

            $out .= html_writer::end_tag('tr');
        }

        $out .= html_writer::end_tag('tbody');
        $out .= html_writer::end_tag('table');
        $out .= html_writer::end_div(); // Close table-responsive-mobile
        return $out;
    }

    /**
     * Render performance levels for a criterion
     */
    protected function render_performance_levels($cid, array $levels, string $elementname, array $value = null, int $mode): string {
        $checkedlevel = $value['criteria'][$cid]['levelid'] ?? null;
        
        // Check different display modes
        $isStudentView = ($mode == gradingform_utbrubrics_controller::DISPLAY_VIEW);
        $isStudentResult = ($mode == gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT);
        $isPreviewMode = ($mode == gradingform_utbrubrics_controller::DISPLAY_PREVIEW || 
                         $mode == gradingform_utbrubrics_controller::DISPLAY_PREVIEW_GRADED);
        
        // For student views, show a simpler read-only display
        if ($isStudentView || $isStudentResult) {
            return $this->render_student_performance_levels($levels, $checkedlevel);
        }
        
        // For preview mode (advanced grading view), show read-only cards without interaction
        if ($isPreviewMode) {
            return $this->render_preview_performance_levels($levels);
        }
        
        // For evaluation mode, use dynamic distribution system
        $levelCount = count($levels);
        $levelsClass = 'levels-' . min($levelCount, 6); // Cap at 6 for CSS support
        $out = html_writer::start_div('performance-levels ' . $levelsClass);
        
        foreach ($levels as $lid => $lev) {
            $isSelected = ((string)$checkedlevel === (string)$lid);
            
            // Add level type class and selection state - support both English and Spanish
            $range = number_format($lev['min'], 2) . ' - ' . number_format($lev['max'], 2);
            $levelTypeClass = $this->get_level_type_class($lev['definition']);
            
            $levelClass = 'level-option ' . $levelTypeClass . ($isSelected ? ' selected' : '');
            $levelAttributes = [
                'data-min' => $lev['min'],
                'data-max' => $lev['max'],
                'data-levelid' => $lid
            ];
            
            $radioattrs = [
                'type' => 'radio', 
                'name' => "{$elementname}[criteria][{$cid}][levelid]", 
                'value' => $lid, 
                'id' => "{$elementname}_{$cid}_{$lid}",
                'class' => 'level-radio'
            ];
            
            if ($isSelected) {
                $radioattrs['checked'] = 'checked';
            }
            
            if ($mode == gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN || 
                $mode == gradingform_utbrubrics_controller::DISPLAY_REVIEW ||
                $mode == gradingform_utbrubrics_controller::DISPLAY_VIEW) {
                $radioattrs['disabled'] = 'disabled';
            }

            // Background class only applied when selected (handled by CSS)
            $levelBgClass = '';

            // Create content with clear separation: Title first, description below
            $labelContent = html_writer::start_div('card-body p-2');
            
            // Level name with range - this should be the header/title
            $labelContent .= html_writer::div(
                html_writer::tag('strong', format_string($lev['definition'])),
                'level-name text-center mb-2 bg-light p-2 rounded'
            );
            
            // Add detailed description ONLY if it exists and is not empty
            if (!empty($lev['description']) && trim($lev['description']) !== '') {
                $labelContent .= html_writer::div(
                    format_string($lev['description']),
                    'level-description small text-muted'
                );
            }
            
            $labelContent .= html_writer::end_div(); // card-body

            $out .= html_writer::div(
                html_writer::empty_tag('input', $radioattrs) .
                html_writer::tag('label', 
                    html_writer::div($labelContent, 'card h-100 border-light'),
                    [
                        'for' => "{$elementname}_{$cid}_{$lid}",
                        'class' => 'level-label w-100 cursor-pointer'
                    ]
                ),
                $levelClass,
                $levelAttributes
            );
        }
        
        $out .= html_writer::end_div();
        
        static $jsinitialised = false;

        if (!$jsinitialised) {
            $jsinitialised = true;
            
            // Load JavaScript strings
            $this->page->requires->strings_for_js([
                'selectlevel',
                'graderange'
            ], 'gradingform_utbrubrics');

            $js = <<<'JS'
(function() {
    function clearValidation(input) {
        input.classList.remove('is-invalid', 'is-valid');
        var existingFeedback = input.parentNode.querySelector('.invalid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
    }

    function updateRangeDisplay(container, text, classname) {
        var rangeDisplay = container ? container.querySelector('.grade-range') : null;
        if (!rangeDisplay) {
            return;
        }
        rangeDisplay.textContent = text;
        rangeDisplay.className = classname;
    }

    function resetGradeInput(row) {
        if (!row) {
            return;
        }
        var gradeInput = row.querySelector('.grade-input');
        if (!gradeInput || gradeInput.readOnly) {
            return;
        }
        gradeInput.value = '';
        gradeInput.disabled = true;
        gradeInput.removeAttribute('min');
        gradeInput.removeAttribute('max');
        gradeInput.placeholder = '';
        clearValidation(gradeInput);
        updateRangeDisplay(gradeInput.parentNode, M.util.get_string('selectlevel', 'gradingform_utbrubrics') || 'Select performance level first', 'grade-range text-center small text-warning mt-1');
    }

    function updateSelection(radio, focusGrade) {
        var option = radio.closest('.level-option');
        if (!option) {
            return;
        }

        var container = option.closest('.performance-levels');
        if (container) {
            container.querySelectorAll('.level-option').forEach(function(other) {
                if (other === option) {
                    return;
                }
                other.classList.remove('selected');
                var otherRadio = other.querySelector('input[type=radio]');
                if (otherRadio && otherRadio !== radio) {
                    otherRadio.checked = false;
                }
            });
        }

        option.classList.add('selected');

        var row = option.closest('tr');
        var gradeInput = row ? row.querySelector('.grade-input') : null;
        if (!gradeInput || gradeInput.readOnly) {
            return;
        }

        var min = parseFloat(option.dataset.min);
        var max = parseFloat(option.dataset.max);

        if (!isNaN(min) && !isNaN(max)) {
            // Check if this is a change from a previously selected level
            var wasGraded = gradeInput.value && gradeInput.value.trim() !== '';
            var currentMin = parseFloat(gradeInput.min);
            var currentMax = parseFloat(gradeInput.max);
            var isLevelChange = !isNaN(currentMin) && !isNaN(currentMax) && 
                               (currentMin !== min || currentMax !== max);
            
            gradeInput.min = min;
            gradeInput.max = max;
            gradeInput.placeholder = min + ' - ' + max;
            gradeInput.disabled = false;
            
            // Clear existing grade when performance level changes
            if (isLevelChange && wasGraded) {
                gradeInput.value = '';
                // Show visual feedback that grade was cleared
                gradeInput.style.background = '#fff3cd';
                gradeInput.style.borderColor = '#ffc107';
                setTimeout(function() {
                    gradeInput.style.background = '';
                    gradeInput.style.borderColor = '';
                }, 1500);
            }
            
            clearValidation(gradeInput);
            var rangeText = M.util.get_string('graderange', 'gradingform_utbrubrics') || 'Range: {$a->min} - {$a->max}';
            rangeText = rangeText.replace('{$a->min}', min).replace('{$a->max}', max);
            updateRangeDisplay(gradeInput.parentNode, rangeText, 'grade-range text-center small text-muted mt-1');
            if (focusGrade) {
                gradeInput.focus();
                gradeInput.select();
            }
        }
    }

    function handleLevelOptionClick(event) {
        var option = event.currentTarget;
        var radio = option.querySelector('input[type=radio]');
        if (!radio || radio.disabled) {
            return;
        }

        if (event.target && event.target.matches('input[type=radio]')) {
            return;
        }

        if (option.classList.contains('selected') && radio.checked) {
            radio.checked = false;
            option.classList.remove('selected');
            resetGradeInput(option.closest('tr'));
            radio.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        radio.checked = true;
        updateSelection(radio, true);
        radio.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function handleRadioChange(event) {
        var radio = event.target;
        if (radio.checked) {
            updateSelection(radio, false);
        } else {
            resetGradeInput(radio.closest('tr'));
        }
    }

    function handleGradeInput(event) {
        var input = event.target;
        if (input.readOnly || input.disabled) {
            return;
        }

        clearValidation(input);

        var value = parseFloat(input.value);
        var min = parseFloat(input.min);
        var max = parseFloat(input.max);

        if (!isNaN(value) && !isNaN(min) && !isNaN(max)) {
            if (value < min || value > max) {
                input.classList.add('is-invalid');
                var feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Value must be between ' + min + ' and ' + max;
                input.parentNode.appendChild(feedback);
            } else {
                input.classList.add('is-valid');
            }
        }
    }

    function init(root) {
        root = root || document;
        var containers = root.querySelectorAll('.gradingform_utbrubrics');
        if (!containers.length) {
            return;
        }

        root.querySelectorAll('.gradingform_utbrubrics .level-option').forEach(function(option) {
            if (option.dataset.utbrubricsBound) {
                return;
            }
            option.dataset.utbrubricsBound = '1';
            option.addEventListener('click', handleLevelOptionClick);
        });

        root.querySelectorAll('.gradingform_utbrubrics input[type=radio].level-radio').forEach(function(radio) {
            if (radio.dataset.utbrubricsBound) {
                return;
            }
            radio.dataset.utbrubricsBound = '1';
            radio.addEventListener('change', handleRadioChange);
        });

        root.querySelectorAll('.gradingform_utbrubrics .grade-input').forEach(function(input) {
            if (!input.dataset.utbrubricsInitialised) {
                if (!input.readOnly && !input.value && !input.hasAttribute('min')) {
                    input.disabled = true;
                    input.placeholder = '';
                }
                input.dataset.utbrubricsInitialised = '1';
            }

            if (input.dataset.utbrubricsBound) {
                return;
            }

            input.dataset.utbrubricsBound = '1';
            input.addEventListener('input', handleGradeInput);
        });

        root.querySelectorAll('.gradingform_utbrubrics input[type=radio].level-radio:checked').forEach(function(radio) {
            updateSelection(radio, false);
        });
    }

    window.gradingformAbetrubricsInit = init;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            init(document);
        });
    } else {
        init(document);
    }
})();
JS;

            $out .= html_writer::script($js);
        } else {
            $out .= html_writer::script('if (window.gradingformAbetrubricsInit) { window.gradingformAbetrubricsInit(document); }');
        }

        return $out;
    }

    /**
            .gradingform_utbrubrics .feedback-input {
                min-height: 96px;
                resize: vertical;
            }
     * Render grade input for a criterion
     */
    protected function render_grade_input($cid, array $levels, string $elementname, array $value = null, int $mode): string {
        $chosen = $value['criteria'][$cid]['chosenvalue'] ?? '';
        // Format chosen value to 2 decimal places for display
        if ($chosen !== '' && is_numeric($chosen)) {
            $chosen = number_format((float)$chosen, 2, '.', '');
        }
        $selectedLevel = $value['criteria'][$cid]['levelid'] ?? null;
        
        // Find range for selected level
        $range = null;
        if ($selectedLevel) {
            foreach ($levels as $lid => $lev) {
                if ((string)$lid === (string)$selectedLevel) {
                    $range = ['min' => $lev['min'], 'max' => $lev['max']];
                    break;
                }
            }
        }

        $inputattrs = [
            'type' => 'number', 
            'step' => '0.01', 
            'name' => "{$elementname}[criteria][{$cid}][chosenvalue]", 
            'value' => $chosen, 
            'class' => 'form-control grade-input'
        ];

        if ($range) {
            $inputattrs['min'] = $range['min'];
            $inputattrs['max'] = $range['max'];
            $inputattrs['placeholder'] = $range['min'] . ' - ' . $range['max'];
            $inputattrs['title'] = get_string('graderange', 'gradingform_utbrubrics', (object)['min' => $range['min'], 'max' => $range['max']]);
        } else {
            $inputattrs['disabled'] = 'disabled';
        }

        if ($mode == gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN || 
            $mode == gradingform_utbrubrics_controller::DISPLAY_REVIEW ||
            $mode == gradingform_utbrubrics_controller::DISPLAY_VIEW) {
            $inputattrs['readonly'] = 'readonly';
            unset($inputattrs['disabled']);
        }

        $out = html_writer::start_div('grade-input-container');
        $out .= html_writer::tag('label', get_string('grade_label', 'gradingform_utbrubrics'), ['class' => 'small font-weight-bold text-muted mb-1 d-block']);
        $out .= html_writer::empty_tag('input', $inputattrs);
        
        if ($range) {
            $out .= html_writer::div(
                get_string('graderange', 'gradingform_utbrubrics', (object)['min' => $range['min'], 'max' => $range['max']]),
                'grade-range text-center small text-muted mt-1'
            );
        } else {
            $out .= html_writer::div(
                get_string('selectlevel', 'gradingform_utbrubrics'),
                'grade-range text-center small text-warning mt-1'
            );
        }
        
        // Add remark field for feedback
        $remarkValue = $value['criteria'][$cid]['remark'] ?? '';
        $remarkAttrs = [
            'name' => "{$elementname}[criteria][{$cid}][remark]",
            'class' => 'form-control form-control-sm mt-2 noeditor',
            'placeholder' => get_string('optionalfeedback', 'gradingform_utbrubrics'),
            'rows' => '2'
        ];
        
        if ($mode == gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN || 
            $mode == gradingform_utbrubrics_controller::DISPLAY_REVIEW ||
            $mode == gradingform_utbrubrics_controller::DISPLAY_VIEW) {
            $remarkAttrs['readonly'] = 'readonly';
        }
        
        $out .= html_writer::tag('label', get_string('feedback_label', 'gradingform_utbrubrics'), ['class' => 'small font-weight-bold text-muted mb-1 d-block mt-2']);
        $out .= html_writer::tag('textarea', s($remarkValue), $remarkAttrs);
        $out .= html_writer::end_div();

        return $out;
    }



    /**
     * Render student result view showing their grade and feedback
     * 
     * This method checks individual criterion grading data, unlike has_graded_criteria()
     * which checks if ANY criterion is graded.
     */
    protected function render_student_result($cid, array $levels, string $elementname, array $value = null): string {
        $out = '';
        

        
        // Check if this specific criterion has been graded (has a performance level selected)
        // This is criterion-specific, unlike the global has_graded_criteria() check
        $hasGrade = isset($value['criteria'][$cid]['levelid']) && !empty($value['criteria'][$cid]['levelid']);
        $selectedLevelId = $hasGrade ? $value['criteria'][$cid]['levelid'] : null;
        $chosenValue = isset($value['criteria'][$cid]['chosenvalue']) ? $value['criteria'][$cid]['chosenvalue'] : null;
        $feedback = isset($value['criteria'][$cid]['remark']) ? $value['criteria'][$cid]['remark'] : '';
        
        // Enhanced grade detection - also check for chosen value even if levelid is missing
        if (!$hasGrade && isset($value['criteria'][$cid]['chosenvalue']) && $value['criteria'][$cid]['chosenvalue'] !== null) {
            // Try to find the appropriate level based on the chosen value
            foreach ($levels as $lid => $level) {
                if ($chosenValue >= $level['min'] && $chosenValue <= $level['max']) {
                    $hasGrade = true;
                    $selectedLevelId = $lid;
                    break;
                }
            }
        }
        
        // Show grade information if we have any grading data
        if ($hasGrade || $chosenValue !== null || !empty($feedback)) {
            $selectedLevel = null;
            if ($selectedLevelId && isset($levels[$selectedLevelId])) {
                $selectedLevel = $levels[$selectedLevelId];
            }
            
            // Get level color class
            $levelClass = 'student-result-level';
            $badgeClass = 'badge-secondary';
            
            if ($selectedLevel) {
                if (strpos($selectedLevel['definition'], 'Excellent') !== false) {
                    $levelClass .= ' level-excellent-student';
                    $badgeClass = 'badge-success';
                } elseif (strpos($selectedLevel['definition'], 'Good') !== false) {
                    $levelClass .= ' level-good-student';
                    $badgeClass = 'badge-primary';
                } elseif (strpos($selectedLevel['definition'], 'Fair') !== false) {
                    $levelClass .= ' level-fair-student';
                    $badgeClass = 'badge-warning';
                } elseif (strpos($selectedLevel['definition'], 'Inadequate') !== false) {
                    $levelClass .= ' level-inadequate-student';
                    $badgeClass = 'badge-danger';
                }
            } else if ($chosenValue !== null) {
                // Determine level based on score
                if ($chosenValue >= 4.5) {
                    $levelClass .= ' level-excellent-student';
                    $badgeClass = 'badge-success';
                } elseif ($chosenValue >= 3.5) {
                    $levelClass .= ' level-good-student';
                    $badgeClass = 'badge-primary';
                } elseif ($chosenValue >= 3.0) {
                    $levelClass .= ' level-fair-student';
                    $badgeClass = 'badge-warning';
                } else {
                    $levelClass .= ' level-inadequate-student';
                    $badgeClass = 'badge-danger';
                }
            }
            
            $out .= html_writer::start_div('student-grade-result');
            
            // Score achieved (prominently displayed)
            if ($chosenValue !== null) {
                $scoreDisplay = get_string('score', 'gradingform_utbrubrics') . ': ' . number_format($chosenValue, 2);
                $out .= html_writer::div(
                    html_writer::tag('strong', $scoreDisplay, ['class' => 'h5 mb-2 d-block text-primary']),
                    'achieved-score mb-3'
                );
            } else if ($selectedLevel) {
                // Show level even without specific score
                $out .= html_writer::div(
                    html_writer::tag('strong', get_string('performancelevel', 'gradingform_utbrubrics') . ': ' . $selectedLevel['definition'], ['class' => 'h5 mb-2 d-block text-primary']),
                    'achieved-level mb-3'
                );
            }
            
            // Teacher feedback (if available)
            if (!empty($feedback)) {
                $out .= html_writer::div(
                    html_writer::tag('strong', get_string('teacher_feedback', 'gradingform_utbrubrics'), ['class' => 'small text-muted d-block mb-2']) .
                    html_writer::div(format_text($feedback, FORMAT_MOODLE), 'feedback-text p-3 border-left border-primary bg-light rounded', ['style' => 'border-left-width: 4px !important;']),
                    'teacher-feedback'
                );
            }
            
            $out .= html_writer::end_div();
            
        } else {
            // Not graded yet
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::span(get_string('notyetgraded', 'gradingform_utbrubrics'), 'badge badge-light border p-2 d-block') .
                    html_writer::tag('small', get_string('indicatornotgraded', 'gradingform_utbrubrics'), ['class' => 'text-muted d-block mt-2']),
                    'text-center'
                ),
                'not-graded-indicator'
            );
        }
        
        return $out;
    }

    /**
     * Check if current view should be student view based on user capabilities
     */
    protected function is_student_view(): bool {
        global $USER;
        
        // Get the context for capability checking
        $context = $this->page->context;
        
        // If user cannot manage grading forms and cannot grade, it's likely a student
        if (!has_capability('moodle/grade:managegradingforms', $context) && 
            !has_capability('mod/assign:grade', $context)) {
            return true;
        }
        
        return false;
    }

    /**
     * Render performance levels for student view (read-only, informational)
     * Uses dynamic grid distribution based on the number of performance levels
     */
    protected function render_student_performance_levels(array $levels, $selectedLevelId = null): string {
        $levelCount = count($levels);
        $levelsClass = 'levels-' . min($levelCount, 6); // Cap at 6 for CSS support
        
        // Use dynamic distribution system based on number of levels
        $out = html_writer::start_div('performance-levels ' . $levelsClass);
        
        foreach ($levels as $lid => $lev) {
            $isSelected = ((string)$selectedLevelId === (string)$lid);
            
            // Same level type classification - support both English and Spanish
            $levelTypeClass = $this->get_level_type_class($lev['definition']);
            
            // Use dynamic class structure that will be sized by CSS based on count
            $levelClass = 'level-option ' . $levelTypeClass . ($isSelected ? ' selected' : '');
            
            // Create content with clear separation: Title first, description below
            $labelContent = html_writer::start_div('card-body p-2');
            
            // Level name with range - this should be the header/title
            $labelContent .= html_writer::div(
                html_writer::tag('strong', format_string($lev['definition'])),
                'level-name text-center mb-2 bg-light p-2 rounded'
            );
            
            // Add "YOUR LEVEL" indicator for selected level
            if ($isSelected) {
                $yourlevelText = get_string('yourlevel', 'gradingform_utbrubrics', '✓ TU NIVEL'); // With fallback
                $labelContent .= html_writer::div(
                    html_writer::tag('span', $yourlevelText, [
                        'class' => 'badge',
                        'style' => 'background-color: #28a745; color: white; font-weight: bold; font-size: 11px;'
                    ]),
                    'text-center mb-2'
                );
            }
            
            // Add detailed description ONLY if it exists and is not empty
            if (!empty($lev['description']) && trim($lev['description']) !== '') {
                $labelContent .= html_writer::div(
                    format_string($lev['description']),
                    'level-description small text-muted'
                );
            }
            
            $labelContent .= html_writer::end_div(); // card-body

            // Clean structure for dynamic sizing
            $out .= html_writer::div(
                html_writer::div($labelContent, 'card h-100 border-light'),
                $levelClass
            );
        }
        
        $out .= html_writer::end_div(); // performance-levels
        return $out;
    }

    /**
     * Render performance levels for preview mode (advanced grading view - read only)
     * Uses dynamic grid distribution based on the number of performance levels
     */
    protected function render_preview_performance_levels(array $levels): string {
        $levelCount = count($levels);
        $levelsClass = 'levels-' . min($levelCount, 6); // Cap at 6 for CSS support
        
        // Use dynamic distribution system based on number of levels
        $out = html_writer::start_div('performance-levels ' . $levelsClass);
        
        foreach ($levels as $lid => $lev) {
            // Add level type class (same logic as grading view)
            $levelTypeClass = '';
            if (strpos($lev['definition'], 'Excellent') !== false || strpos($lev['definition'], 'Excelente') !== false) {
                $levelTypeClass = 'level-excellent';
            } elseif (strpos($lev['definition'], 'Good') !== false || strpos($lev['definition'], 'Bueno') !== false) {
                $levelTypeClass = 'level-good';
            } elseif (strpos($lev['definition'], 'Fair') !== false || strpos($lev['definition'], 'Regular') !== false) {
                $levelTypeClass = 'level-fair';
            } elseif (strpos($lev['definition'], 'Inadequate') !== false || strpos($lev['definition'], 'Inadecuado') !== false) {
                $levelTypeClass = 'level-inadequate';
            }
            
            // Use dynamic class structure that will be sized by CSS based on count
            $levelClass = 'level-option ' . $levelTypeClass;

            // Create content with clear separation: Title first, description below
            $labelContent = html_writer::start_div('card-body p-2');
            
            // Level name with range - this should be the header/title
            $labelContent .= html_writer::div(
                html_writer::tag('strong', format_string($lev['definition'])),
                'level-name text-center mb-2 bg-light p-2 rounded'
            );
            
            // Add detailed description ONLY if it exists and is not empty
            if (!empty($lev['description']) && trim($lev['description']) !== '') {
                $labelContent .= html_writer::div(
                    format_string($lev['description']),
                    'level-description small text-muted'
                );
            }
            
            $labelContent .= html_writer::end_div(); // card-body

            // Clean structure for dynamic sizing
            $out .= html_writer::div(
                html_writer::div($labelContent, 'card h-100 border-light'),
                $levelClass
            );
        }
        
        $out .= html_writer::end_div(); // performance-levels
        return $out;
    }
    
    /**
     * Render performance levels for an indicator
     * Updated for new database structure
     */
    private function render_indicator_performance_levels($levels, $indicator_id, $mode, $elementname, $current_value) {
        $levelcount = max(1, min(count($levels), 6));
        $selectedlevel = $this->resolve_selected_level($levels, $current_value);
        $selectedlevelid = $selectedlevel['id'] ?? ($current_value['performance_level_id'] ?? null);

        $containerattrs = [
            'class' => 'performance-levels levels-' . $levelcount,
            'data-indicator-id' => $indicator_id
        ];

        $out = html_writer::start_tag('div', $containerattrs);

        foreach ($levels as $level) {
            $levelid = $level['id'];
            $isselected = ((string)$selectedlevelid === (string)$levelid);
            $leveltype = $this->resolve_level_type($level['definition'] ?? '');
            $levelclass = 'level-option';
            if ($leveltype !== 'default') {
                $levelclass .= ' level-' . $leveltype;
            }
            if ($isselected) {
                $levelclass .= ' selected';
            }

            $minvalue = isset($level['min']) ? number_format((float)$level['min'], 2, '.', '') : '';
            $maxvalue = isset($level['max']) ? number_format((float)$level['max'], 2, '.', '') : '';
            $rangeobject = (object)[
                'min' => $minvalue !== '' ? $minvalue : '0.00',
                'max' => $maxvalue !== '' ? $maxvalue : '0.00'
            ];
            $rangetext = ($minvalue !== '' && $maxvalue !== '') ?
                get_string('graderange', 'gradingform_utbrubrics', $rangeobject) : '';

            $optionattrs = [
                'class' => $levelclass,
                'data-level-id' => $levelid,
                'data-indicator-id' => $indicator_id,
                'data-min' => $minvalue,
                'data-max' => $maxvalue,
                'data-level-name' => format_string($level['definition'] ?? ''),
                'data-level-type' => $leveltype,
                'data-range-label' => $rangetext,
                'tabindex' => $this->is_grading_mode($mode) ? '0' : '-1'
            ];

            if (!$this->is_grading_mode($mode)) {
                $optionattrs['data-readonly'] = '1';
            }

            $radioid = $elementname . '_' . $indicator_id . '_level_' . $levelid;
            $radioattrs = [
                'type' => 'radio',
                'name' => $elementname . '[criteria][' . $indicator_id . '][performance_level_id]',
                'value' => $levelid,
                'id' => $radioid,
                'class' => 'performance-level-radio visually-hidden'
            ];

            if ($isselected) {
                $radioattrs['checked'] = 'checked';
            }

            if (!$this->is_grading_mode($mode)) {
                $radioattrs['disabled'] = 'disabled';
            }

            $cardbody = html_writer::start_div('card-body p-3');
            $cardbody .= html_writer::div(
                html_writer::tag('strong', format_string($level['definition'] ?? '')),
                'level-name text-center mb-2'
            );

            if (!empty($rangetext)) {
                $cardbody .= html_writer::div($rangetext, 'level-range text-center text-muted small');
            }

            if (!empty($level['description'])) {
                $cardbody .= html_writer::div(
                    format_string($level['description']),
                    'level-description card-text small mt-2'
                );
            }

            $cardbody .= html_writer::end_div();

            $cardcontent = html_writer::div($cardbody, 'card level-card h-100');

            $out .= html_writer::tag('div',
                html_writer::empty_tag('input', $radioattrs) .
                html_writer::tag('label', $cardcontent, [
                    'for' => $radioid,
                    'class' => 'level-card-label w-100'
                ]),
                $optionattrs
            );
        }

        $out .= html_writer::end_tag('div');

        if ($this->is_grading_mode($mode)) {
            static $cardsinitialised = false;

            if (!$cardsinitialised) {
                $cardsinitialised = true;

                $js = <<<'JS'
(function() {
    function findIndicatorRow(indicatorId) {
        if (!indicatorId) {
            return null;
        }
        return document.querySelector('.indicator-row[data-indicator-id="' + indicatorId + '"]');
    }

    function resetIndicator(indicatorId) {
        var row = findIndicatorRow(indicatorId);
        if (!row) {
            return;
        }

        var controls = row.querySelector('.grading-controls');
        var scoreInput = controls ? controls.querySelector('.score-input') : null;
        var rangeDisplay = controls ? controls.querySelector('.score-range-display') : null;

        if (scoreInput) {
            scoreInput.value = '';
            scoreInput.disabled = true;
            scoreInput.removeAttribute('min');
            scoreInput.removeAttribute('max');
            scoreInput.classList.remove('is-valid', 'is-invalid');
        }

        if (rangeDisplay) {
            var defaultText = rangeDisplay.dataset.defaultText || '';
            rangeDisplay.textContent = defaultText;
            rangeDisplay.className = 'score-range-display small text-muted mt-1';
            rangeDisplay.dataset.currentText = defaultText;
            rangeDisplay.dataset.rangeLabel = '';
        }
    }

    function applySelection(option, focusScore) {
        var container = option.closest('.performance-levels');
        var radio = option.querySelector('.performance-level-radio');
        if (!container || !radio) {
            return;
        }

        container.querySelectorAll('.level-option').forEach(function(other) {
            if (other === option) {
                return;
            }
            other.classList.remove('selected');
            var otherRadio = other.querySelector('.performance-level-radio');
            if (otherRadio) {
                otherRadio.checked = false;
            }
        });

        option.classList.add('selected');
        if (!radio.checked) {
            radio.checked = true;
        }

        var indicatorId = option.dataset.indicatorId;
        var row = findIndicatorRow(indicatorId);
        if (!row) {
            return;
        }

        var controls = row.querySelector('.grading-controls');
        var scoreInput = controls ? controls.querySelector('.score-input') : null;
        var rangeDisplay = controls ? controls.querySelector('.score-range-display') : null;

        var min = parseFloat(option.dataset.min);
        var max = parseFloat(option.dataset.max);
        var rangeLabel = option.dataset.rangeLabel || '';

        if (scoreInput) {
            scoreInput.disabled = false;
            if (!isNaN(min)) {
                scoreInput.min = min;
            }
            if (!isNaN(max)) {
                scoreInput.max = max;
            }

            if (scoreInput.value !== '') {
                var current = parseFloat(scoreInput.value);
                if (( !isNaN(min) && current < min) || ( !isNaN(max) && current > max)) {
                    scoreInput.value = '';
                }
            }

            scoreInput.classList.remove('is-valid', 'is-invalid');

            if (focusScore && !scoreInput.readOnly) {
                scoreInput.focus();
                scoreInput.select();
            }
        }

        if (rangeDisplay) {
            var baseText = rangeLabel || rangeDisplay.dataset.defaultText || '';
            rangeDisplay.textContent = baseText;
            rangeDisplay.className = 'score-range-display small text-muted mt-1';
            rangeDisplay.dataset.currentText = baseText;
            rangeDisplay.dataset.rangeLabel = rangeLabel || '';
        }

        validateScoreInput(scoreInput);
    }

    function validateScoreInput(input) {
        if (!input) {
            return;
        }

        var controls = input.closest('.grading-controls');
        var rangeDisplay = controls ? controls.querySelector('.score-range-display') : null;
        var baseClass = 'score-range-display small text-muted mt-1';

        input.classList.remove('is-valid', 'is-invalid');

        if (rangeDisplay) {
            if (!rangeDisplay.dataset.defaultClass) {
                rangeDisplay.dataset.defaultClass = baseClass;
            }
            var currentText = rangeDisplay.dataset.currentText || rangeDisplay.dataset.defaultText || '';
            rangeDisplay.textContent = currentText;
            rangeDisplay.className = rangeDisplay.dataset.defaultClass;
        }

        if (input.disabled) {
            return;
        }

        var rawValue = input.value.trim();

        // Don't validate empty inputs
        if (rawValue === '') {
            return;
        }

        // Basic format checks
        var justDot = rawValue === '.';
        var justComma = rawValue === ',';
        var hasComma = rawValue.indexOf(',') !== -1;
        var hasNonNumeric = /[^0-9.,]/.test(rawValue);
        var dotMatches = rawValue.match(/\./g) || [];
        var hasMultipleDots = dotMatches.length > 1;
        var hasTrailingDot = rawValue.endsWith('.');
        var hasTrailingComma = rawValue.endsWith(',');

        // Treat any comma usage as invalid input (keep behaviour consistent with previous validation)
        var invalidCommaUsage = hasComma;

        var invalidFormat = justDot || justComma || hasNonNumeric || hasMultipleDots || hasTrailingDot || hasTrailingComma || invalidCommaUsage;

        var value = NaN;
        if (!invalidFormat) {
            value = parseFloat(rawValue);
        }
        var isParseable = !isNaN(value) && isFinite(value);

        // A number is valid only when the format is correct and it's parseable
        var isValidNumber = !invalidFormat && isParseable;

        // Debug logging for testing
        console.log('Validating:', rawValue, {
            justDot: justDot,
            justComma: justComma,
            hasComma: hasComma,
            hasNonNumeric: hasNonNumeric,
            hasMultipleDots: hasMultipleDots,
            hasTrailingDot: hasTrailingDot,
            hasTrailingComma: hasTrailingComma,
            invalidFormat: invalidFormat,
            isParseable: isParseable,
            value: value,
            isValidNumber: isValidNumber
        });

        if (!isValidNumber) {
            input.classList.add('is-invalid');
            if (rangeDisplay) {
                var invalidText = rangeDisplay.dataset.rangeLabel || rangeDisplay.dataset.currentText || rangeDisplay.dataset.defaultText || 'Número inválido';
                rangeDisplay.textContent = '⚠ ' + invalidText;
                rangeDisplay.className = 'score-range-display small text-danger mt-1 font-weight-bold';
            }
            return;
        }

        var min = parseFloat(input.min);
        var max = parseFloat(input.max);

        var outOfRange = false;

        if (!outOfRange && !isNaN(min) && value < min) {
            outOfRange = true;
        }

        if (!outOfRange && !isNaN(max) && value > max) {
            outOfRange = true;
        }

        if (outOfRange) {
            input.classList.add('is-invalid');
            if (rangeDisplay) {
                var warning = '⚠ ' + (rangeDisplay.dataset.rangeLabel || rangeDisplay.dataset.currentText || rangeDisplay.dataset.defaultText || '');
                rangeDisplay.textContent = warning;
                rangeDisplay.className = 'score-range-display small text-danger mt-1 font-weight-bold';
            }
            return;
        }

        input.classList.add('is-valid');
        if (rangeDisplay) {
            var successText = rangeDisplay.dataset.rangeLabel || rangeDisplay.dataset.currentText || rangeDisplay.dataset.defaultText || '';
            rangeDisplay.textContent = successText;
            rangeDisplay.className = 'score-range-display small text-success mt-1';
        }
    }

    function toggleSelection(option, focusScore) {
        var radio = option.querySelector('.performance-level-radio');
        if (!radio || radio.disabled) {
            return;
        }

        if (option.classList.contains('selected') && radio.checked) {
            radio.dataset.silentChange = '1';
            radio.checked = false;
            option.classList.remove('selected');
            resetIndicator(option.dataset.indicatorId);
            radio.dispatchEvent(new Event('change', { bubbles: true }));
            delete radio.dataset.silentChange;
            return;
        }

        radio.dataset.silentChange = '1';
        applySelection(option, focusScore);
        radio.dispatchEvent(new Event('change', { bubbles: true }));
        delete radio.dataset.silentChange;
    }

    function bind(root) {
        root = root || document;

        root.querySelectorAll('.gradingform_utbrubrics .performance-levels .level-option').forEach(function(option) {
            if (option.dataset.utbCardsBound) {
                return;
            }
            option.dataset.utbCardsBound = '1';

            option.addEventListener('click', function(event) {
                if (option.dataset.readonly === '1') {
                    return;
                }
                if (event.cancelable) {
                    event.preventDefault();
                }
                if (event.target && event.target.matches('input[type="radio"]')) {
                    return;
                }
                toggleSelection(option, true);
            });

            option.addEventListener('keydown', function(event) {
                if (option.dataset.readonly === '1') {
                    return;
                }
                if (event.key === ' ' || event.key === 'Enter') {
                    event.preventDefault();
                    toggleSelection(option, true);
                }
            });
        });

        root.querySelectorAll('.gradingform_utbrubrics .performance-levels .performance-level-radio').forEach(function(radio) {
            if (radio.dataset.utbCardsBound) {
                return;
            }
            radio.dataset.utbCardsBound = '1';

            radio.addEventListener('change', function() {
                if (radio.dataset.silentChange === '1') {
                    return;
                }

                var option = radio.closest('.level-option');
                if (!option) {
                    return;
                }

                if (radio.checked) {
                    applySelection(option, false);
                } else {
                    option.classList.remove('selected');
                    resetIndicator(option.dataset.indicatorId);
                }
            });
        });

        root.querySelectorAll('.gradingform_utbrubrics .performance-levels .performance-level-radio:checked').forEach(function(radio) {
            var option = radio.closest('.level-option');
            if (option) {
                applySelection(option, false);
            }
        });

        root.querySelectorAll('.gradingform_utbrubrics .score-input').forEach(function(input) {
            if (input.dataset.utbCardsBound) {
                validateScoreInput(input);
                return;
            }

            input.dataset.utbCardsBound = '1';
            
            // Prevent negative numbers by blocking the minus key
            input.addEventListener('keydown', function(e) {
                if (e.key === '-' || e.key === 'Minus') {
                    e.preventDefault();
                }
            });
            
            // Multiple event listeners for real-time validation
            input.addEventListener('input', function() {
                validateScoreInput(input);
            });
            input.addEventListener('keyup', function() {
                validateScoreInput(input);
            });
            input.addEventListener('blur', function() {
                validateScoreInput(input);
            });
            input.addEventListener('paste', function() {
                setTimeout(function() {
                    validateScoreInput(input);
                }, 10);
            });
            
            // Initial validation
            validateScoreInput(input);
        });
    }

    window.gradingformUtbrubricsCardsInit = bind;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            bind(document);
        });
    } else {
        bind(document);
    }
})();
JS;

                $out .= html_writer::script($js);
            } else {
                $out .= html_writer::script('if (window.gradingformUtbrubricsCardsInit) { window.gradingformUtbrubricsCardsInit(document); }');
            }
        }

        return $out;
    }
    
    /**
     * Render grading controls for an indicator
     * Updated for new database structure
     */
    private function render_grading_controls($indicator, $rubric, $mode, $elementname, $current_value) {
        $indicator_id = $indicator['id'];

        $levels = $indicator['levels'] ?? [];
        $selected_level = $this->resolve_selected_level($levels, $current_value ?? []);

        $rawscore = $current_value['score'] ?? null;
        $current_score = '';
        if ($rawscore !== null && $rawscore !== '') {
            $current_score = number_format((float)$rawscore, 2, '.', '');
        }

        $range = null;
        if ($selected_level && isset($selected_level['min']) && isset($selected_level['max'])) {
            $range = [
                'min' => number_format((float)$selected_level['min'], 2, '.', ''),
                'max' => number_format((float)$selected_level['max'], 2, '.', '')
            ];
        } elseif (isset($current_value['minscore']) && isset($current_value['maxscore'])) {
            $range = [
                'min' => number_format((float)$current_value['minscore'], 2, '.', ''),
                'max' => number_format((float)$current_value['maxscore'], 2, '.', '')
            ];
        }

        $default_select_text = get_string('selectlevel', 'gradingform_utbrubrics');
        $current_feedback = $current_value['feedback'] ?? '';
        $student_outcome_id = $indicator['student_outcome_id'] ?? ($indicator['studentoutcomeid'] ?? ($rubric['id'] ?? 0));

        if (!$this->is_grading_mode($mode)) {
            $out = html_writer::start_div('grading-controls grading-summary', ['data-indicator-id' => $indicator_id]);

            $levellabel = trim((string)($current_value['performance_level_name'] ?? ($selected_level['definition'] ?? '')));
            $levelreference = $levellabel !== '' ? $levellabel : ($selected_level['definition'] ?? '');
            $leveltype = $this->resolve_level_type($levelreference);

            $summaryclasses = ['student-grade-result'];

            $out .= html_writer::start_div(implode(' ', $summaryclasses));

            if ($levellabel !== '' || $leveltype !== 'default') {
                $badgeclassmap = [
                    'excellent' => 'badge-success',
                    'good' => 'badge-primary',
                    'fair' => 'badge-warning',
                    'inadequate' => 'badge-danger'
                ];
                $badgeclass = $badgeclassmap[$leveltype] ?? 'badge-secondary';
                
                // Use localized level name if available, otherwise use original label
                $displayname = $leveltype !== 'default' ? $this->get_localized_level_name($leveltype) : $levellabel;
                if (empty($displayname)) {
                    $displayname = $levellabel;
                }
                
                $out .= html_writer::div(
                    html_writer::span(format_string($displayname), 'badge ' . $badgeclass),
                    'achieved-level mb-2'
                );
            }

            if ($current_score !== '') {
                $out .= html_writer::div(
                    html_writer::tag('strong', get_string('score', 'gradingform_utbrubrics') . ': ') .
                    html_writer::span($current_score),
                    'achieved-score mb-2 d-block'
                );
            }

            // Intentionally omit the range display to keep the summary concise

            if (trim((string)$current_feedback) !== '') {
                $formattedfeedback = format_text($current_feedback, FORMAT_MOODLE, [
                    'context' => $this->page->context,
                    'para' => false
                ]);
                $out .= html_writer::div(
                    html_writer::tag('strong', get_string('teacher_feedback', 'gradingform_utbrubrics'), ['class' => 'small text-muted d-block mb-1']) .
                    html_writer::div($formattedfeedback, 'feedback-text'),
                    'teacher-feedback mt-3 text-left'
                );
            }

            if ($levellabel === '' && $current_score === '' && trim((string)$current_feedback) === '') {
                $out .= html_writer::div(
                    html_writer::div(
                        html_writer::span(get_string('notyetgraded', 'gradingform_utbrubrics'), 'badge badge-light border p-2 d-block') .
                        html_writer::tag('small', get_string('indicatornotgraded', 'gradingform_utbrubrics'), ['class' => 'text-muted d-block mt-2']),
                        'text-center'
                    ),
                    'not-graded-indicator'
                );
            }

            $out .= html_writer::end_div(); // student-grade-result

            $out .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $elementname . '[criteria][' . $indicator_id . '][student_outcome_id]',
                'value' => $student_outcome_id
            ]);

            $out .= html_writer::end_div(); // grading-controls
            return $out;
        }

        $rangeobject = $range ? (object)$range : null;
        $range_text = $rangeobject ? get_string('graderange', 'gradingform_utbrubrics', $rangeobject) : $default_select_text;

        $score_attrs = [
            'type' => 'number',
            'name' => $elementname . '[criteria][' . $indicator_id . '][score]',
            'value' => $current_score,
            'class' => 'form-control score-input',
            'step' => '0.01',
            'data-indicator-id' => $indicator_id,
            'placeholder' => ''
        ];

        if ($range) {
            $score_attrs['min'] = $range['min'];
            $score_attrs['max'] = $range['max'];
            $score_attrs['placeholder'] = $range['min'] . ' - ' . $range['max'];
            $score_attrs['title'] = $range_text;
        } else {
            $score_attrs['disabled'] = 'disabled';
        }

        $out = html_writer::start_div('grading-controls', ['data-indicator-id' => $indicator_id]);

        $out .= html_writer::tag('label', get_string('grade_label', 'gradingform_utbrubrics'), ['class' => 'form-label small mb-1']);
        $out .= html_writer::empty_tag('input', $score_attrs);

        $out .= html_writer::div(
            $range_text,
            'score-range-display small text-muted mt-1',
            [
                'data-default-text' => $default_select_text,
                'data-current-text' => $range_text,
                'data-range-label' => $range ? $range_text : ''
            ]
        );

        $out .= html_writer::tag('label', get_string('feedback_label', 'gradingform_utbrubrics'), ['class' => 'form-label small mt-3 mb-1']);
        $out .= html_writer::tag('textarea', s($current_feedback), [
            'name' => $elementname . '[criteria][' . $indicator_id . '][feedback]',
            'class' => 'form-control feedback-input noeditor',
            'rows' => '4',
            'placeholder' => get_string('indicator_feedback_placeholder', 'gradingform_utbrubrics')
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $elementname . '[criteria][' . $indicator_id . '][student_outcome_id]',
            'value' => $student_outcome_id
        ]);

        $out .= html_writer::end_div();

        return $out;
    }

    private function resolve_level_type(?string $definition): string {
        if (empty($definition)) {
            return 'default';
        }

        $needle = \core_text::strtolower(trim($definition));
        $mapping = [
            'excellent' => ['excellent', 'excelente', 'sobresaliente'],
            'good' => ['good', 'bueno', 'satisfactorio'],
            'fair' => ['fair', 'regular', 'aceptable'],
            'inadequate' => ['inadequate', 'inadecuado', 'deficiente']
        ];

        foreach ($mapping as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($needle, $keyword) !== false) {
                    return $type;
                }
            }
        }

        return 'default';
    }

    /**
     * Get localized performance level name based on level type
     */
    private function get_localized_level_name(string $leveltype): string {
        switch ($leveltype) {
            case 'excellent':
                return get_string('level_excellent', 'gradingform_utbrubrics');
            case 'good':
                return get_string('level_good', 'gradingform_utbrubrics');
            case 'fair':
                return get_string('level_fair', 'gradingform_utbrubrics');
            case 'inadequate':
                return get_string('level_inadequate', 'gradingform_utbrubrics');
            default:
                return '';
        }
    }

    private function resolve_selected_level(array $levels, array $current_value): ?array {
        if (empty($levels)) {
            return null;
        }

        $levelid = $current_value['performance_level_id'] ?? null;
        if ($levelid !== null && $levelid !== '') {
            foreach ($levels as $level) {
                if ((string)($level['id'] ?? '') === (string)$levelid) {
                    return $level;
                }
            }
        }

        if (!empty($current_value['performance_level_name'])) {
            $target = \core_text::strtolower(trim($current_value['performance_level_name']));
            foreach ($levels as $level) {
                $definition = \core_text::strtolower(trim($level['definition'] ?? ''));
                if ($definition === $target) {
                    return $level;
                }
            }
        }

        if (isset($current_value['minscore']) && isset($current_value['maxscore'])) {
            $minscore = (float)$current_value['minscore'];
            $maxscore = (float)$current_value['maxscore'];
            foreach ($levels as $level) {
                if (!isset($level['min']) || !isset($level['max'])) {
                    continue;
                }
                if ((float)$level['min'] == $minscore && (float)$level['max'] == $maxscore) {
                    return $level;
                }
            }
        }

        if (isset($current_value['score']) && $current_value['score'] !== '' && $current_value['score'] !== null) {
            $score = (float)$current_value['score'];
            foreach ($levels as $level) {
                if (!isset($level['min']) || !isset($level['max'])) {
                    continue;
                }
                $min = (float)$level['min'];
                $max = (float)$level['max'];
                if ($score >= $min && $score <= $max) {
                    return $level;
                }
            }
        }

        return null;
    }
    
    /**
     * Check if current mode is a grading mode
     */
    private function is_grading_mode($mode) {
        return in_array($mode, [
            gradingform_utbrubrics_controller::DISPLAY_EVAL,
            gradingform_utbrubrics_controller::DISPLAY_EDIT_FULL
        ]);
    }

    private function is_readonly_grade_mode(int $mode): bool {
        return in_array($mode, [
            gradingform_utbrubrics_controller::DISPLAY_EVAL_FROZEN,
            gradingform_utbrubrics_controller::DISPLAY_REVIEW,
            gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT
        ], true);
    }

    private function should_show_grade_column(int $mode, ?array $value = null, ?bool $hasgraded = null): bool {
        if ($this->is_grading_mode($mode)) {
            return true;
        }

        if ($mode === gradingform_utbrubrics_controller::DISPLAY_STUDENT_RESULT) {
            $hasgraded = $hasgraded ?? $this->has_graded_criteria($value);
            if (!$hasgraded) {
                return false;
            }
        }

        if ($this->is_readonly_grade_mode($mode)) {
            return true;
        }

        return false;
    }
    
    /**
     * Display results for student view
     */
    public function display_student_results($rubric, $value, $options) {
        if (empty($value) || empty($value['criteria'])) {
            return html_writer::div(
                get_string('notgradedexplanation', 'gradingform_utbrubrics'),
                'alert alert-info'
            );
        }
        
        $out = html_writer::start_div('gradingform_utbrubrics student-results');
        
        $out .= html_writer::tag('h3', get_string('yourgradingresults', 'gradingform_utbrubrics'));
        $out .= html_writer::tag('p', get_string('resultsexplanation', 'gradingform_utbrubrics'));
        
        // Group evaluations by Student Outcome
        $so_groups = [];
        foreach ($value['criteria'] as $indicator_id => $evaluation) {
            $so_id = $evaluation['student_outcome_id'] ?? 0;
            if (!isset($so_groups[$so_id])) {
                $so_groups[$so_id] = [
                    'so_title' => $evaluation['so_title'] ?? 'Student Outcome',
                    'indicators' => []
                ];
            }
            $so_groups[$so_id]['indicators'][$indicator_id] = $evaluation;
        }
        
        foreach ($so_groups as $so_data) {
            $out .= html_writer::start_div('student-outcome-results');
            $out .= html_writer::tag('h4', $so_data['so_title'], ['class' => 'so-title']);
            
            foreach ($so_data['indicators'] as $indicator_id => $evaluation) {
                $out .= html_writer::start_div('indicator-result card mb-2');
                $out .= html_writer::start_div('card-body');
                
                // Indicator description
                $indicator_text = ($evaluation['indicator'] ?? '') . '. ' . 
                                ($evaluation['indicator_description'] ?? '');
                $out .= html_writer::tag('h6', $indicator_text, ['class' => 'indicator-title']);
                
                // Performance level and score
                if (!empty($evaluation['performance_level_name'])) {
                    $score_text = !empty($evaluation['score']) ? 
                                 ' (' . number_format($evaluation['score'], 2) . ')' : '';
                    $out .= html_writer::tag('p', 
                        html_writer::tag('strong', 'Performance Level: ') . 
                        $evaluation['performance_level_name'] . $score_text,
                        ['class' => 'performance-result']
                    );
                } else {
                    $out .= html_writer::tag('p', 
                        get_string('indicatornotgraded', 'gradingform_utbrubrics'),
                        ['class' => 'not-graded text-muted']
                    );
                }
                
                // Feedback
                if (!empty($evaluation['feedback'])) {
                    $out .= html_writer::tag('div', 
                        html_writer::tag('strong', 'Feedback: ') . $evaluation['feedback'],
                        ['class' => 'feedback-result mt-2']
                    );
                }
                
                $out .= html_writer::end_div(); // card-body
                $out .= html_writer::end_div(); // indicator-result
            }
            
            $out .= html_writer::end_div(); // student-outcome-results
        }
        
        $out .= html_writer::end_div(); // gradingform_utbrubrics student-results
        
        return $out;
    }
}
<?php
/**
 * Database helper functions for UTB Rubrics
 * 
 * These functions provide access to Student Outcomes, indicators, performance levels,
 * and evaluation data storage for the UTB Rubrics grading method.
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get all Student Outcomes
 * 
 * @param string $lang Language (en or es) - if null, auto-detect from current_language()
 * @return array Array of Student Outcomes with their indicators and performance levels
 */
function gradingform_utbrubrics_get_student_outcomes($lang = null) {
    global $DB;
    
    // Auto-detect language if not provided
    if ($lang === null) {
        $current_lang = current_language();
        $lang = ($current_lang == 'es' || $current_lang == 'es_mx' || $current_lang == 'es_co') ? 'es' : 'en';
    }
    
    // Validate language parameter
    $lang = in_array($lang, ['en', 'es']) ? $lang : 'en';
    
    // Get all Student Outcomes
    $title_field = ($lang == 'es') ? 'title_es' : 'title_en';
    $desc_field = ($lang == 'es') ? 'description_es' : 'description_en';
    
    $sql = "SELECT id, so_number, {$title_field} as title, {$desc_field} as description,
                   sortorder, timecreated, timemodified
            FROM {gradingform_utb_outcomes}
            ORDER BY sortorder ASC";
    
    $outcomes = $DB->get_records_sql($sql);
    
    // Convert to indexed array and process indicators
    $result = array();
    foreach ($outcomes as $outcome) {
        $outcome->indicators = gradingform_utbrubrics_get_indicators_for_so($outcome->id, $lang);
        $outcome->outcome_code = $outcome->so_number;
        $result[] = $outcome;
    }
    
    return $result;
}

/**
 * Get all indicators for a Student Outcome
 * 
 * @param int $so_id Student Outcome ID
 * @param string $lang Language (en or es) - if null, auto-detect
 * @return array Array of indicators with their performance levels
 */
function gradingform_utbrubrics_get_indicators_for_so($so_id, $lang = null) {
    global $DB;
    
    // Auto-detect language if not provided
    if ($lang === null) {
        $current_lang = current_language();
        $lang = ($current_lang == 'es' || $current_lang == 'es_mx' || $current_lang == 'es_co') ? 'es' : 'en';
    }
    
    // Validate language parameter
    $lang = in_array($lang, ['en', 'es']) ? $lang : 'en';
    
    $desc_field = ($lang == 'es') ? 'description_es' : 'description_en';
    
    $sql = "SELECT id, student_outcome_id, indicator_letter, 
                   {$desc_field} as description, timecreated, timemodified
            FROM {gradingform_utb_indicators}
            WHERE student_outcome_id = :so_id
            ORDER BY indicator_letter ASC";
    
    $indicators = $DB->get_records_sql($sql, ['so_id' => $so_id]);
    
    // For each indicator, get its available performance levels
    foreach ($indicators as $indicator) {
        $indicator->performance_levels = gradingform_utbrubrics_get_performance_levels_for_indicator($indicator->id, $lang);
    }
    
    return array_values($indicators); // Return as indexed array
}

/**
 * Get all performance levels for an indicator
 * 
 * @param int $indicator_id Indicator ID
 * @param string $lang Language (en or es) - if null, auto-detect
 * @return array Array of performance levels for this indicator
 */
function gradingform_utbrubrics_get_performance_levels_for_indicator($indicator_id, $lang = null) {
    global $DB;
    
    // Auto-detect language if not provided
    if ($lang === null) {
        $current_lang = current_language();
        $lang = ($current_lang == 'es' || $current_lang == 'es_mx' || $current_lang == 'es_co') ? 'es' : 'en';
    }
    
    // Validate language parameter
    $lang = in_array($lang, ['en', 'es']) ? $lang : 'en';
    
    $title_field = ($lang == 'es') ? 'title_es' : 'title_en';
    $desc_field = ($lang == 'es') ? 'description_es' : 'description_en';
    
    $sql = "SELECT id, indicator_id, {$title_field} as level_name, 
                   {$desc_field} as description,
                   minscore, maxscore, sortorder,
                   timecreated, timemodified
            FROM {gradingform_utb_lvl}
            WHERE indicator_id = :indicator_id
            ORDER BY sortorder ASC";
    
    return array_values($DB->get_records_sql($sql, ['indicator_id' => $indicator_id]));
}

/**
 * Get complete structure for definition loading
 * 
 * @param int $definition_id Definition ID
 * @param string $lang Language
 * @return array Complete structure for the selected Student Outcome
 */
function gradingform_utbrubrics_get_complete_structure($definition_id, $lang = 'en') {
    global $DB;
    
    // Get the selected SO from the definition
    $definition = $DB->get_record('grading_definitions', ['id' => $definition_id]);
    if (!$definition) {
        return [];
    }
    
    $options = json_decode($definition->options, true);
    $keyname = $options['keyname'] ?? '';
    
    if (empty($keyname)) {
        return ['keyname' => '', 'criteria' => []];
    }
    
    // Get the Student Outcome data
    $student_outcomes = gradingform_utbrubrics_get_student_outcomes($lang);
    
    foreach ($student_outcomes as $so) {
        if (strtolower($so->so_number) === strtolower($keyname)) {
            // Convert to format expected by renderer.
            $criteria = [];
            foreach ($so->indicators as $indicator) {
                $indicatorid = (int)$indicator->id;
                $levels = [];
                foreach ($indicator->performance_levels as $level) {
                    $levels[] = [
                        'id' => (int)$level->id,
                        'definition' => $level->level_name,
                        'min' => (float)$level->minscore,
                        'max' => (float)$level->maxscore,
                        'description' => $level->description,
                        'sortorder' => isset($level->sortorder) ? (int)$level->sortorder : null
                    ];
                }

                $criteria[] = [
                    'id' => $indicatorid,
                    'indicator' => strtoupper($indicator->indicator_letter),
                    'code' => strtolower($indicator->indicator_letter),
                    'description' => $indicator->description,
                    'student_outcome_id' => (int)$indicator->student_outcome_id,
                    'sortorder' => isset($indicator->sortorder) ? (int)$indicator->sortorder : null,
                    'levels' => $levels
                ];
            }

            return [
                'keyname' => $keyname,
                'title' => $so->title,
                'description' => $so->description,
                'student_outcome_id' => (int)$so->id,
                'so_number' => $so->so_number,
                'criteria' => $criteria
            ];
        }
    }
    
    return ['keyname' => $keyname, 'criteria' => []];
}

/**
 * Ensure data is initialized for definition
 * 
 * @param int $definition_id Definition ID
 * @param string $selected Selected SO key
 */
function gradingform_utbrubrics_ensure_data_initialized($definition_id, $selected) {
    global $DB;
    
    // Update the definition options to store the selected SO
    $definition = $DB->get_record('grading_definitions', ['id' => $definition_id]);
    if ($definition) {
        $options = json_decode($definition->options, true) ?: [];
        $options['keyname'] = $selected;
        
        $DB->update_record('grading_definitions', [
            'id' => $definition_id,
            'options' => json_encode($options),
            'timemodified' => time()
        ]);
    }
}

/**
 * Get all performance levels
 * 
 * @param string $lang Language (en or es)
 * @return array Array of all performance levels
 */
function gradingform_utbrubrics_get_all_performance_levels($lang = 'en') {
    global $DB;
    
    $title_field = ($lang == 'es') ? 'title_es' : 'title_en';
    $desc_field = ($lang == 'es') ? 'description_es' : 'description_en';
    
    $sql = "SELECT id, {$title_field} as title, 
                   {$desc_field} as description,
                   minscore, maxscore, sortorder,
                   timecreated, timemodified
            FROM {gradingform_utb_lvl}
            ORDER BY sortorder ASC";
    
    return $DB->get_records_sql($sql);
}

/**
 * Save evaluation data to the database
 * 
 * @param array $evaluation_data Evaluation data
 * @return bool|int False on failure, evaluation ID on success
 */
function gradingform_utbrubrics_save_evaluation($evaluation_data) {
    global $DB;
    
    $required_fields = ['instanceid', 'studentid', 'courseid', 'activityid', 'activityname', 
                       'student_outcome_id', 'indicator_id'];
    
    // Validate required fields
    foreach ($required_fields as $field) {
        if (!isset($evaluation_data[$field])) {
            return false;
        }
    }
    
    $record = new stdClass();
    $record->instanceid = $evaluation_data['instanceid'];
    $record->studentid = $evaluation_data['studentid'];
    $record->courseid = $evaluation_data['courseid'];
    $record->activityid = $evaluation_data['activityid'];
    $record->activityname = $evaluation_data['activityname'];
    $record->student_outcome_id = $evaluation_data['student_outcome_id'];
    $record->indicator_id = $evaluation_data['indicator_id'];
    $record->performance_level_id = $evaluation_data['performance_level_id'] ?? null;
    $record->score = $evaluation_data['score'] ?? null;
    $record->feedback = $evaluation_data['feedback'] ?? '';
    $record->timecreated = $evaluation_data['timecreated'] ?? time();
    $record->timemodified = time();
    
    // Check if evaluation already exists for this instance and indicator
    $existing = $DB->get_record('gradingform_utb_evaluations', [
        'instanceid' => $record->instanceid,
        'indicator_id' => $record->indicator_id
    ]);
    
    if ($existing) {
        // Update existing record
        $record->id = $existing->id;
        $record->timecreated = $existing->timecreated; // Keep original creation time
        return $DB->update_record('gradingform_utb_evaluations', $record);
    } else {
        // Insert new record
        return $DB->insert_record('gradingform_utb_evaluations', $record);
    }
}

/**
 * Get evaluations for a grading instance
 * 
 * @param int $instanceid Grading instance ID
 * @param string $lang Language (en or es)
 * @return array Array of evaluations with full details
 */
function gradingform_utbrubrics_get_evaluations_for_instance($instanceid, $lang = 'en') {
    global $DB;
    
    $sql = "SELECT e.*, 
                   so.so_number, so.title_{$lang} as so_title, so.description_{$lang} as so_description,
                   i.indicator_letter, i.description_{$lang} as indicator_description,
                   l.title_{$lang} as performance_level_name, 
                   l.description_{$lang} as performance_level_description,
                   l.minscore, l.maxscore
            FROM {gradingform_utb_evaluations} e
            JOIN {gradingform_utb_outcomes} so ON so.id = e.student_outcome_id
            JOIN {gradingform_utb_indicators} i ON i.id = e.indicator_id
            LEFT JOIN {gradingform_utb_lvl} l ON l.id = e.performance_level_id
            WHERE e.instanceid = :instanceid
            ORDER BY so.sortorder ASC, i.indicator_letter ASC";
    
    return $DB->get_records_sql($sql, ['instanceid' => $instanceid]);
}

/**
 * Get available Student Outcomes for selection in edit form
 * 
 * @param string $lang Language (en or es)
 * @return array Array of SO options for select elements
 */
function gradingform_utbrubrics_get_so_options($lang = 'en') {
    // This would normally come from a settings table, but for now return the standard 7 SOs
    $options = [];
    
    for ($i = 1; $i <= 7; $i++) {
        $key = "so{$i}";
        $title_key = "so{$i}_title";
        $option_key = "so{$i}_option";
        
        $title = get_string($title_key, 'gradingform_utbrubrics');
        $option = get_string($option_key, 'gradingform_utbrubrics');
        
        $options[$key] = $option;
    }
    
    return $options;
}

/**
 * Delete all evaluation data for a definition
 * 
 * @param int $definitionid The grading definition ID
 * @return bool Success
 */
function gradingform_utbrubrics_delete_definition_data($definitionid) {
    global $DB;
    
    try {
        $transaction = $DB->start_delegated_transaction();
        
        // Get all Student Outcomes for this definition
        $student_outcomes = $DB->get_records('gradingform_utb_outcomes', 
                                           ['definitionid' => $definitionid]);
        
        foreach ($student_outcomes as $so) {
            // Get all indicators for this SO
            $indicators = $DB->get_records('gradingform_utb_indicators', 
                                         ['student_outcome_id' => $so->id]);
            
            foreach ($indicators as $indicator) {
                // Delete evaluations for this indicator
                $DB->delete_records('gradingform_utb_evaluations', 
                                  ['indicator_id' => $indicator->id]);
                
                // Delete level ranges for this indicator
                $DB->delete_records('gradingform_utb_lvl', 
                                  ['indicator_id' => $indicator->id]);
            }
            
            // Delete indicators
            $DB->delete_records('gradingform_utb_indicators', 
                              ['student_outcome_id' => $so->id]);
        }
        
        // Delete student outcomes
        $DB->delete_records('gradingform_utb_outcomes', 
                          ['definitionid' => $definitionid]);
        
        $transaction->allow_commit();
        return true;
        
    } catch (Exception $e) {
        $transaction->rollback($e);
        return false;
    }
}
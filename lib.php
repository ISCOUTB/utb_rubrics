<?php
/**
 * Grading method controller for the UTB Rubrics plugin
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/form/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/grade/grading/form/utbrubrics/db/helper_functions.php');

const UTBRUBRICS = 'utbrubrics';

class gradingform_utbrubrics_controller extends gradingform_controller {
    // Display modes copied from rubric/guide as baseline; can be refined.
    const DISPLAY_EDIT_FULL     = 1;  // Create/edit templates selection
    const DISPLAY_EDIT_FROZEN   = 2;  // Preview read-only.
    const DISPLAY_PREVIEW       = 3;  // Preview for managers.
    const DISPLAY_PREVIEW_GRADED= 8;  // Preview with sample grades.
    const DISPLAY_EVAL          = 4;  // Evaluate a student.
    const DISPLAY_EVAL_FROZEN   = 5;  // Evaluate read-only.
    const DISPLAY_REVIEW        = 6;  // Teacher reviews filled forms.
    const DISPLAY_VIEW          = 7;  // Student view.
    const DISPLAY_STUDENT_RESULT= 9;  // Student viewing their graded results.

    /**
     * Adds settings navigation link.
     * 
     * @param settings_navigation $settingsnav Settings navigation object
     * @param navigation_node|null $node Navigation node
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {
        $node->add(get_string('defineutbrubrics', 'gradingform_utbrubrics'),
            $this->get_editor_url(), settings_navigation::TYPE_CUSTOM,
            null, null, new pix_icon('icon', '', 'gradingform_utbrubrics'));
    }

    /**
     * Extends the module navigation for users without manage capabilities to preview the definition.
     * 
     * @param global_navigation $navigation Global navigation object
     * @param navigation_node|null $node Navigation node
     */
    public function extend_navigation(global_navigation $navigation, navigation_node $node=null) {
        if (has_capability('moodle/grade:managegradingforms', $this->get_context())) {
            return;
        }
        if ($this->is_form_defined() && ($options = $this->get_options()) && !empty($options['alwaysshowdefinition'])) {
            $node->add(get_string('gradingof', 'gradingform_utbrubrics', get_grading_manager($this->get_areaid())->get_area_title()),
                new moodle_url('/grade/grading/form/'.$this->get_method_name().'/preview.php', array('areaid' => $this->get_areaid())),
                settings_navigation::TYPE_CUSTOM);
        }
    }

    /**
     * Loads the UTB rubrics form definition using the database structure.
     */
    protected function load_definition() {
        global $DB;
        
        // Load main definition.
        $sql = "SELECT gd.* FROM {grading_definitions} gd WHERE gd.areaid = :areaid AND gd.method = :method";
        $params = ['areaid' => $this->areaid, 'method' => $this->get_method_name()];
        $record = $DB->get_record_sql($sql, $params);
        if (!$record) {
            $this->definition = false;
            return;
        }
        
        $this->definition = new stdClass();
        foreach (['id', 'name', 'description', 'descriptionformat', 'status', 'copiedfromid', 'timecreated', 'usercreated', 'timemodified', 'usermodified', 'timecopied', 'options'] as $f) {
            $this->definition->$f = $record->$f;
        }

        // Load UTB rubrics structure using database schema.
        $lang = (current_language() == 'es' || current_language() == 'es_mx' || current_language() == 'es_co') ? 'es' : 'en';
        $this->definition->utbrubric = gradingform_utbrubrics_get_complete_structure($this->definition->id, $lang);
    }

    /**
     * Get plugin display options with defaults.
     */
    public static function get_default_options() {
        return [
            'alwaysshowdefinition' => 1,
            'showscoreteacher' => 1,
            'showscorestudent' => 1
        ];
    }

    public function get_options() {
        $options = self::get_default_options();
        if (!empty($this->definition->options)) {
            $thisoptions = json_decode($this->definition->options, true);
            if (is_array($thisoptions)) {
                foreach ($thisoptions as $k => $v) { $options[$k] = $v; }
            }
        }
        return $options;
    }

    /**
     * Save or check definition - delegates to update_or_check_utbrubrics.
     */
    public function update_definition(stdClass $newdefinition, $usermodified = null) {
        $this->update_or_check_utbrubrics($newdefinition, $usermodified, true);
        // Mark for regrade if requested via form.
        if (!empty($newdefinition->utbrubrics['regrade'])) {
            $this->mark_for_regrade();
        }
    }

    /**
     * Check/save changes for UTB rubrics using database structure.
     */
    public function update_or_check_utbrubrics(stdClass $newdefinition, $usermodified = null, $doupdate = false) {
        global $DB;

        if ($this->definition === false) {
            if (!$doupdate) { return 5; }
            parent::update_definition(new stdClass(), $usermodified);
            parent::load_definition();
        }
        
        if (!isset($newdefinition->utbrubrics['options'])) {
            $newdefinition->utbrubrics['options'] = self::get_default_options();
        }
        
        // Extract selected keyname 
        $selected = isset($newdefinition->utbrubrics['keyname']) ? $newdefinition->utbrubrics['keyname'] : '';
        if (empty($selected) && isset($newdefinition->utbrubrics['selected'])) {
            $selected = $newdefinition->utbrubrics['selected'];
        }
        
        // Ensure keyname is saved in options
        if (!empty($selected)) {
            $newdefinition->utbrubrics['options']['keyname'] = $selected;
        }
        
        $newdefinition->options = json_encode($newdefinition->utbrubrics['options']);
        $editoroptions = self::description_form_field_options($this->get_context());
        $newdefinition = file_postupdate_standard_editor($newdefinition, 'description', $editoroptions, $this->get_context(),
            'grading', 'description', $this->definition->id);

        $currentdefinition = $this->get_definition(true);
        $haschanges = array();

        // Initialize data if needed
        if ($doupdate && !empty($selected)) {
            // Ensure default data is initialized
            gradingform_utbrubrics_ensure_data_initialized($this->definition->id, $selected);
            $haschanges[5] = true; // Significant change
        }

        // Save core definition fields if changed
        foreach (array('status','description','descriptionformat','name','options') as $key) {
            if (isset($newdefinition->$key) && (!isset($this->definition->$key) || $newdefinition->$key != $this->definition->$key)) {
                $haschanges[1] = true;
            }
        }
        if ($usermodified && (!isset($this->definition->usermodified) || $usermodified != $this->definition->usermodified)) {
            $haschanges[1] = true;
        }
        
        if (!count($haschanges)) { return 0; }
        if ($doupdate) {
            parent::update_definition($newdefinition, $usermodified);
            $this->load_definition();
        }
        $levels = array_keys($haschanges); 
        sort($levels); 
        return array_pop($levels);
    }

    /**
     * Converts the current definition into an object suitable for the editor form's set_data().
     */
    public function get_definition_for_editing($addemptycriterion = false) {
        $definition = $this->get_definition();
        $properties = new stdClass();
        $properties->areaid = $this->areaid;
        if ($definition) {
            foreach (['id','name','description','descriptionformat','status'] as $key) {
                if (isset($definition->$key)) { $properties->$key = $definition->$key; }
            }
            $options = self::description_form_field_options($this->get_context());
            $properties = file_prepare_standard_editor($properties, 'description', $options, $this->get_context(),
                'grading', 'description', $definition->id);
        }
        
        $properties->utbrubrics = [
            'options' => $this->get_options(),
        ];
        
        // Add selected rubric information if it exists
        if ($definition && !empty($definition->utbrubric)) {
            $properties->utbrubrics['selected'] = $definition->utbrubric['keyname'] ?? '';
            $properties->utbrubrics['criteria'] = $definition->utbrubric['criteria'] ?? [];
            $properties->utbrubrics['title'] = $definition->utbrubric['title'] ?? '';
            $properties->utbrubrics['description'] = $definition->utbrubric['description'] ?? '';
        }
        
        // Get available Student Outcomes for selection
        $lang = current_language() == 'es' ? 'es' : 'en';
        $properties->utbrubrics['available_sos'] = gradingform_utbrubrics_get_so_options($lang);
        
        return $properties;
    }

    /**
     * Returns the plugin renderer.
     */
    public function get_renderer(moodle_page $page) {
        return $page->get_renderer('gradingform_'.$this->get_method_name());
    }

    /**
     * Renders a preview of the UTB rubrics definition.
     */
    public function render_preview(moodle_page $page) {
        if (!$this->is_form_defined()) {
            throw new coding_exception('Form is not defined');
        }
        $options = $this->get_options();
        $output = $this->get_renderer($page);
        $showdescription = has_capability('moodle/grade:managegradingforms', $page->context)
            ? true
            : !empty($options['alwaysshowdefinition']);
        $html = '';
        if ($showdescription) {
            $html .= $output->box($this->get_formatted_description(), 'gradingform_utbrubrics-description');
        }
        $mode = has_capability('moodle/grade:managegradingforms', $page->context)
            ? self::DISPLAY_PREVIEW
            : self::DISPLAY_STUDENT_RESULT;
        
        // For student view, try to get existing grading data
        $value = null;
        if ($mode == self::DISPLAY_STUDENT_RESULT) {
            global $USER, $DB;
            
            // Try to find existing grading instance for this student
            $grading_manager = get_grading_manager($this->get_areaid());
            $context = $grading_manager->get_context();
            
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cm = get_coursemodule_from_id(null, $context->instanceid);
                
                if ($cm) {
                    $itemid = null;
                    
                    // Handle different module types
                    if ($cm->modname == 'assign') {
                        // For assignments, find the grade record for this user
                        $grade_record = $DB->get_record('assign_grades', 
                            ['assignment' => $cm->instance, 'userid' => $USER->id]);
                        
                        if ($grade_record) {
                            // The itemid in grading_instances corresponds to the grade ID
                            $itemid = $grade_record->id;
                        }
                    } else {
                        // For other modules, use more direct approach based on grade structure
                        $sql = "SELECT gi.*, gg.userid 
                                FROM {grading_instances} gi 
                                JOIN {assign_grades} ag ON gi.itemid = ag.id
                                WHERE gi.definitionid = ? AND ag.userid = ?";
                        $instances = $DB->get_records_sql($sql, [$this->definition->id, $USER->id]);
                        
                        if ($instances) {
                            $instance_record = reset($instances);
                            $itemid = $instance_record->itemid;
                        }
                    }
                    
                    // If we found an itemid, get the grading data
                    if ($itemid) {
                        $instances = $DB->get_records('grading_instances', 
                            ['definitionid' => $this->definition->id, 'itemid' => $itemid], 
                            'timemodified DESC');
                        
                        if ($instances) {
                            // Get the most recent instance
                            $instance_record = reset($instances);
                            $instance = $this->get_instance($instance_record);
                            $filling = $instance->get_utbrubrics_filling();
                            
                            if (!empty($filling['criteria'])) {
                                $value = $filling;
                            }
                        }
                    }
                }
            }
            
            // Fallback: Direct query to find student's grading data
            if (!$value) {
                // Direct approach: find grading instances that belong to this user
                $sql = "SELECT gi.* 
                        FROM {grading_instances} gi 
                        JOIN {assign_grades} ag ON gi.itemid = ag.id 
                        WHERE gi.definitionid = ? AND ag.userid = ?
                        ORDER BY gi.timemodified DESC";
                $user_instances = $DB->get_records_sql($sql, [$this->definition->id, $USER->id]);
                
                if ($user_instances) {
                    $instance_record = reset($user_instances);
                    $instance = $this->get_instance($instance_record);
                    $filling = $instance->get_utbrubrics_filling();
                    
                    if (!empty($filling['criteria'])) {
                        $value = $filling;
                    }
                }
            }
        }
        
        $html .= $output->display_definition($this->definition->utbrubric, $options, $mode, 'utbrubrics', $value);
        return $html;
    }

    /**
     * Deletes the UTB rubrics definition and all associated data.
     */
    protected function delete_plugin_definition() {
        global $DB;
        
        // Get instances for this definition
        $instances = array_keys($DB->get_records('grading_instances', ['definitionid' => $this->definition->id], '', 'id'));
        if ($instances) {
            // Delete evaluations for these instances
            $DB->delete_records_list('gradingform_utb_eval', 'instanceid', $instances);
            // Delete the instances themselves
            $DB->delete_records_list('grading_instances', 'id', $instances);
        }
        
        // Note: Student Outcomes, indicators, and performance levels are shared/global
        // and should NOT be deleted when a grading definition is removed.
        // Only delete evaluation data that is specific to this definition's instances.
    }

    /**
     * Returns existing instance or creates a new one (supports drafts like rubric).
     */
    public function get_or_create_instance($instanceid, $raterid, $itemid) {
        global $DB;
        if ($instanceid && ($instance = $DB->get_record('grading_instances', ['id'=>$instanceid, 'raterid'=>$raterid, 'itemid'=>$itemid], '*', IGNORE_MISSING))) {
            return $this->get_instance($instance);
        }
        if ($itemid && $raterid) {
            $params = ['definitionid'=>$this->definition->id, 'raterid'=>$raterid, 'itemid'=>$itemid];
            if ($rs = $DB->get_records('grading_instances', $params, 'timemodified DESC', '*', 0, 1)) {
                $record = reset($rs);
                // Always return existing instance if it has data, regardless of status
                $instance = $this->get_instance($record);
                $filling = $instance->get_utbrubrics_filling();
                if (!empty($filling['criteria'])) {
                    $record->isrestored = true;
                    return $instance;
                }
                
                $current = $this->get_current_instance($raterid, $itemid);
                if ($record->status == gradingform_utbrubrics_instance::INSTANCE_STATUS_INCOMPLETE && (!$current || $record->timemodified > $current->get_data('timemodified'))) {
                    $record->isrestored = true;
                    return $this->get_instance($record);
                }
            }
        }
        return $this->create_instance($raterid, $itemid);
    }

    /**
     * Returns HTML for the grading element shown in the grader UI.
     */
    public function render_grading_element($page, $gradingformelement) {
        if (!$gradingformelement->_flagFrozen) {
            $mode = self::DISPLAY_EVAL;
        } else {
            $mode = $gradingformelement->_persistantFreeze ? self::DISPLAY_EVAL_FROZEN : self::DISPLAY_REVIEW;
        }
        $value = $gradingformelement->getValue();
        if ($value === null || empty($value)) {
            // Try to get existing data for this grading element
            $gradinginstance = $gradingformelement->gradingattributes['gradinginstance'] ?? null;
            if ($gradinginstance && method_exists($gradinginstance, 'get_utbrubrics_filling')) {
                $filling = $gradinginstance->get_utbrubrics_filling();
                if (!empty($filling['criteria'])) {
                    $value = $filling;
                }
            }
        }
        if ($value === null) {
            $value = [];
        }
        $options = $this->get_options();
        $html = '';
        $html .= $this->get_renderer($page)->display_definition($this->definition->utbrubric, $options, $mode, $gradingformelement->getName(), $value);
        return $html;
    }

    /**
     * Returns method name (folder name) used by the base controller to locate URLs.
     */
     public function get_method_name() { return 'utbrubrics'; }

    /**
     * Returns the display name of the plugin for UI purposes.
     * Uses the pluginname string from language files.
     * 
     * @return string The localized plugin name
     */
    public static function get_plugin_name() {
        return get_string('pluginname', 'gradingform_utbrubrics');
    }

    /**
     * Return default description editor options (copied from rubric).
     */
    public static function description_form_field_options($context) {
        global $CFG;
        return array('context'=>$context, 'maxfiles'=>0, 'maxbytes'=>0, 'trusttext'=>true, 'subdirs'=>false);
    }

    /**
     * Get translated text for multilingual content.
     * 
     * @param string $langKey The language string key
     * @param string $fallbackText Fallback text if translation is not found
     * @return string The translated text or fallback
     */
    public static function get_translated_text($langKey, $fallbackText = '') {
        if (empty($langKey)) {
            return $fallbackText;
        }
        
        try {
            // Get the translation
            $translated = get_string($langKey, 'gradingform_utbrubrics');
            return $translated;
        } catch (Exception $e) {
            // If there's any error getting the string, return fallback or error message
            if (!empty($fallbackText)) {
                return $fallbackText;
            }
            // In clean version, we should have all translations
            return "Missing translation: {$langKey}";
        }
    }

    /**
     * Define a minimal preset of the 7 UTB Student Outcomes. This can be later moved to lang files or JSON.
     * Each level has min/max range, and a textual label.
     */
    public static function get_preset_rubric($key) {
        $all = self::get_all_presets();
        return isset($all[$key]) ? $all[$key] : null;
    }

    public static function get_all_presets() {
        // Detect current language automatically
        $current_lang = current_language();
        $lang = ($current_lang == 'es' || $current_lang == 'es_mx' || $current_lang == 'es_co') ? 'es' : 'en';
        
        // Get all Student Outcomes from the database using helper function
        $student_outcomes = gradingform_utbrubrics_get_student_outcomes($lang);
        $presets = array();
        
        foreach ($student_outcomes as $so) {
            $key = strtolower($so->so_number); // Use so_number (SO1, SO2, etc.)
            
            $criteria = array();
            
            foreach ($so->indicators as $indicator) {
                $levels = array();
                foreach ($indicator->performance_levels as $level) {
                    $levels[] = array(
                        'definition' => $level->level_name,
                        'min' => floatval($level->minscore),
                        'max' => floatval($level->maxscore),
                        'description' => $level->description
                    );
                }
                
                $criteria[] = array(
                    'id' => strtolower($indicator->indicator_letter),
                    'description' => $indicator->description,
                    'levels' => $levels
                );
            }
            
            $presets[$key] = array(
                'title' => $so->title,
                'description' => $so->description,
                'criteria' => $criteria
            );
        }
        
        return $presets;
    }
}

/**
 * Instance handler for UTB rubrics grading.
 */
class gradingform_utbrubrics_instance extends gradingform_instance {

    /** @var array|null cache of fillings */
    protected $filling = null;

    /**
     * Fetch evaluations from DB using new structure.
     */
    public function get_utbrubrics_filling($force = false) {
        global $DB;
        if ($this->filling === null || $force) {
            $lang = current_language() == 'es' ? 'es' : 'en';
            $evaluations = gradingform_utbrubrics_get_evaluations_for_instance($this->get_id(), $lang);
            
            $this->filling = ['criteria' => []];
            foreach ($evaluations as $eval) {
                // Convert to format expected by renderer
                $this->filling['criteria'][$eval->indicator_id] = [
                    'id' => $eval->id,
                    'instanceid' => $eval->instanceid,
                    'criterionid' => $eval->indicator_id, // Using indicator_id as criterionid for compatibility
                    'indicator_id' => $eval->indicator_id,
                    'student_outcome_id' => $eval->student_outcome_id,
                    'performance_level_id' => $eval->performance_level_id,
                    'score' => $eval->score,
                    'feedback' => $eval->feedback,
                    'so_title' => $eval->so_title,
                    'indicator_description' => $eval->indicator_description,
                    'performance_level_name' => $eval->performance_level_name,
                    'minscore' => $eval->minscore,
                    'maxscore' => $eval->maxscore
                ];
            }
        }
        return $this->filling;
    }

    /**
     * Snapshot for render fallback.
     */
    public function get_form_data_snapshot() {
        return $this->get_utbrubrics_filling();
    }

    /**
     * Determines whether the submitted form was empty.
     */
    public function is_empty_form($elementvalue) {
        $definition = $this->get_controller()->get_definition();
        $structure = $definition->utbrubric['criteria'] ?? [];

        if (empty($structure)) {
            return true;
        }

        $criteriavalue = [];
        if (!empty($elementvalue['criteria']) && is_array($elementvalue['criteria'])) {
            $criteriavalue = $elementvalue['criteria'];
        }

        $hasinput = false;
        foreach ($structure as $indicator) {
            if (!is_array($indicator) || !isset($indicator['id'])) {
                continue;
            }

            $indicatorid = (string)$indicator['id'];
            $criteriondata = $criteriavalue[$indicatorid] ?? [];
            if (!is_array($criteriondata)) {
                $criteriondata = [];
            }

            $haslevel = isset($criteriondata['performance_level_id']) && $criteriondata['performance_level_id'] !== '' && $criteriondata['performance_level_id'] !== null;
            $hasscore = array_key_exists('score', $criteriondata) && trim((string)$criteriondata['score']) !== '';
            $hasfeedback = array_key_exists('feedback', $criteriondata) && trim((string)$criteriondata['feedback']) !== '';

            if ($haslevel || $hasscore || $hasfeedback) {
                $hasinput = true;
                break;
            }
        }

        if ($hasinput) {
            return false;
        }

        if (!empty($elementvalue['submissionflag'])) {
            return false;
        }

        return empty($criteriavalue);
    }

    /**
     * Clears attempt evaluations for the provided data.
     */
    public function clear_attempt($data) {
        global $DB;
        foreach ($data['criteria'] as $indicator_id => $record) {
            $DB->delete_records('gradingform_utb_eval', [
                'indicator_id' => $indicator_id, 
                'instanceid' => $this->get_id()
            ]);
        }
    }

    /**
     * Validate the grading element data.
     */
    public function validate_grading_element($elementvalue) {
        $definition = $this->get_controller()->get_definition();
        $structure = $definition->utbrubric['criteria'] ?? [];

        if (empty($structure)) {
            return false;
        }

        if (empty($elementvalue['criteria']) || !is_array($elementvalue['criteria'])) {
            return false;
        }

        foreach ($structure as $indicator) {
            if (!is_array($indicator) || !isset($indicator['id'])) {
                continue;
            }

            $indicatorid = (string)$indicator['id'];
            $criterion = $elementvalue['criteria'][$indicatorid] ?? null;

            if (!is_array($criterion)) {
                return false;
            }

            $levelid = $criterion['performance_level_id'] ?? null;
            if ($levelid === null || $levelid === '') {
                return false;
            }

            $levelinfo = null;
            if (!empty($indicator['levels']) && is_array($indicator['levels'])) {
                foreach ($indicator['levels'] as $candidate) {
                    if ((string)($candidate['id'] ?? '') === (string)$levelid) {
                        $levelinfo = $candidate;
                        break;
                    }
                }
            }

            if (empty($levelinfo)) {
                return false;
            }

            if (!array_key_exists('score', $criterion) || trim((string)$criterion['score']) === '') {
                return false;
            }

            $score = unformat_float($criterion['score']);
            if (!is_numeric($score)) {
                return false;
            }

            $min = isset($levelinfo['min']) ? (float)$levelinfo['min'] : null;
            $max = isset($levelinfo['max']) ? (float)$levelinfo['max'] : null;

            if (($min !== null && $score < $min) || ($max !== null && $score > $max)) {
                return false;
            }
        }

        return true;
    }

    public function default_validation_error_message() {
        return get_string('validationerror', 'gradingform_utbrubrics');
    }

    /**
     * Update the instance data using database structure.
     */
    public function update($data) {
        global $DB;

        $criteriadata = isset($data['criteria']) && is_array($data['criteria']) ? $data['criteria'] : [];
        $validationpayload = ['criteria' => $criteriadata];
        if (isset($data['submissionflag'])) {
            $validationpayload['submissionflag'] = $data['submissionflag'];
        }

        if (!$this->is_empty_form($validationpayload) && !$this->validate_grading_element($validationpayload)) {
            throw new moodle_exception('validationerror', 'gradingform_utbrubrics');
        }

        $current = $this->get_utbrubrics_filling();
        parent::update($data);

        // Get context information for evaluations.
        $contextinfo = $this->get_evaluation_context_info();
        $definition = $this->get_controller()->get_definition();
        $structure = $definition->utbrubric['criteria'] ?? [];
        $processed = [];
        $now = time();

        foreach ($structure as $indicator) {
            if (!is_array($indicator) || !isset($indicator['id'])) {
                continue;
            }

            $indicatorid = (int)$indicator['id'];
            $indicatorkey = (string)$indicatorid;
            $criterioninput = $criteriadata[$indicatorkey] ?? [];

            $studentoutcomeid = $criterioninput['student_outcome_id'] ?? ($indicator['student_outcome_id'] ?? null);
            if (empty($studentoutcomeid)) {
                // Without a Student Outcome we cannot persist the evaluation.
                continue;
            }

            $levelid = $criterioninput['performance_level_id'] ?? null;
            $score = null;
            if (array_key_exists('score', $criterioninput) && trim((string)$criterioninput['score']) !== '') {
                $score = unformat_float($criterioninput['score']);
            }
            $feedback = array_key_exists('feedback', $criterioninput) ? trim((string)$criterioninput['feedback']) : '';

            $evaldata = [
                'instanceid' => $this->get_id(),
                'studentid' => $contextinfo['studentid'],
                'courseid' => $contextinfo['courseid'],
                'activityid' => $contextinfo['activityid'],
                'activityname' => $contextinfo['activityname'],
                'student_outcome_id' => (int)$studentoutcomeid,
                'indicator_id' => $indicatorid,
                'performance_level_id' => $levelid !== null && $levelid !== '' ? (int)$levelid : null,
                'score' => $score,
                'feedback' => $feedback,
                'timecreated' => $now
            ];

            $saveresult = gradingform_utbrubrics_save_evaluation($evaldata);
            if ($saveresult !== false) {
                $processed[] = $indicatorid;
            }
        }

        $structurelookup = [];
        foreach ($structure as $key => $indicator) {
            if (is_array($indicator) && isset($indicator['id'])) {
                $structurelookup[(int)$indicator['id']] = true;
            } else {
                $structurelookup[(int)$key] = true;
            }
        }

        if (!empty($current['criteria'])) {
            foreach ($current['criteria'] as $indicatorid => $record) {
                $indicatorid = (int)$indicatorid;
                $existsindefinition = isset($structurelookup[$indicatorid]);
                $shouldremove = !$existsindefinition || (!empty($processed) && !in_array($indicatorid, $processed, true));

                if ($shouldremove) {
                    $DB->delete_records('gradingform_utb_eval', [
                        'instanceid' => $this->get_id(),
                        'indicator_id' => $indicatorid
                    ]);
                }
            }
        }

        // Refresh cache.
        $this->get_utbrubrics_filling(true);
    }
    
    /**
     * Get evaluation context information (student, course, activity)
     */
    private function get_evaluation_context_info() {
        global $DB, $USER;

        $itemid = $this->get_data('itemid');
        $context = [
            'studentid' => 0,
            'courseid' => 0,
            'activityid' => 0,
            'activityname' => ''
        ];

        if (!$itemid) {
            return $context;
        }

        $controller = $this->get_controller();
        $gradingmanager = get_grading_manager($controller->get_areaid());
        $gradingcontext = $gradingmanager->get_context();

        if ($gradingcontext->contextlevel == CONTEXT_MODULE) {
            try {
                $cm = get_coursemodule_from_id(null, $gradingcontext->instanceid);
            } catch (Exception $e) {
                $cm = null;
            }

            if ($cm) {
                $context['courseid'] = (int)$cm->course;
                $context['activityid'] = (int)$cm->id;
                $context['activityname'] = (string)$cm->name;

                switch ($cm->modname) {
                    case 'assign':
                        $grade = $DB->get_record('assign_grades', ['id' => $itemid], 'id, assignment, userid', IGNORE_MISSING);
                        if ($grade) {
                            $context['studentid'] = (int)$grade->userid;
                        } else {
                            $submission = $DB->get_record('assign_submission', ['id' => $itemid], 'id, userid', IGNORE_MISSING);
                            if ($submission) {
                                $context['studentid'] = (int)$submission->userid;
                            }
                        }
                        break;
                    case 'workshop':
                        $assessment = $DB->get_record('workshop_assessments', ['id' => $itemid], 'id, submissionid', IGNORE_MISSING);
                        if ($assessment) {
                            $submission = $DB->get_record('workshop_submissions', ['id' => $assessment->submissionid], 'id, authorid', IGNORE_MISSING);
                            if ($submission) {
                                $context['studentid'] = (int)$submission->authorid;
                            }
                        } else {
                            $submission = $DB->get_record('workshop_submissions', ['id' => $itemid], 'id, authorid', IGNORE_MISSING);
                            if ($submission) {
                                $context['studentid'] = (int)$submission->authorid;
                            }
                        }
                        break;
                    default:
                        $gradegrade = $DB->get_record('grade_grades', ['id' => $itemid], 'id, userid', IGNORE_MISSING);
                        if ($gradegrade) {
                            $context['studentid'] = (int)$gradegrade->userid;
                        }
                        break;
                }
            }
        }

        if (empty($context['studentid'])) {
            $existingstudent = $DB->get_field('gradingform_utb_eval', 'studentid', ['instanceid' => $this->get_id()], IGNORE_MISSING);
            if ($existingstudent) {
                $context['studentid'] = (int)$existingstudent;
            }
        }

        if (empty($context['studentid'])) {
            // As a last resort, avoid returning zero so the record can be stored.
            $context['studentid'] = (int)$USER->id;
        }

        return $context;
    }

    /**
     * Calculate the final grade mapped to the grade range based on rubric score.
     */
    public function get_grade() {
        $controller = $this->get_controller();
        $definition = $controller->get_definition();
        $fill = $this->get_utbrubrics_filling();
        $graderange = array_keys($controller->get_grade_range());
        if (empty($graderange)) { return -1; }
        sort($graderange);
        $mingrade = $graderange[0];
        $maxgrade = $graderange[count($graderange)-1];

        $sumscores = 0.0;
        $summax = 0.0;
        $gradedcount = 0;

        if ($definition->utbrubric && is_array($definition->utbrubric) && isset($definition->utbrubric['criteria'])) {
            foreach ($definition->utbrubric['criteria'] as $cid => $crit) {
                if (!is_array($crit)) {
                    continue;
                }

                $indicatorid = isset($crit['id']) ? (int)$crit['id'] : (int)$cid;
                $fillkey = null;
                if (isset($fill['criteria'][$indicatorid])) {
                    $fillkey = $indicatorid;
                } else if (isset($fill['criteria'][(string)$indicatorid])) {
                    $fillkey = (string)$indicatorid;
                }

                if ($fillkey === null || !isset($fill['criteria'][$fillkey]['score'])) {
                    continue;
                }

                $score = (float)$fill['criteria'][$fillkey]['score'];

                $critmax = 0.0;
                if (!empty($crit['levels']) && is_array($crit['levels'])) {
                    foreach ($crit['levels'] as $lev) {
                        if (!is_array($lev)) {
                            continue;
                        }
                        if (isset($lev['max'])) {
                            $critmax = max($critmax, (float)$lev['max']);
                        }
                    }
                }

                if ($critmax <= 0) {
                    continue;
                }

                $sumscores += $score;
                $summax += $critmax;
                $gradedcount++;
            }
        }

        if ($gradedcount === 0 || $summax <= 0) {
            return -1;
        }

        $ratio = $sumscores / $summax;
        $ratio = min(max($ratio, 0.0), 1.0);

        $final = $mingrade + ($maxgrade - $mingrade) * $ratio;
        return $controller->get_allow_grade_decimals() ? $final : round($final, 0);
    }

    /**
     * Render the grading element.
     */
    public function render_grading_element($page, $gradingformelement) {
        // Ensure current filling data is passed to the form element
        if (!$gradingformelement->getValue()) {
            $filling = $this->get_utbrubrics_filling();
            if (!empty($filling['criteria'])) {
                // Set the current filling as the form value so it displays correctly
                $gradingformelement->setValue($filling);
            }
        }
        
        // Delegate to controller like rubric does.
        return $this->get_controller()->render_grading_element($page, $gradingformelement);
    }
}

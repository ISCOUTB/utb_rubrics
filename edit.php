<?php

/**
 * Editor for UTB rubrics selection.
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/grade/grading/lib.php');

$areaid = required_param('areaid', PARAM_INT);

$manager = get_grading_manager($areaid);
list($context, $course, $cm) = get_context_info_array($manager->get_context()->id);

require_login($course, true, $cm);
require_capability('moodle/grade:managegradingforms', $context);

$controller = $manager->get_controller('utbrubrics');

$PAGE->set_url(new moodle_url('/grade/grading/form/utbrubrics/edit.php', array('areaid' => $areaid)));
$PAGE->set_title(get_string('defineutbrubrics', 'gradingform_utbrubrics'));
$PAGE->set_heading(get_string('defineutbrubrics', 'gradingform_utbrubrics'));

// Get presets from database
$presets = gradingform_utbrubrics_controller::get_all_presets();

$options = $controller->get_options();
$returnurl = optional_param('returnurl', $manager->get_management_url(), PARAM_LOCALURL);

// Get current selection (single rubric only)
$currentselection = '';
if ($controller->is_form_defined()) {
    $definition = $controller->get_definition();
    $options = json_decode($definition->options ?? '{}', true);
    if (!empty($options['keyname'])) {
        $currentselection = $options['keyname'];
    }
}

if (optional_param('save', 0, PARAM_BOOL) && confirm_sesskey()) {
    $selected = optional_param('selected', '', PARAM_ALPHANUMEXT);

    // Validate selection
    if (empty($selected)) {
        $error = get_string('noselectedrubrics', 'gradingform_utbrubrics');
    } else {
        $def = new stdClass();
        $def->id = $controller->get_definition()->id ?? null;
        $def->name = get_string('pluginname', 'gradingform_utbrubrics');
        $def->status = gradingform_controller::DEFINITION_STATUS_READY;
        $def->utbrubrics = [
            'keyname' => $selected,  // String identifier (so1, so2, etc.)
            'selected' => $selected, // For update_definition compatibility
            'options'  => [
                'keyname' => $selected,  // Store keyname in options as well
                'alwaysshowdefinition' => 1,
                'showscoreteacher' => 1,
                'showscorestudent' => 1
            ],
        ];
        $controller->update_definition($def);
        redirect(new moodle_url($returnurl));
    }
}

// Keep session alive.
\core\session\manager::keepalive();

echo $OUTPUT->header();

// Display any errors
if (!empty($error)) {
    echo $OUTPUT->notification($error, 'error');
}

// Add enhanced CSS for professional styling
echo html_writer::tag('style', '
    .utb-selection-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(135deg, #05a0a0 0%, #037f7f 100%);
        color: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 300;
    }
    
    .page-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .grading-ranges {
        margin: 20px 0;
        padding: 20px;
        background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .grading-ranges h4 {
        margin: 0 0 15px 0;
        color: #2d3436;
        font-size: 18px;
        font-weight: 600;
    }
    
    .grade-ranges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .grade-range-item {
        background: rgba(255,255,255,0.9);
        padding: 12px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .grade-range-level {
        font-weight: bold;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .grade-range-score {
        color: #636e72;
        font-size: 13px;
    }
    

    
    .rubrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    
    .utb-rubric-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
        border: 2px solid transparent;
    }
    
    .utb-rubric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .utb-rubric-card.selected {
        border-color: #05a0a0;
        box-shadow: 0 4px 20px rgba(5,160,160,0.3);
    }
    
    .utb-rubric-card.selected::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #05a0a0, #06b6b6);
    }
    
    .card-header {
        padding: 20px 20px 15px 20px;
        border-bottom: 1px solid #eee;
        position: relative;
    }
    
    .card-checkbox {
        position: absolute;
        top: 15px;
        right: 15px;
        transform: scale(1.3);
        accent-color: #05a0a0;
    }
    
    .rubric-title {
        font-size: 18px;
        font-weight: 600;
        color: #2d3436;
        margin: 0 40px 8px 0;
        line-height: 1.3;
    }
    
    .rubric-subtitle {
        color: #636e72;
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .rubric-description {
        color: #636e72;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 15px;
    }
    
    .rubric-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: #636e72;
    }
    
    .stat-icon {
        width: 16px;
        height: 16px;
        background: #ddd;
        border-radius: 50%;
        display: inline-block;
    }
    

    
    .form-actions {
        text-align: center;
        margin-top: 40px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #05a0a0 0%, #06b6b6 100%);
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        color: white;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        margin-right: 15px;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(5,160,160,0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary-custom {
        background: white;
        border: 2px solid #ddd;
        padding: 10px 28px;
        border-radius: 25px;
        color: #636e72;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary-custom:hover {
        border-color: #05a0a0;
        color: #05a0a0;
        text-decoration: none;
    }
    
    .selection-counter {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #05a0a0;
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        box-shadow: 0 4px 15px rgba(5,160,160,0.3);
        font-weight: 500;
        z-index: 1000;
        transition: all 0.3s;
    }
    
    .selection-counter.hidden {
        transform: translateY(100px);
        opacity: 0;
    }
    
    @media (max-width: 768px) {
        .rubrics-grid {
            grid-template-columns: 1fr;
        }
        
        .grade-ranges-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .utb-selection-container {
            padding: 10px;
        }
    }
');

echo html_writer::start_div('utb-selection-container');

// Page header
echo html_writer::start_div('page-header');
echo html_writer::tag('h2', get_string('studentoutcomestitle', 'gradingform_utbrubrics'));
echo html_writer::tag('p', get_string('selectstudentoutcome', 'gradingform_utbrubrics'));
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'utb-rubrics-form']);
echo html_writer::input_hidden_params(new moodle_url('', ['sesskey' => sesskey(), 'save' => 1]));

// Rubrics grid container
echo html_writer::start_div('rubrics-grid');

// Enhanced rubric cards
foreach ($presets as $key => $preset) {
    $isSelected = ($key === $currentselection);
    $cardClass = 'utb-rubric-card' . ($isSelected ? ' selected' : '');
    
    echo html_writer::start_div($cardClass);
    
    // Card header
    echo html_writer::start_div('card-header');
    
    // Radio button for single selection
    $radioattrs = [
        'type' => 'radio',
        'name' => 'selected',
        'value' => $key,
        'id' => 'chk_' . $key,
        'class' => 'card-checkbox'
    ];
    if ($isSelected) {
        $radioattrs['checked'] = 'checked';
    }
    echo html_writer::empty_tag('input', $radioattrs);
    
    // Title and subtitle
    echo html_writer::tag('h3', format_string($preset['title']), ['class' => 'rubric-title']);
    
    // Extract number from key (so1 -> 1)
    $so_number = preg_replace('/[^0-9]/', '', $key);
    $subtitle = 'Student Outcome ' . $so_number . ' (' . strtoupper($key) . ')';
    echo html_writer::tag('p', $subtitle, ['class' => 'rubric-subtitle']);
    
    echo html_writer::end_div();
    
    // Card body
    echo html_writer::start_div('card-body');
    
    // Description
    $description = $preset['description'] ?? 'UTB Student Outcome que evalúa las competencias y habilidades específicas de los estudiantes de ingeniería.';
    echo html_writer::div(format_string($description), 'rubric-description');
    
    // Stats
    echo html_writer::start_div('rubric-stats');
    $indicator_count = count($preset['criteria']);
    
    $indicator_text = ($indicator_count == 1) ? 
        get_string('indicator', 'gradingform_utbrubrics') :
        get_string('indicators', 'gradingform_utbrubrics');
    
    echo html_writer::div(
        html_writer::span('', 'stat-icon') . 
        html_writer::span($indicator_count . ' ' . $indicator_text, ''),
        'stat-item'
    );
    
    // Count performance levels from first criterion (all should have same number)
    $levelCount = !empty($preset['criteria']) && !empty($preset['criteria'][0]['levels']) 
        ? count($preset['criteria'][0]['levels']) 
        : 4;
    
    $level_text = ($levelCount == 1) ?
        get_string('performancelevel', 'gradingform_utbrubrics') :
        get_string('performancelevels', 'gradingform_utbrubrics');

    echo html_writer::div(
        html_writer::span('', 'stat-icon') . 
        html_writer::span($levelCount . ' ' . $level_text, ''),
        'stat-item'
    );
    echo html_writer::end_div();
    
    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // utb-rubric-card
}

echo html_writer::end_div(); // rubrics-grid

// Enhanced submit buttons
echo html_writer::start_div('form-actions');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('savechanges'),
    'class' => 'btn-primary-custom'
]);
echo html_writer::link($returnurl, get_string('cancel'), ['class' => 'btn-secondary-custom']);
echo html_writer::end_div();

echo html_writer::end_tag('form');

// Selection indicator
echo html_writer::div(get_string('norubricselected', 'gradingform_utbrubrics'), 'selection-counter hidden', ['id' => 'selection-counter']);

echo html_writer::end_div(); // utb-selection-container

// Prepare localized strings for JavaScript
$js_strings = json_encode([
    'norubricselected' => get_string('norubricselected', 'gradingform_utbrubrics'),
    'rubricselectedprefix' => get_string('rubricselectedprefix', 'gradingform_utbrubrics'),
    'mustselectrubric' => get_string('mustselectrubric', 'gradingform_utbrubrics')
]);

// Enhanced JavaScript for professional UX
echo html_writer::script("
var utbStrings = $js_strings;
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('.card-checkbox');
    const selectionCounter = document.getElementById('selection-counter');
    
    function updateSelectionDisplay() {
        let selectedRubric = null;
        
        radioButtons.forEach(radio => {
            const container = radio.closest('.utb-rubric-card');
            if (radio.checked) {
                container.classList.add('selected');
                selectedRubric = radio.value;
            } else {
                container.classList.remove('selected');
            }
        });
        
        // Update selection counter
        if (selectedRubric) {
            const selectedCard = document.querySelector('#chk_' + selectedRubric).closest('.utb-rubric-card');
            const rubricTitle = selectedCard.querySelector('.rubric-title').textContent;
            selectionCounter.textContent = utbStrings.rubricselectedprefix + rubricTitle;
            selectionCounter.classList.remove('hidden');
            selectionCounter.style.background = '#05a0a0';
        } else {
            selectionCounter.textContent = utbStrings.norubricselected;
            selectionCounter.classList.add('hidden');
        }
    }
    
    function addCardClickHandler() {
        radioButtons.forEach(radio => {
            const card = radio.closest('.utb-rubric-card');
            card.addEventListener('click', function(e) {
                // Only trigger if not clicking on radio button
                if (!e.target.matches('input')) {
                    radio.click();
                }
            });
        });
    }
    
    function addSmoothAnimations() {
        // Add staggered animation for cards
        const cards = document.querySelectorAll('.utb-rubric-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    // Event listeners
    radioButtons.forEach(radio => {
        radio.addEventListener('change', updateSelectionDisplay);
    });
    
    // Initialize
    updateSelectionDisplay();
    addCardClickHandler();
    addSmoothAnimations();
    
    // Form validation
    const form = document.querySelector('.utb-rubrics-form');
    form.addEventListener('submit', function(e) {
        const selectedCount = document.querySelectorAll('.card-checkbox:checked').length;
        if (selectedCount === 0) {
            e.preventDefault();
            alert(utbStrings.mustselectrubric);
            return false;
        }
    });
});
");

echo $OUTPUT->footer();

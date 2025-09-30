<?php
/**
 * Upgrade script for UTB Rubrics grading method.
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UTB rubrics grading method upgrade function.
 *
 * This is the first public release (v1.0.0), so no upgrade logic is needed.
 * Future versions will add upgrade paths here as needed.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool true (always successful for v1.0.0)
 */
function xmldb_gradingform_utbrubrics_upgrade($oldversion) {
    // No upgrade logic needed for the first version (1.0.0)
    // Future version upgrades will be added here when needed
    
    return true;
}
<?php
defined('MOODLE_INTERNAL') || die();

class block_statsgreeting extends block_base
{
  public function init()
  {
    $this->title = get_string('pluginname', 'block_statsgreeting');
  }

  public function get_content()
  {
    global $USER, $DB, $OUTPUT, $CFG;

    if ($this->content !== null) {
      return $this->content;
    }

    $this->content = new stdClass;

    $courses_enrolled = $DB->count_records_sql("
            SELECT COUNT(DISTINCT e.courseid)
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE ue.userid = ?", array($USER->id));

    $courses_completed = $DB->count_records_sql("
            SELECT COUNT(DISTINCT c.id)
            FROM {course_completions} cc
            JOIN {course} c ON c.id = cc.course
            WHERE cc.userid = ? AND cc.timecompleted IS NOT NULL", array($USER->id));

    $activities_completed = $DB->count_records_sql("
            SELECT COUNT(DISTINCT cmc.id)
            FROM {course_modules_completion} cmc
            JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
            WHERE cmc.userid = ? AND cmc.completionstate = 1", array($USER->id));

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    $time = time();
    $activities_due = $DB->count_records_sql("
            SELECT COUNT(DISTINCT a.id)
            FROM {assign} a
            JOIN {course_modules} cm ON cm.instance = a.id
            JOIN {modules} m ON m.id = cm.module
            JOIN {course} c ON c.id = cm.course
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = ?
            WHERE m.name = 'assign'
            AND ue.userid = ?
            AND a.duedate > 0
            AND a.duedate > ?
            AND (s.id IS NULL OR s.status <> 'submitted')", array($USER->id, $USER->id, $time));

    $hour = date('G');
    if ($hour >= 5 && $hour < 12) {
      $greeting = get_string('morning', 'block_statsgreeting');
    } elseif ($hour >= 12 && $hour < 18) {
      $greeting = get_string('afternoon', 'block_statsgreeting');
    } else {
      $greeting = get_string('evening', 'block_statsgreeting');
    }

    $data = [
      'greeting' => $greeting,
      'date' => date('F j, Y'),
      'courses_enrolled' => $courses_enrolled,
      'courses_completed' => $courses_completed,
      'activities_completed' => $activities_completed,
      'activities_due' => $activities_due,
      'courses_icon' => $OUTPUT->pix_icon('i/course', ''),
      'completion_icon' => $OUTPUT->pix_icon('i/completion', ''),
      'activity_icon' => $OUTPUT->pix_icon('i/activities', ''),
      'calendar_icon' => $OUTPUT->pix_icon('i/calendar', ''),
    ];

    $this->content->text = $OUTPUT->render_from_template('block_statsgreeting/main', $data);

    return $this->content;
  }

  public function get_required_javascript()
  {
    parent::get_required_javascript();
    $this->page->requires->css('/blocks/statsgreeting/styles.css');
  }
}

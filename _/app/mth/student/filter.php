<?php

class mth_student_filter
{

  protected $grade_levels = [];
  protected $school_year = null;
  protected $shedule_statuses = [];
  protected $homerooms = [];
  protected $grade = null;
  protected $students = [];
  protected $xdays = null;
  protected $notice = null; //1 for first notice, 2 for final notice

  protected function setValue($field, $value)
  {
    if (!is_array($value)) {
      $value = array($value);
    }
    if (empty($value)) {
      $value = [-1];
    }
    $this->$field = array_map(
      array('core_db', 'escape'),
      array_filter(
        array_map(
          'trim',
          $value
        ),
        function ($value) {
          return !is_null($value);
        }
      )
    );
  }

  public function setLessGrade($value)
  {
    $this->setValue('grade', $value);
  }

  public function setHomeRoom(array $value)
  {
    $this->setValue('homerooms', $value);
  }

  public function setGradeLevel(array $value)
  {
    $this->setValue('grade_levels', $value);
  }

  public function setSchoolYear($value)
  {
    $this->setValue('school_year', $value);
  }

  public function setScheduleStatus(array $value)
  {
    $this->setValue('shedule_statuses', $value);
  }

  public function setDaysNotLogin($value)
  {
    $this->setValue('xdays', $value);
  }

  public function setNotice($value)
  {
    $this->setValue('notice', $value);
  }

  public function setStudents(array $value)
  {
    $this->setValue('students', $value);
  }

  protected function _having()
  {
    $having = [];
    if ($this->grade) {
      $having[] = "grade<={$this->grade[0]}";
    }

    if ($this->notice) {
      $having[] = $this->notice[0] == 1 ? "offense_count = 0" : "offense_count > 0";
    }

    return count($having) > 0 ? "HAVING " . implode(" AND ", $having) : '';
  }

  protected function getQueryResult()
  {

    $having = $this->_having();

    $homeroom = $this->homerooms ? ('AND sh.homeroom_canvas_course_id in(' . implode(',', $this->homerooms) . ')') : '';
    $grade_level = $this->grade_levels ? ("AND sgl.grade_level in(" . implode(',', $this->grade_levels) . ")") : '';
    $school_year = $this->school_year ? "AND sc.school_year_id={$this->school_year[0]} AND sgl.school_year_id={$this->school_year[0]}" : '';
    $status = $this->shedule_statuses ? "AND sc.status in(" . implode(',', $this->shedule_statuses) . ")" : '';
    $students = $this->students ? "AND s.student_id in(" . implode(',', $this->students) . ")" : '';

    $sql = "select s.student_id as id,p.first_name,p.gender,p.last_name,sgl.grade_level,sc.school_year_id,
		(
			select name from mth_homeroom as h
			where sh.homeroom_canvas_course_id=h.canvas_course_id limit 1
		) as 'homeroom',
		(
			select email from mth_person as mp 
			inner join mth_parent as mp2 on mp2.person_id=mp.person_id
			where mp2.parent_id=s.parent_id
		) as 'parent_email',
		(
			select zero_count from mth_canvas_enrollment as ce
			where ce.canvas_user_id=cu.canvas_user_id  limit 1
		) as zero_count,
		(
			select grade from mth_canvas_enrollment as ce
			where ce.canvas_user_id=cu.canvas_user_id limit 1
		) as grade,
		(select count(*) from mth_offense_notif as ot 
			where mth_student_id=s.student_id and school_year_id=sc.school_year_id
		) as offense_count,
		(select date_created from mth_offense_notif as ot 
			where mth_student_id=s.student_id and school_year_id=sc.school_year_id limit 1
		) as last_notice,
		cu.last_login
		from mth_student as s
		inner join mth_schedule as sc  on sc.student_id=s.student_id
		left join mth_person as p on p.person_id=s.person_id
		inner join mth_student_grade_level as sgl on sgl.student_id=s.student_id
		inner join mth_canvas_user as cu on cu.mth_person_id=p.person_id
		left join mth_student_homeroom as sh on sh.student_id=s.student_id
		left join mth_schedule_period as sp on sp.schedule_id=sc.schedule_id
		where sp.course_type=1 and sp.period=1
		$school_year $status
		$grade_level
		$homeroom
		$students
		$having";
    return core_db::runQuery($sql);
  }

  public function getStudents($report = false)
  {
    $result = $this->getQueryResult();
    if (!$result) {
      error_log('Error in filter query: ' . print_r($this, true));
      return;
    }
    $students = $report ? [
      [
        'First Name', 'Last Name', 'Gender', 'Grade Level', 'Homeroom', 'Parent Email', '# of 0', 'Homeroom Grade', 'Last Login'
      ]
    ] : [];

    while ($r = &$result->fetch_object()) {
      if ($this->xdays && strtotime($r->last_login) > strtotime("-" . $this->xdays[0] . " days")) {
        continue;
      }
      $r->last_login = $r->last_login ? date('m/d/Y', strtotime($r->last_login)) : null;

      $students[] = $report ? [
        $r->first_name,
        $r->last_name,
        $r->gender,
        $r->grade_level,
        $r->homeroom,
        $r->parent_email,
        $r->zero_count,
        round($r->grade, 2),
        $r->last_login
      ] : $r;
    }
    $result->free_result();
    return $students;
  }
}

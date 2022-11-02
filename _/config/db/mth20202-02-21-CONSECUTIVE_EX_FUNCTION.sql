USE infocenter;
drop function if exists GET_CONSECUTIVE_EX;

DELIMITER //
CREATE FUNCTION  GET_CONSECUTIVE_EX(ssid INTEGER,yid INTEGER)
RETURNS INTEGER
BEGIN
	declare consecutive_count int;
	set consecutive_count = 0;
		select count(*) into consecutive_count from
		(select @prev as prev,IF(@prev=excused,'YES',NULL) as consecutive,@prev:=excused
		from yoda_student_assessments as ysa
		inner join mth_student as ms on ms.person_id = ysa.person_id 
		inner join yoda_teacher_assessments as yta on yta.id=ysa.assessment_id
		inner join yoda_student_homeroom as ysh on ysh.yoda_course_id=yta.course_id
		where ms.student_id=ssid and ysh.student_id=ms.student_id 
		and ysh.school_year_id=yid
		and ysa.id = (select max(id) from yoda_student_assessments where person_id=ysa.person_id and assessment_id=ysa.assessment_id)
		order by ysa.id) as llog where consecutive is not null;
	
	return consecutive_count;
END
//

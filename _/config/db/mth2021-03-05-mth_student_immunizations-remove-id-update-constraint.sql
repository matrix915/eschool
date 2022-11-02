ALTER TABLE `mth_student_immunizations`
    DROP COLUMN `id`;

ALTER TABLE `mth_student_immunizations` 
    ADD CONSTRAINT student_immunization_pk PRIMARY KEY (`student_id`,`immunization_id`)
<ul class="sf-menu MainMenu cms_nav">
    <li><a href="/home">Home</a></li>
    <?php

    if (($parent = mth_parent::getByUser())):
        $students = $parent->getStudents();
        foreach ($students as $student):
            /* @var $student mth_student */
            ?>
            <li>
                <a href="/student/<?= $student->getSlug() ?>"><?= $student->getPreferredFirstName() ?></a>
            </li>
            <?php
        endforeach;
    endif; //if parent
    ?>
    <li><a href="/student/new" style="font-style: italic">Add New Student</a></li>
</ul>
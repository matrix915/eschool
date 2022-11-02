<?php

use mth\yoda\settings;

/**
 * Static methods for my tech high user handling
 *
 * @author abe
 */
class mth_user
{
    const L_STUDENT = 1;
    const L_PARENT = 2;
    const L_TEACHER = 3;
    const L_TEACHER_ASSISTANT = 4;

    const L_ADMIN = 10;
    const L_SUB_ADMIN = 9;

    public static function isStudent(core_user $user = NULL)
    {
        if (!$user && !($user = core_user::getCurrentUser())) {
            return false;
        }
        return $user->getLevel() == self::L_STUDENT;
    }

    public static function isParent(core_user $user = NULL)
    {
        if (!$user && !($user = core_user::getCurrentUser())) {
            return false;
        }
        return $user->getLevel() >= self::L_PARENT;
    }

    public static function isTeacher(core_user $user = NULL)
    {
        if (!$user && !($user = core_user::getCurrentUser())) {
            return false;
        }
        return $user->getLevel() >= self::L_TEACHER;
    }

    public static function isAssistant(core_user $user = NULL)
    {
        if (!$user && !($user = core_user::getCurrentUser())) {
            return false;
        }
        return $user->getLevel() >= self::L_TEACHER_ASSISTANT;
    }

    public static function isSubAdmin(core_user $user = NULL)
    {
        if (!$user && !($user = core_user::getCurrentUser())) {
            return false;
        }
        return $user->getLevel() >= self::L_SUB_ADMIN;
    }

    /**
     * execute before core_secure::userFun();
     */
    public static function preFun()
    {
        $isCASurl = strpos($_SERVER['REQUEST_URI'], '/_/cas') === 0;
        if (!req_post::bool('password') || !req_post::bool('email')) {
            return;
        }
        $userLoggingIn = core_user::findUser(req_post::txt('email'));
        if (!$userLoggingIn) {
            $userLoggingIn = self::newStudentUser();
        }
        if ($userLoggingIn && !$userLoggingIn->checkPassword(req_post::raw('password'))
            && ($dob = strtotime(req_post::txt('password')))
            && $userLoggingIn->checkPassword((string)$dob)
        ) {
            $_POST['password'] = (string)$dob; //default password for a new student is a unix timestamp of their birthdate
            self::set_requirePasswordChange(true);
        }
        if (!$isCASurl && $userLoggingIn) {
            self::adminFun($userLoggingIn);
        }
    }

    /**
     * execute after core_secure::userFun();
     */
    public static function postFun()
    {
        $isCASurl = strpos($_SERVER['REQUEST_URI'], '/_/cas') === 0;
        if (self::isStudent()) {
            if (self::requirePasswordChange()) {
                core_loader::printPage(core_path::getPath('/_/user/set-password'));
                exit();
            }
        }
    }

    protected static function adminFun(core_user $user)
    {
        $_SERVER['REQUEST_URI'] = $user->getHomeUrl();
    }

    /**
     * Finds user by email, checks if the password has the students birthdate, creates user account
     * @return core_user core_user of new account or FALSE on failure
     */
    protected static function newStudentUser()
    {
        if (!($bdayPass = strtotime(req_post::txt('password')))
            || !($student = mth_student::getByEmail(req_post::txt('email')))
            || $student->getDateOfBirth() != $bdayPass
        ) {
            return false;
        }
        if ($student->makeUser()) {
            self::set_requirePasswordChange(true);
            return core_user::findUser(req_post::txt('email'));
        }
        return false;
    }

    public static function requirePasswordChange()
    {
        if (!($user = core_user::getCurrentUser())) {
            return false;
        }
        if (($requirePasswordChange = &$_SESSION[core_config::getCoreSessionVar()][__CLASS__]['requirePasswordChange'])) {
            return true;
        }
        if (($student = mth_student::getByEmail($user->getEmail()))
            && $user->checkPassword((string)$student->getDateOfBirth())
        ) {
            return true;
        }
        return false;
    }


    public static function set_requirePasswordChange($bool)
    {
        core_secure::startSession();
        $_SESSION[core_config::getCoreSessionVar()][__CLASS__]['requirePasswordChange'] = (bool)$bool;
    }
}

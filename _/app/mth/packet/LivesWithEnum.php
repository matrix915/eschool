<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 1/25/17
 * Time: 3:30 PM
 */

namespace mth\packet;


use core\BasicEnum;

class LivesWithEnum extends BasicEnum
{

    const One_Parent = 1;
    const Two_Parents = 2;
    const One_Parent_Plus = 3;
    const Other_Adult = 4;
    const Alone = 5;
    const Non_Parent = 6;

    protected static $labels = [
        self::One_Parent => '1 parent',
        self::Two_Parents => '2 parents',
        self::One_Parent_Plus => '1 parent & another adult',
        self::Other_Adult => 'a relative, friend(s) or other adult(s)',
        self::Alone => 'alone with no adults',
        self::Non_Parent => 'an adult that is not the parent or the legal guardian'
    ];
}
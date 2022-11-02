<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 1/25/17
 * Time: 3:23 PM
 */

namespace mth\packet;


use core\BasicEnum;

class LivingLocationEnum extends BasicEnum
{
    const Shelter = 1;
    const Multi_Family = 2;
    const Campground = 3;
    const Hotel = 4;
    const None_of_the_above = 5;

    protected static $labels = [
        self::Shelter =>'in a shelter, transitional housing, or awaiting foster care',
        self::Multi_Family =>'with more than one family in a house or an apartment due to loss of housing or economic hardship',
        self::Campground=>'In a temporary trailer, campground, car, or park',
        self::Hotel=>'In a hotel or motel',
        self::None_of_the_above=>'Choices above do not apply (skip question #2)'
    ];
}
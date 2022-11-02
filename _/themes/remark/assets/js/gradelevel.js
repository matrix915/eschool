/**
 * Rex Cambarijan
 * Jquery dependent Gradelevel filter
 */
$(function () {
    /**
     * Grade selector should be the checkboxes
     * [All Grades: 'gAll', k-8: 'gKto8', 9-12: 'g9to12']
     */
    var _selector = '.grade_selector';
    /**
     * Fieldset which contains the grade_selector and all grades level checkboxes(k-12)
     * grade_level checkboxes value is it's own grade level
     */
    var _container = '.grade-levels-block';

    var GradeLevel = new function () {
        var groups = {
            gAll: [],
            gKto8: [],
            g9to12: []
        };
        var $target = null;
        var $list = null;
        var selected = [];


        this.init = function (target) {
            $target = target;
            $list = $target.find('.grade-level-list :checkbox');
            groups.gAll = $list;
            groups.gKto8 = $list.filter(function () {
                return $(this).val() === 'OR-K' || $(this).val() === 'K' || ($(this).val() * 1) < 9;
            });
            groups.g9to12 = $list.filter(function () {
                return ($(this).val() * 1) >= 9;
            });
            return this;
        }

        this.check = function (group_name) {
            $.inArray(group_name, selected) == -1 && selected.push(group_name);
            groups[group_name].each(function (key, val) {
                $(this).prop('checked', true);
            });
        }

        this.uncheck = function (group_name) {

            selected = selected.filter(function (group) {
                return group != group_name;
            });


            groups[group_name].each((key, val) => {
                var $cb = $(val);
                if (selected.length > 0 && this._isNotSelected($cb)) {
                    $cb.prop('checked', false);
                }

                if (selected.length == 0) {
                    $cb.prop('checked', false);
                }
            });
        }

        this._isNotSelected = function ($cb) {
            var notSelected = true;
            $.each(selected, function (key, value) {
                groups[value].each(function (key, value) {
                    if ($cb.val() == $(this).val()) {
                        notSelected = false;
                        return false;
                    }

                });
            });
            return notSelected;
        }

    };

    var $grade_selector = $(_selector);
    var $grade_level_block = $(_container);
    var grade_level = GradeLevel.init($grade_level_block);

    $grade_selector.click(function () {
        var $this = $(this);
        if ($this.is(':checked')) {
            grade_level.check($this.val());
        } else {
            grade_level.uncheck($this.val());
        }
    });
});
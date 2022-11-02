/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


var mth_address_geocoder = new google.maps.Geocoder();
function mth_address_validator(field, cityName) {
    if (mth_address_validator.checked === undefined) {
        mth_address_validator.checked = {};
    }
    var value = $.trim(field.value);
    var type = 'zip code';
    if (field.id.indexOf('city') > -1) {
        if ($(field).is(':focus')) {
            return false;
        }
        type = 'city';
        value += ', UT';
    } else if (field.id == 'parent-address-zip') {
        return !isNaN(value);
    } else if (cityName !== undefined && cityName.length > 1) {
        value = cityName + ', UT ' + value;
    }
    if (mth_address_validator.checked[field.id] !== undefined
        && mth_address_validator.checked[field.id] === value) {
        return true;
    }
    mth_address_validator.checked[field.id] = value;
    mth_address_geocoder.geocode(
        {'address': value},
        function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                var formattedResults = {};
                for (var r in results) {
                    formattedResults[r] = {};
                    for (var c in results[r].address_components) {
                        if (!results[r].address_components[c].types || !results[r].address_components[c].types[0]) {
                            continue;
                        }
                        formattedResults[r][results[r].address_components[c].types[0]] = results[r].address_components[c].long_name;
                    }
                }
                for (r in formattedResults) {
                    if (formattedResults[r].administrative_area_level_1 !== 'Utah'
                        || formattedResults[r].locality === undefined
                        || formattedResults[r].route !== undefined
                        || formattedResults[r].street_address !== undefined
                        || formattedResults[r].intersection !== undefined
                        || formattedResults[r].airport !== undefined
                        || formattedResults[r].park !== undefined
                        || formattedResults[r].point_of_interest !== undefined) {
                        continue;
                    }
                    if (type === 'city') {
                        var possibleCities = [];
                        if ($.trim(field.value).toLowerCase() === formattedResults[r].locality.toLowerCase()) {
                            mth_address_validator.checked[field.id] = formattedResults[r].locality + ', UT';
                            field.value = formattedResults[r].locality;
                            $(field).valid();
                            return;
                        }
                        if (possibleCities.indexOf(formattedResults[r].locality) < 0) {
                            possibleCities.push(formattedResults[r].locality);
                        }
                    } else {
                        if ($.trim(field.value) === formattedResults[r].postal_code
                            || (formattedResults[r].postal_code === undefined && formattedResults[r].locality
                            && $.trim(cityName).toLowerCase() === formattedResults[r].locality.toLowerCase())) {
                            $(field).valid();
                            return;
                        }
                    }
                }

                
                if (type === 'city' && possibleCities && possibleCities.length > 0) {
                    var cityList = '';
                    for (c in possibleCities) {
                        if ((typeof possibleCities[c]) !== 'string') {
                            continue;
                        }
                        cityList += '<p><a onclick="$(\'#' + field.id + '\').val(\'' + possibleCities[c] + '\'); global_popup_close(\'mth_address_city_select\');">' + possibleCities[c] + '</a></p>';
                    }
                    cityList += '<hr><p><a onclick="global_popup_close(\'mth_address_city_select\'); swal(\'\',\'Please make sure the city entered is a valid Utah city\',\'warning\',);">Use what I entered</a></p>';
                    var cityPopUp = $('#mth_address_city_select');
                    if (cityPopUp.length < 1) {
                        cityPopUp = $('<div></div>', {'id': 'mth_address_city_select'}).appendTo($('body'));
                    }
                    cityPopUp.html('<p>By ' + field.value + ' did you mean:</p>' + cityList);
                    global_popup('mth_address_city_select');
                }
                $(field).valid();
            } else {
                swal('','We were unable to verify the ' + type + ' you entered. Please double check it and make sure it is a valid Utah ' + type , 'error');
            }
        }
    );
    return false;
}

$(function () {
    if ($('.mth-address-zip.mth-address-geo-check').length > 0) {
        $('.mth-address-zip').each(function () {
            $(this).change(function () {
                return mth_address_validator(this, $('#' + this.id.replace('zip', 'city')).val());
            });
        });
        $('.mth-address-city').each(function () {
            $(this).change(function () {
                return mth_address_validator(this);
            });
        });

        if ($.validator) {
            $.validator.addMethod('geocheck_zip', function (value, element) {
                return $(element).triggerHandler('change');
            }, 'Enter the zip code');
            $.validator.addMethod('geocheck_city', function (value, element) {
                return $(element).triggerHandler('change');
            }, 'Enter the city\'s standard name');

            $.validator.addClassRules("mth-address-zip", {
                minlength: 5,
                maxlength: 5
            });

            $.validator.addClassRules("mth-address-zip", {
                geocheck_zip: true
            });

            $.validator.addClassRules("mth-address-city", {
                geocheck_city: true
            });

            $('.mth-address-geo-check').each(function () {
                $(this).valid();
            });
        }
    }
});
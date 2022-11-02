<?php

function printAddressFields($fieldName, $required = false, mth_parent $parent = null, mth_student $student = null)
{
    global $printAddressFields_googleMapsAPIincluded;
    $required = $required ? 'required' : '';
    $id = str_replace(array('[', ']'), array('-', ''), $fieldName);

    $checkgeo = true;

    if ($parent && core_user::isUserAdmins()) {
        $address = $parent->getAddress();
        if (!$student) {
            $_check = 0;
            foreach ($parent->getStudents() as $_student) {
                if (!$_student->getStatus()) {
                    $_check += 0;
                } else {
                    $_check += 1;
                }
            }
            $checkgeo = $_check > 0;
        } else {
            $checkgeo = !(!$student->getStatus());
        }
    } else {
        $parent = mth_parent::getByUser();
        $address = $parent->getAddress();
    }

    ?>
    <?php if (empty($printAddressFields_googleMapsAPIincluded)): $printAddressFields_googleMapsAPIincluded = true?>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?=core_setting::get('GoogleAPIkey')?>&sensor=false"></script>
    <?php core_loader::addJsRef('addressjs', '/_/mth_forms/address.js')?>
    <?php ?>
    <?php endif;?>

    <?php
        $inputState = $address ? $address->getState() : 'UT';
        $initialCounty = $address ? $address->getCounty() : 'format_started';
        $initialSchoolDistrict = $address ? $address->getSchoolDistrictOfR() : 'format_started';
    ?>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        let initState = '<?php echo $inputState; ?>';
        let initialCounty = '<?php echo $initialCounty; ?>';
        let initialSchoolDistrict = '<?php echo $initialSchoolDistrict; ?>';
        // console.log("initialSchoolDistrict: ", initialSchoolDistrict);
        localStorage.setItem('chooseStateType', initState);
        //console.log("initialCounty: ", initialCounty);
        CountyArrayUT = ['Beaver County', 'Box Elder County', 'Cache County', 'Carbon County', 'Daggett County', 'Davis County', 'Duchesne County', 'Emery County', 'Garfield County', 'Grand County', 'Iron County', 'Juab County', 'Kane County', 'Millard County', 'Morgan County', 'Piute County', 'Rich County', 'Salt Lake County', 'San Juan County', 'Sanpete County', 'Sevier County', 'Summit County', 'Tooele County', 'Uintah County', 'Utah County', 'Wasatch County', 'Washington County', 'Wayne County', 'Weber County'];
        CountyArrayOR = ['Baker County', 'Benton County', 'Clackamas County', 'Clatsop County', 'Columbia County', 'Coos County', 'Crook County', 'Curry County', 'Deschutes County', 'Douglas County', 'Gilliam County', 'Grant County', 'Harney County', 'Hood River County', 'Jackson County', 'Jefferson County', 'Josephine County', 'Klamath County', 'Lake County', 'Lane County', 'Lincoln County', 'Linn County', 'Malheur County', 'Marion County', 'Morrow County', 'Multnomah County', 'Polk County', 'Sherman County', 'Tillamook County', 'Umatilla County', 'Union County', 'Wallowa County', 'Wasco County', 'Washington County', 'Wheeler County', 'Yamhill County'];

        SchoolDistrictsArrayUT = ['Alpine', 'Beaver','Box Elder', 'Cache', 'Canyons', 'Carbon', 'Daggett', 'Davis', 'Duchesne', 'Emery', 'Garfield', 'Grand', 'Granite', 'Iron', 'Jordan', 'Juab', 'Kane', 'Logan', 'Millard', 'Morgan', 'Murray', 'Nebo', 'North Sanpete', 'North Summit', 'Ogden', 'Park City', 'Piute', 'Provo', 'Rich', 'Salt Lake City', 'San Juan', 'Sevier', 'South Sanpete', 'South Summit', 'Tintic', 'Tooele', 'Uintah', 'Wasatch', 'Washington', 'Wayne', 'Weber'];
        SchoolDistrictsArrayOR = ["Adel School District","Adrian School District","Alsea School District","Amity School District","Annex School District, Ontario","Arlington School District","Arock School District","Ashland School District","Ashwood School District","Astoria School District","Athena-Weston School District","Baker School District, Baker City","Bandon School District","Banks School District","Beaverton School District","Bend-La Pine School District","Bethel School District, Eugene","Blachly School District","Black Butte School District, Camp Sherman","Brookings-Harbor School District","Burnt River School District, Unity","Butte Falls School District","Camas Valley School District","Canby School District","Cascade School District, Turner","Centennial School District, Portland","Central Curry School District, Gold Beach","Central Linn School District, Brownsville","Central Point School District (formerly Jackson County School District)","Central School District, Independence","Clatskanie School District","Colton School District","Condon School District","Coos Bay School District","Coquille School District","Corbett School District","Corvallis School District","Cove School District","Creswell School District","Crook County School District, Prineville","Crow-Applegate-Lorane School District","Culver School District","Dallas School District","David Douglas School District, Portland","Days Creek School District (Douglas County School District 15)","Dayton School District","Dayville School District","Diamond School District","Double O School District, Hines","Drewsey School District","Dufur School District","Eagle Point School District","Echo School District","Elgin School District","Elkton School District","Enterprise School District","Estacada School District","Eugene School District","Falls City School District","Fern Ridge School District, Elmira","Forest Grove School District","Fossil School District","Frenchglen School District","Gaston School District","Gervais School District","Gladstone School District","Glendale School District","Glide School District","Grants Pass School District","Greater Albany Public School District","Gresham-Barlow School District","Harney County School District 3, Burns","Harney County School District 4 (Crane Elementary School District), Crane","Harney County Union High School District (Crane Union High School District), Crane","Harper School District","Harrisburg School District","Helix School District","Hermiston School District","Hillsboro School District","Hood River County School District, Hood River","Huntington School District","Imbler School District","Ione School District","Jefferson County School District, Madras","Jewell School District","John Day School District (Grant County School District), Canyon City","Jordan Valley School District","Joseph School District","Junction City School District","Juntura School District","Klamath County School District","Klamath Falls City Schools","Knappa School District","La Grande School District","Lake County School District (Lakeview School District)","Lake Oswego School District","Lebanon Community Schools","Lincoln County School District, Newport","Long Creek School District","Lowell School District","Mapleton School District","Marcola School District","McDermitt Elementary School District (Students attend school in McDermitt, Nevada)","McKenzie School District, Finn Rock","McMinnville School District","Medford School District","Milton-Freewater Unified School District","Mitchell School District","Molalla River School District","Monroe School District","Monument School District","Morrow School District, Lexington","Mt. Angel School District","Myrtle Point School District","Neah-Kah-Nie School District, Rockaway Beach","Nestucca Valley School District, Hebo","Newberg School District","North Bend School District","North Clackamas School District, Milwaukie","North Douglas School District, Drain","North Lake School District, Silver Lake","North Marion School District, Aurora","North Powder School District","North Santiam School District, Stayton","North Wasco County School District (formerly The Dalles and Chenowith school districts)","Nyssa School District","Oakland School District","Oakridge School District","Ontario School District","Oregon City School District","Oregon Trail School District, Sandy","Paisley School District","Parkrose School District, Portland","Pendleton School District","Perrydale School District","Philomath School District","Phoenix-Talent School District","Pilot Rock School District","Pine Creek School District, Hines","Pine Eagle School District, Halfway","Pinehurst School District, Ashland","Pleasant Hill School District","Plush School District","Port Orford-Langlois School District","Portland Public Schools","Powers School District","Prairie City School District","Prospect School District","Rainier School District","Redmond School District","Reedsport School District","Reynolds School District, Fairview","Riddle School District","Riverdale School District, Portland","Rogue River School District","Roseburg School District (Douglas County School District 4)","Salem-Keizer School District","Santiam Canyon School District, Mill City","Scappoose School District","Scio School District","Seaside School District","Sheridan School District","Sherman County School District, Wasco","Sherwood School District","Silver Falls School District, Silverton","Sisters School District","Siuslaw School District, Florence","South Harney School District, Fields","South Lane School District, Cottage Grove","South Umpqua School District, Myrtle Creek","South Wasco County School District, Maupin","Spray School District","Springfield School District","St. Helens School District","St. Paul School District","Stanfield School District","Suntex School District, Hines","Sutherlin School District","Sweet Home School District","Three Rivers/Josephine County School District, Murphy","Tigard-Tualatin School District","Tillamook School District","Troy School District","Ukiah School District","Umatilla School District","Union School District","Vale School District","Vernonia School District","Wallowa School District","Warrenton-Hammond School District","West Linn-Wilsonville School District","Willamina School District","Winston-Dillard School District","Woodburn School District","Yamhill-Carlton School District","Yoncalla School District"];

        $(document).ready(function() {
            changeCountyDropdown(initState);
            changeSchoolDistrictOfRDropdown(initState);
         });

        function onChangeAddressState(changedState) {
            localStorage.setItem('chooseStateType', changedState);
            changeCountyDropdown(changedState);
            changeSchoolDistrictOfRDropdown(changedState);

            // let countyEle = document.getElementById('change-county-by-state');
            // countyEle.value = id;

            // $.ajax({
            //     type: "GET",
            //     url: "mypage.php",
            //     data: "mainid =" + id,
            //     success: function(result) {
            //         $("#somewhere").html(result);
            //     }
            // });
        };

        function changeCountyDropdown(stateValue){
            //console.log("changeCountyDropdown: ", stateValue);
            var optionsAsString = "";
            $('select[id="county_state_dynamic_change"]').html('<option></option>');
            if(stateValue == 'UT'){
                for(var i = 0; i < CountyArrayUT.length; i++) {
                    optionsAsString += "<option value='" + CountyArrayUT[i] + "'>" + CountyArrayUT[i] + "</option>";
                }
                $( 'select[id="county_state_dynamic_change"]' ).append( optionsAsString );
            }else if(stateValue == "OR"){
                for(var i = 0; i < CountyArrayOR.length; i++) {
                    optionsAsString += "<option value='" + CountyArrayOR[i] + "'>" + CountyArrayOR[i] + "</option>";
                }
                $( 'select[id="county_state_dynamic_change"]' ).append( optionsAsString );
            }

            if(initialCounty != "format_started"){
                $('select[id="county_state_dynamic_change"]').val(initialCounty).change();
            }
        }


        function changeSchoolDistrictOfRDropdown(stateValue){
            //console.log("changeSchoolDistrictOfRDropdown: ", stateValue);
            var optionsAsString = "";
            $('select[id="school_district_state_dynamic_change"]').html('<option></option>');
            if(stateValue == 'UT'){
                for(var i = 0; i < SchoolDistrictsArrayUT.length; i++) {
                    optionsAsString += "<option value='" + SchoolDistrictsArrayUT[i] + "'>" + SchoolDistrictsArrayUT[i] + "</option>";
                }
                $( 'select[id="school_district_state_dynamic_change"]' ).append( optionsAsString );
            }else if(stateValue == "OR"){
                for(var i = 0; i < SchoolDistrictsArrayOR.length; i++) {
                    optionsAsString += "<option value='" + SchoolDistrictsArrayOR[i] + "'>" + SchoolDistrictsArrayOR[i] + "</option>";
                }
                $( 'select[id="school_district_state_dynamic_change"]' ).append( optionsAsString );
            }

            if(initialSchoolDistrict != "format_started"){
                $('select[id="school_district_state_dynamic_change"]').val(initialSchoolDistrict).change();
            }
        }

        function onChangeAddressCountySelecter(county){
            // console.log("county: ", county);
            if(county == ""){
                $('#edit_people_address_county_require_label').addClass('edit_people_address_require_label_here');
            }else{
                $('#edit_people_address_county_require_label').removeClass('edit_people_address_require_label_here');
            }
        }

        function onChangeAddressSchoolDistrictSelecter(schoolDistrict){
            // console.log("schoolDistrict: ", schoolDistrict);
            if(schoolDistrict == ""){
                $('#edit_people_address_school_district_require_label').addClass('edit_people_address_require_label_here');
            }else{
                $('#edit_people_address_school_district_require_label').removeClass('edit_people_address_require_label_here');
            }
        }

    </script>


    <div class="mth_address" id="mth_address-<?=$id?>">

        <?php if ($parent && core_user::isUserAdmins()): ?>
            <input type="hidden" name="<?=$fieldName?>[parent_id]" value="<?=$parent->getID()?>" class="form-control">
        <?php endif;?>

        <div class="form-group">
            <label>Street</label>
                        <input type="text" class="form-control" name="<?=$fieldName?>[street]" id="<?=$id?>-street"
                   value="<?=$address ? $address->getStreet() : ''?>" <?=$required?>>
        </div>
        <div class="form-group">
            <label>Street Line 2</label>
            <input type="text" name="<?=$fieldName?>[street2]" id="<?=$id?>-street2"
                   value="<?=$address ? $address->getStreet2() : ''?>" class="form-control">
        </div>
        <div class="form-group">
            <label>City</label>
            <input type="text" name="<?=$fieldName?>[city]" id="<?=$id?>-city"
                   value="<?=$address ? $address->getCity() : ''?>" <?=$required?>
                   class="form-control mth-address-city  <?=$checkgeo ? 'mth-address-geo-check' : ''?>">
        </div>
        <div class="form-group">
            <label>State</label>
            <input type="text" class="form-control" name="<?=$fieldName?>[state]" id="<?=$id?>-state"
                   value="<?php echo $inputState; ?>"
                   <?=$required?>
                  onchange="onChangeAddressState(this.value);"
                   style="max-width: 60px;" maxlength="2">
        </div>
        <div class="form-group">
            <label>Zip</label>
            <input type="text" name="<?=$fieldName?>[zip]" id="<?=$id?>-zip"
                   value="<?=$address ? $address->getZip() : ''?>" <?=$required?>
                   style="max-width: 90px;"
                   class="form-control mth-address-zip  <?=$checkgeo ? 'mth-address-geo-check' : ''?>">
        </div>
        <div class="form-group">
            <label>County</label>
            <!-- <input type="text" id="change-county-by-state" class="form-control"> -->
            <select onchange="onChangeAddressCountySelecter(this.value);"  <?=$required?> name="<?=$fieldName?>[county]" id="county_state_dynamic_change" class="borderLess mth_student-status-select form-control <?=$checkgeo ? 'mth-address-geo-check' : ''?>">
            <option></option>
            </select>
            <label id="edit_people_address_county_require_label"></label>
        </div>
        <div class="form-group response-form-group-width-style">
            <label>School District of Residence</label>
            <select onchange="onChangeAddressSchoolDistrictSelecter(this.value);" <?=$required?> name="<?=$fieldName?>[school_district]" id="school_district_state_dynamic_change" class="borderLess mth_student-status-select form-control <?=$checkgeo ? 'mth-address-geo-check' : ''?>">
            <option></option>
            </select>
            <label id="edit_people_address_school_district_require_label"></label>
        </div>
    </div>

    <style>
        .response-form-group-width-style{
            min-width: 494px;
        }

        @media(max-width:1470px){
            .response-form-group-width-style{
            min-width: 230px;
            }
        }

        @media(max-width:865px){
            .response-form-group-width-style{
            min-width: 210px;
            }
        }

        @media(max-width:768px){
            .response-form-group-width-style{
            min-width: 260px;
            }
        }

        @media(max-width:410px){
            .response-form-group-width-style{
            min-width: 215px;
            }
        }

        @media(max-width:370px){
            .response-form-group-width-style{
            min-width: 160px;
            }
        }

        @media(max-width:310px){
            .response-form-group-width-style{
            min-width: 50px;
            }
        }
    </style>

<?php } ?>
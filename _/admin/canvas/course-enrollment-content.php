<?php
if (req_get::bool('runStep')) {
    $step = req_get::int('runStep');
    switch ($step) {
        case 1:
            if (($success = mth_canvas_enrollment::pull_from_course(2204820)) === TRUE) {
                exit('Canvas Course Enrollment Updated');
            } elseif (is_null($success)) {
                core_loader::redirect('?runStep=' . $step);
            }
        exit('<span class="error">Problems updating Canvas Course Enrollments!</span>');
        break;
    }
}

$stepCount = 1; //based on the above operations.

core_loader::isPopUp();
core_loader::printHeader();
?>
    <script>
        var mth_canvas_progress = {
            onStep: 0,
            progressStep: 0,
            stepCount: <?=$stepCount?>,
            totalProgressSteps: <?=$stepCount * 3?>,
            execute: function () {
                mth_canvas_progress.onStep++;
                mth_canvas_progress.progressStep += 2;
                mth_canvas_progress.updateProgressBar();
                $.ajax({
                    url: '?runStep=' + mth_canvas_progress.onStep,
                    success: function (data) {
                        mth_canvas_progress.postMessage(data);
                        mth_canvas_progress.progressStep++;
                        mth_canvas_progress.updateProgressBar();
                        if (mth_canvas_progress.onStep < mth_canvas_progress.stepCount) {
                            setTimeout(mth_canvas_progress.execute, 500);
                        }
                    },
                    error: function () {
                        if (mth_canvas_progress.onStep > 1) {
                            mth_canvas_progress.onStep--; //restart failed step.
                            mth_canvas_progress.progressStep -= 2;
                            mth_canvas_progress.postMessage('<span class="error">Step timed out. Continuing Step...</span>');
                            mth_canvas_progress.execute();
                        } else {
                            mth_canvas_progress.postMessage('<span class="error">Unable to complete process because of an error!</span>');
                            mth_canvas_progress.complete();
                        }
                    }
                });
            },
            updateProgressBar: function () {
                var progress = (this.progressStep / this.totalProgressSteps) * 100;
                if (progress >= 100) {
                    mth_canvas_progress.postMessage('<b>Canvas synchronization completed!</b>');
                    $('#mth_canvas_progress_bar_complete').css('transition', '.5s');
                    setTimeout(mth_canvas_progress.complete, 500);
                }
                $('#mth_canvas_progress_bar_complete').css('width', progress + '%');
            },
            complete: function () {
                $('#mth_canvas_progress_close').fadeIn();
                $('#mth_canvas_progress_bar_complete').css({
                    'background': '#7FA3DB'
                });
            },
            postMessage: function (content) {
                $('#mth_canvas_progress_feedback').append('<p>' + content + '</p>');
            }
        };
        $(function () {
            mth_canvas_progress.execute();
        });
    </script>
    <style>
        #mth_canvas_progress_bar {
            margin-top: 30px;
            height: 30px;
            border: solid 2px #7FA3DB;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, .1);
            overflow: hidden;
        }

        #mth_canvas_progress_bar_complete {
            box-shadow: 2px 0 10px rgba(0, 0, 0, .1), inset 2px 2px 10px rgba(0, 0, 0, .3);
            height: 30px;
            background: #7FA3DB url(/_/includes/img/progress.gif);
            opacity: 1;
            transition: width 50s;
        }

        .error {
            color: red;
        }
    </style>
    <div id="mth_canvas_progress_bar">
        <div id="mth_canvas_progress_bar_complete" style="width: 0"></div>
    </div>
    <div id="mth_canvas_progress_feedback">
        <p><b>Canvas Course Enrollment synchronization started...</b><br><?= mth_canvas::url() ?></p>
    </div>
    <p id="mth_canvas_progress_close" style="display: none">
        <input type="button" onclick="parent.location.reload(true)" value="Close"><br>
        See the Canvas Management for any errors.
    </p>
<?php
core_loader::printFooter();
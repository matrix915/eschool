CKEDITOR.config.removePlugins = "uploadimage,widget,uploadwidget,tabletools,tableselection,print,format,pastefromword,pastetext,clipboard,about,image,forms,youtube,print,stylescombo,flash,newpage,save,preview,templates";
CKEDITOR.config.removeButtons = "Subscript,Superscript";
CKEDITOR.config.disableNativeSpellChecker = false;
CKEDITOR.config.allowedContent = true;
CKEDITOR.config.height = 400;
CKEDITOR.replace('details-content');


$(function () {
     $('#deadline_d').datepicker();
     $('#deadline_t').timepicker({
          'step': function (i) {
               return (i != 48) ? 30 : 29;
          }
     });

     $('#save-log').click(function () {
          $('#logform').submit();
     });


     $('#learning-log-question-container').on('click', '.delete-question', function () {
          var $this = $(this);
          top.swal({
               title: '',
               text: 'Are you sure you want to delete this question?',
               type: "warning",
               showCancelButton: true,
               confirmButtonText: "Yes",
               closeOnConfirm: true,
          }, function () {
               $this.closest('.question-item-container').remove();
          });
     }).on('click', '.delete-checklist-item', function () {
          var $this = $(this);
          top.swal({
               title: '',
               text: 'Are you sure you want to delete this checklist item?',
               type: "warning",
               showCancelButton: true,
               confirmButtonText: "Yes",
               closeOnConfirm: true,
          }, function () {
               $this.closest('.checklist-item').remove();
          });
     }).on('click', '.add-checklist', function () {
          var $this = $(this);
          var question_number = $this.closest('.question-item-container').index();

          $.ajax({
               url: '?checklist=1&row=' + question_number + '&value=',
               type: 'GET',
               success: function (response) {
                    $this.closest('.question-container').find('.checklist-container').append(response);
               },
               error: function () {
                    alert('There is an error adding item from the checklist');
               }
          });

     });

     $('#add-question').click(function () {
          var question_content = $('#question-content').val();
          var is_checklist = $('#is-checklist').is(':checked');
          var question_number = $('.question-item-container').length;
          var _data = is_checklist ? {
               title: question_content
          } : question_content;

          $.ajax({
               url: '?question=1',
               data: {
                    row: question_number,
                    ischecklist: (is_checklist ? 1 : 0),
                    data: _data
               },
               type: 'GET',
               success: function (response) {
                    $('#questions-container').append(response);
                    $('#add-question-modal').modal('hide');
               },
               error: function () {
                    alert('There is an error adding the question.');
               }
          });
     });

     $('#add-question-modal').on('show.bs.modal', function (event) {
          $('#question-content').val('');
          $('#is-checklist').prop('checked', false);
     })
});
/**
 * Learling Log Claas
 */
class LearningLog{
     url =  '/_/admin/yoda/courses/homeroom';

     replicate(id){
          swal({
               title: '',
               text: 'Are you sure you want to replicate this Learning Log?',
               type: "warning",
               showCancelButton: true,
               confirmButtonText: "Yes",
               closeOnConfirm: true,
          }, () => {
               this._replicate(id);
          });
     }
     _replicate(id){
          $.ajax({
               url: this.url+'?clone='+id,
               dataType: 'JSON',
               success:function(response){
                    if(response.error == 1){
                         swal('','Unable to clone learning log','error');
                    }else{
                         location.reload();
                    }
               }
          });
     }
     delete(id){
          swal({
               title: '',
               text: 'Are you sure you want to delete this Learning Log?',
               type: "warning",
               showCancelButton: true,
               confirmButtonText: "Yes",
               closeOnConfirm: true,
          }, () => {
               this._delete(id);
          }); 
     }
     _delete(id){
          $.ajax({
               url: this.url+'?delete='+id,
               dataType: 'JSON',
               success:function(response){
                    if(response.error == 1){
                         swal('','Unable to delete learning log','error');
                    }else{
                         location.reload();
                    }
               }
          });
     }
}

LearningLog = new LearningLog();
/**
 * @author Rex
 * Pagination request table use for rendering chuncked/paginated queries
 * to be rendered asynchronously on a datatable
 * This class is dependent to jquery datatable
 * 
 */
class LazyTable {
     pagesize = 250;
     columns = [];
     data = '';
     timeout = 0;
     completed = 0;
     busy = false;
     page = 1;
     resetTable = true;
     statusCountComplete = false;
     active_page = 0;
     table = null;
     dataTable = null;

     /**
      * @param {*} columns 
      * @param {*} table
      * @param {*} $DataTable The datatable instance 
      */
     constructor(columns, table, $DataTable) {
          this.columns = columns;
          this.table = table;
          this.dataTable = $DataTable;
     }
     
     setPageSize(size) {
          this.pagesize = size;
     }

     /**
      * 
      * @param {*} obj 
      * @returns {}
      */
     createDataRowObject(obj, id) {
          var columns = Object.entries(this.columns)
          var row = {};
          for (const [col, val] of columns) {
               row[col] = typeof val == 'function' ? val(obj) : obj[col];
          }
          return { DT_RowId: 'row-' + id, ...row };
     }

     /**
      * @param {*} nextPage +1 page
      * @param {*} data GET request data
      */
     load(nextPage, data) {
          var data = data;
          var prevpage = '';
          var curpage = '';
          if (this.busy && !nextPage) {
               return;
          }
          this.busy = true;

          if (nextPage) {
               this.page += 1;
               prevpage = 'page=' + (this.page - 1);
               curpage = 'page=' + this.page;
               data = data.replace(prevpage, curpage);
          } else {
               this.page = 1;
               data += '&page=1';
          }

          this.data = data;

          this.table.addClass('waiting');
          this.statusCountComplete = false;
          this.request();
     }

     reset(){
          this.busy = false;
          this.resetTable = true;
          this.page = 1;
          this.active_page = 0;
          this.dataTable.rows().remove();
          this.dataTable.draw();
     }

     request() {
          var self = this;
          $.ajax({
               url: '?loadfilter=1',
               data: this.data,
               method: 'get',
               cache: false,
               dataType: 'json',
               success: (res)=>{
                    if (res.count === 0) {
                         if (this.page === 1) {
                              swal('', 'No records', 'info');
                              self.reset();
                         }
                         $table.removeClass('waiting');
                         return;
                    }
          
                    var response = res.filtered;
          
                    if (self.resetTable) {
                         self.dataTable.rows().remove();
                         self.dataTable.draw();
                         self.resetTable = false;
                    }
          
                    $.each(response, function (index, _obj) {
                         var rowData = self.createDataRowObject(_obj,_obj.id);
                         self.dataTable.row.add(rowData);
                    });
          
                    self.dataTable.draw();
                    //$('.student_count_display').text($DataTable.data().length);
          
          
                    if (res.count < self.pagesize) {
                         self.table.removeClass('waiting');
                         self.page = 1;
                         self.busy = false;
                         self.dataTable.page(self.active_page).responsive.recalc().draw(false);
                    } else {
                         self.load(true,self.data);
                    }
               },
               error: function () {
                    swal('', 'Server Error, Unable to load. Please try again.', 'error');
               }
          });
     }

}
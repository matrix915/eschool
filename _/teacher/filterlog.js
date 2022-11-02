class FilterSll{
     _zero =  '.zero-item';
     _search = '.search-content';
     _row = '.ssl-row';
     _hidden = 'fl-hidden';

     $table = null;
     $zero_cb = null;
     $search = null;

     constructor(_table,$zero_cb,$search){
          this.init(_table,$zero_cb,$search);
     }

     init(_table,$zero_cb,$search){
          this.$table = _table;
          this.$zero_cb = $zero_cb;
          this.$search = $search;
     }


     hideNotZero($tr){
          if(this.$zero_cb){               
               return this.$zero_cb.is(':checked') && !$tr.is(this._zero);
          }

          return false;               
     }

     hideReset($tr){
          if(this.$zero_cb){
               return !this.$zero_cb.is(':checked') && $tr.is(this._zero);
          }

          return false;
     }

     hideDontMatch($tr,q){
          var _search = '.search-content';
          return $tr.find(_search).text().toLowerCase().indexOf(q) == -1;
     }

     execute(){
          if(this.$table!=null){
               var _this = this;
               var hidden = this._hidden;
               var $search = this.$search;
               var q = $search?this.$search.val().toLowerCase():'';

               this.$table.find('.ssl-row').filter(function() {
                    var $row = $(this);
                    if(_this.hideNotZero($row) || _this.hideReset($row) || _this.hideDontMatch($row,q)){
                         $(this).addClass(hidden);
                    }else{
                         $(this).removeClass(hidden);
                    }
               });
          }
          
     }

}
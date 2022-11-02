$(function () {
    var win = $(window);
    win.on('scroll', function () {
        var scroll = win.scrollTop();
        document.body.style.backgroundPosition = '0% ' + (scroll + 30) + 'px';
        /*if(scroll<=150){
         try{
         document.getElementById('main-content').style.background = 'rgba(255,255,255,'+(scroll/380+.5);
         }catch(e){
         document.getElementById('main-content').style.background = '#fff';
         }
         }else{
         try{
         document.getElementById('main-content').style.background = 'rgba(255,255,255,.9)';
         }catch(e){
         document.getElementById('main-content').style.background = '#fff';
         }
         }*/
    });
//  $('#bg').css({'min-height':win.height()+'px'});
});

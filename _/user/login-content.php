<?php
core_loader::includejQueryValidate();

$canvasLogin = core_path::getPath()->getSegment(1) == 'cas';

cms_page::setDefaultTempTitle($canvasLogin ? 'Integrated Login with Canvas' : 'Login');


cms_page::setPageContent('<h3>Parents:</h3>
  <p>Please use your InfoCenter email and password.</p>',
    'Login Instructions Left',
    cms_content::TYPE_HTML);

cms_page::setPageContent('<h3>Students:</h3>
  <p>If this is your first time logging in through this form you will use your birth date as your password (e.g. 7/4/1776). 
    You will then be taken to a page to set your password.</p>',
    'Login Instructions Right',
    cms_content::TYPE_HTML);
core_loader::addCssRef('login', core_config::getThemeURI() . '/assets/css/loginv3.min.css');
core_loader::addClassRef('page-login-v3 layout-full page-login-page');
core_loader::printHeader();
?>
<?php if ($canvasLogin): ?>
    <style>
        h1#page-title {
            background: url(/_/cas/canvas.png) center right no-repeat;
        }
    </style>
    <div class="content-left">
        <?= cms_page::getDefaultPageContent('Login Instructions Left', cms_content::TYPE_HTML) ?>
    </div>
    <div class="content-right">
        <?= cms_page::getDefaultPageContent('Login Instructions Right', cms_content::TYPE_HTML) ?>
    </div>
    <div style="clear: both; padding: 40px 0">
        <hr>
    </div>

<?php endif; ?>

    <script>
        if(location.href!=top.location.href){
            top.location.reload(true);
        }
    </script>
 
 <div class="page vertical-align text-center" data-animsition-in="fade-in" data-animsition-out="fade-out">&gt;
 <div class="page-content vertical-align-middle">
 <span class="brand-logo-text font-size-30">
  <span >Info</span><span class="mth-blue">Center</span>
</span>
   <div class="panel mt-20">
     <div class="panel-body">
       <form method="post" class="mt-0 core-secure-login-form" autocomplete="off" action="<?= core_path::getPath()->getString() != core_config::getLoginPath() ? core_path::getPath() : '/' ?>">
         <div class="form-group form-material text-left">
         <label class="form-control-label">Email</label>
           <input type="email" class="form-control empty" name="email" id="email">
           <input type="hidden" name="callback" value="<?=req_get::txt('callback')?>">
         </div>
         <div class="form-group form-material  text-left">
         <label class="form-control-label">Password</label>
           <input type="password" class="form-control empty" name="password" id="password">
           
         </div>
         <div class="form-group clearfix">
           <div class="checkbox-custom checkbox-inline checkbox-primary checkbox-lg float-left">
             <input type="checkbox" id="inputCheckbox" name="remember">
             <label for="inputCheckbox" value="1" name="rememberMe">Remember me</label>
           </div>
           <a class="float-right" href="/_/user/forgot">Forgot password?</a>
         </div>
         <button type="submit" class="btn btn-primary btn-block btn-lg mt-40 btn-round">Sign in to InfoCenter</button>
       </form>
     </div>
   </div>

   <footer class="page-copyright page-copyright-inverse">
        <?php core_loader::printMTHFooterContent();?>
   </footer>
 </div>
</div>

<?php
core_loader::printFooter();
?>
<script>
    $(function(){
        $('#email').trigger('focus');
    });
</script>
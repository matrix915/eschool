<?php
mth_user::isParent() || core_secure::loadLogin();

cms_page::setPageTitle('High-quality core curriculum supported by certified teachers');
core_loader::printHeader('student');
?>
<div class="page">  
     <?= core_loader::printBreadCrumb('window'); ?>
     <div class="page-content container-fluid">
          <h3 style="color:#ff5722">Unlimited access!!</h3>
          <div class="grid row">
               <?php while ($resource = mth_resource_settings::banners()) : ?>
               <div class="grid-item col-md-3">
                    <div class="card">
                         <div class="card-block">
                              <div class="text-center pb-10">
                                   <?php if($resource->image()):?>
                                        <img src="<?=$resource->getBanner()?>" class="img-fluid">
                                   <?php else:?>
                                        <h4><?=$resource->name()?></h4>
                                   <?php endif;?>
                              </div>
                              <div>
                                   <?=$resource->content();?>
                              </div>
                         </div>
                    </div>
               </div>
               <?php endwhile;?>
               <div class="grid-item col-md-3 pt-30">
                    <a class="btn btn-round btn-lg btn-pink btn-block" href="/forms/resource">Request Optional Homeroom Resources</a>
               </div>
                    
          </div>
     </div>
</div>
<?php
core_loader::printFooter('student');
?>
<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
<script>
     $(function() {
          $('.grid').masonry({
               // options
               itemSelector: '.grid-item'
          });
     });
</script>
<br style="clear: both;">
</div> <!-- /#main-content -->
</div> <!-- /#main -->
</div> <!-- /#wrapper -->
<footer id="site-footer">
    <div id="site-footer-content1">
        <?= cms_page::getDefaultPageContent('Footer Block 1', cms_content::TYPE_HTML) ?>
    </div>
    <div id="site-footer-content2">
        <?= cms_page::getDefaultPageContent('Footer Block 2', cms_content::TYPE_HTML) ?>
    </div>
    <div id="site-footer-content3">
        <?= cms_page::getDefaultPageContent('Footer Block 3', cms_content::TYPE_HTML) ?>
    </div>
</footer>
<?php core_loader::printFooterContent() ?>
</body>
</html>
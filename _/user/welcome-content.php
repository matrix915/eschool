<?php
cms_page::setPageTitle('Welcome');
cms_page::setPageContent('
  <p>We have created an account for you. You should receive an email which contains a link for you 
    to create a password for your account.</p>
  <p>Please contact us if you have any questions.</p>
  ');
core_loader::useThemeTemplate();
# Infocenter

Infocenter is a portal for all mytechhigh users, allows admistrative funtionality for admin to manage MyTechHigh Canvas Courses , Students , Enrollments, Packet , etc... It also allows parents to manage their children's information and statuses.

## Getting Started

To get started you need to clone **MyTechHigh-InfoCenter** repository from codecommit to your local machine, provided you already have the access to codecommit but before that you need to comply the prerequisites below.

### Prerequisites

* PHP (>= 7.3) - We are using php as our backend
* Mysql (>= 5.6, 8.x recommended) - Database
* Apache2 - Web server
* Git(>= 1.7.9)
* Gmail Account - SMTP
* Dropbox Developer Account

Once you've installed php you might want to mirror your settings(php.ini) from the LIVE configuration (this is optional)

```
max_file_uploads 20
upload_max_filesize 25M
memory_limit 512M
post_max_size 100M
session.gc_maxlifetime 7200
```

### Installing
This installation process needs to have a sql dump file for data seeds.

*There is also an option to do a fresh installation see [Infocenter Full Documentation](https://docs.google.com/document/d/1XeyfFhsC8OQYjN0XTReetevBHlFDmqN7uvUZTyIF5yY/edit?usp=sharing)*

Clone **MyTechHigh-InfoCenter** repository from codecommit
```
git clone ssh://git-codecommit.us-west-2.amazonaws.com/v1/repos/MyTechHigh-InfoCenter
```

1. Run `composer install`
2. Make a copy of ".env.example" as ".env" and replace the values as needed for the environment. If you have a **beta dump file** be sure to use the same **beta SALT value**

**MIGRATION**

Import the dump sql file provided.
  
**APACHE config**

As much as possible i would like to suggest to create your own virtual domain/host instead of using http://localhost:8080

On your httpd.conf or httpd-vhost.conf you should add something like this
```
<VirtualHost dev-infocenter.mytechhigh.com>
DocumentRoot "C:/xampp/htdocs/MyTechHigh-Infocenter/"
ServerName dev-infocenter.mytechhigh.com
ErrorLog "C:/xampp/htdocs/error.log"
</VirtualHost>
```

If you want to add a custom local domain you should add it to your machine host file.

Please note that the public director of infocenter is / (straight up pointing to index.php)

Hoooray!!! Infocenter app should be accessible using the link you configured on your apache vhost file eg. http://dev-infocenter.mytechhigh.com

Then use the sample credential prodived to you or you can just modify the password from core_users table (replace the password field of the user you want to use ).

Passwords were encrypted so if you want to overwrite password - you can do it by md5(MTH_SALT+[your-new-password]); 

*Please note of the level 1:admin,3:teacher,1:student*

## PHP Code Sniffing

Follow PSR2 standard, you can use [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer).
Intall it via composer global(see documentation) and you can do the sniffing like this
```
phpcs directory/thefile.php
```
PHPCS is also available for vscode as an extension but still need the phpcs excutable

### Break down into end to end tests

NA

## Uploads

Every file uploaded can be found to the designated S3 bucket except for the Attachments made from every attachment enabled rich text/wyswyg editor(files can be found at /_/uploads/)

## Deployment

Currently infocenter has 2 active branches (for MTH)
* master - main branch production infocenter is using
* beta - branch beta site is using
 
See Deployment Section in [Infocenter Documentation](https://docs.google.com/document/d/1XeyfFhsC8OQYjN0XTReetevBHlFDmqN7uvUZTyIF5yY/edit?usp=sharing) for full deployment process

## Built With

* Native PHP
* Jquery
* Bootstrap

## Versioning

We use [SemVer](http://semver.org/) for versioning on every production build as of v1.0.0 . For the versions available, see the tags.

## Authors
*  **Rex Cambarijan** - *Application Developer* - [Codev](https://codev.com)

## License

This project is owned by **My Tech High, Inc.**

# Laravel Table cleaner.

This a artisan command for clearing database tables of duplicates.

 - To use: copy down the DeleteTableDuplicates.php into your App/Console/Commands directory. 
 -  To register for using with scheduler visit the  [docs](https://laravel.com/docs/5.8/artisan#registering-commands).

**The artisan command:**

      php artisan delete-table-duplicates
**Optionally you can pass in arguments making it compatible to call it programmatically like so:**

    php artisan delete-table-duplicates --table=tabel_name --column=column_name --force-delete=false --delete-previous=true
*Note: this was built with Laravel 5.8. You should check if it's compatible with your version.*

Will make this into a packagist plugin
--force-delete=true will delete all soft deleted record, regardless if it's a duplicate or not

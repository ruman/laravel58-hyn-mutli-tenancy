## Laravel with hyn/multi-tenant

This is simple integration of hyn/multi-tenant into Laravel 5.8.\*

Steps:

1. Download repo
2. cd into repo
3. `composer install`
4. `npm install`
5. `php artisan config:cache`
6. `php artisan vendor:publish`
7. `php artian migrate`
8. `php artisan db:seed --class=RolePermissionSeeder`
9. Now to create tenent:
   `php artisan tenant:create foohost Foo Host foo@host.com --password=foo123`
10. Now you can accees the newly created tenant site.

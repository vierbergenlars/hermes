export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)
source .clic-scripts/maintenance.inc.sh
if [[ "$SYMFONY_ENV" != "dev" ]]; then
    composer install --no-dev --optimize-autoloader 2>&1
else
    composer install --optimize-autoloader 2>&1
fi;
npm install
rm -rf var/cache/*/* # Prevent class not found errors during cache clear
php bin/console cache:clear
php bin/console assets:install
php bin/console assetic:dump
php bin/console braincrafted:bootstrap:install
if tty -s; then
    # Only execute migrations when there are new migrations available.
    php bin/console doctrine:migrations:status | grep "New Migrations:" | cut -d: -f2 |grep "^ *0" > /dev/null || \
    php bin/console doctrine:migrations:migrate
else
    php bin/console doctrine:migrations:migrate -n
fi
disable_maintenance

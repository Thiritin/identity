echo "Checking database status."
# until pg_isready -d $DB_DATABASE -h $DB_HOST -p $DB_PORT -U $DB_USERNAME
#do
#  echo "Waiting for database connection..."
#  # wait for 1 seconds before check again
#  sleep 1
#done

## start cronjobs for the queue
echo -e "Starting cron jobs."
crond -L /var/log/crond -l 5

echo -e "Migrate Database"
#php artisan migrate --force

echo -e "Start Supervisor"
exec "$@"

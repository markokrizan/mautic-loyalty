Run 
`chmod +x ./start-dev.sh`
`./start-dev.sh`
To start up the docker-compose stack

Note: the setup wizard will create the db schema

To run additional migrations: 
`docker  exec  mautic  /var/www/html/bin/console  doctrine:migrations:migrate`

Setup cache directory tree (fixes some issues with not found cached directories):
`docker  exec  mautic  /var/www/html/bin/console  cache:warmup  --prod`
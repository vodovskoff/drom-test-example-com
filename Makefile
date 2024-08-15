test:
	docker exec -it app-test-drom-2 php /var/www/html/vendor/phpunit/phpunit/phpunit

fix-cs:
	docker exec -it app-test-drom-2 vendor/bin/php-cs-fixer fix

create:
	docker compose build
	docker compose up -d
	docker exec -it app-test-drom-2 composer install

start:
	docker compose up -d

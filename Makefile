.PHONY: setup generate test format analyse catalogs-update

setup:
	docker compose build
	docker compose run --rm php composer setup

generate:
	docker compose run --rm php php artisan cfdi:40:generate resources/examples/input.json

test:
	docker compose run --rm php php artisan test

format:
	docker compose run --rm php ./vendor/bin/pint

analyse:
	docker compose run --rm php composer analyse

catalogs-update:
	docker compose run --rm php php artisan cfdi:catalogs:install --update

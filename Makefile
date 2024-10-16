.PHONY: build test clean test
build:
	$(if $(PHP_VERSION),,$(error PHP_VERSION make variable needs to be set))
	docker buildx build --build-arg=PHP_VERSION=$(PHP_VERSION) -t php-composer:$(PHP_VERSION) .

lint: build
	docker run --rm -v ./:/code -v/code/vendor php-composer:$(PHP_VERSION) bash -c 'cd /code && composer update && vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ test/'

test: build lint
	docker run --rm -v ./:/code -e dependencies=highest -v/code/vendor php-composer:$(PHP_VERSION) bash -c 'cd /code && composer update && ./project_tests.sh'

test-lowest: build lint
	docker run --rm -v ./:/code -e dependencies=lowest -v/code/vendor php-composer:$(PHP_VERSION) bash -c 'cd /code && ./project_tests.sh'


test-7.1:
	@$(MAKE) PHP_VERSION=7.1 test test-lowest
test-7.2:
	@$(MAKE) PHP_VERSION=7.2 test test-lowest
test-7.3:
	@$(MAKE) PHP_VERSION=7.3 test test-lowest
test-7.4:
	@$(MAKE) PHP_VERSION=7.4 test test-lowest
test-8.0:
	@$(MAKE) PHP_VERSION=8.0 test test-lowest
test-8.1:
	@$(MAKE) PHP_VERSION=8.1 test test-lowest
test-8.2:
	@$(MAKE) PHP_VERSION=8.2 test test-lowest
test-8.3:
	@$(MAKE) PHP_VERSION=8.3 test test-lowest

test-all: test-7.1 test-7.2 test-7.3 test-7.4 test-8.0 test-8.1 test-8.2 test-8.3

test-all-supported: test-7.1 test-7.2 test-7.3 test-7.4

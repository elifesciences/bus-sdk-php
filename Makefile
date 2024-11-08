PROJECT_NAME = bus-sdk-php
.PHONY: build test clean test
build:
	$(if $(PHP_VERSION),,$(error PHP_VERSION make variable needs to be set))
	docker buildx build --build-arg=PHP_VERSION=$(PHP_VERSION) -t $(PROJECT_NAME):$(PHP_VERSION) .

lint: build
	docker run --rm $(PROJECT_NAME):$(PHP_VERSION) bash -c 'vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ test/'

test: build lint
	docker run --rm -e AWS_SUPPRESS_PHP_DEPRECATION_WARNING=true $(PROJECT_NAME):$(PHP_VERSION) bash -c './project_tests.sh'

test-7.1:
	@$(MAKE) PHP_VERSION=7.1 test
test-7.2:
	@$(MAKE) PHP_VERSION=7.2 test
test-7.3:
	@$(MAKE) PHP_VERSION=7.3 test
test-7.4:
	@$(MAKE) PHP_VERSION=7.4 test
test-8.0:
	@$(MAKE) PHP_VERSION=8.0 test
test-8.1:
	@$(MAKE) PHP_VERSION=8.1 test
test-8.2:
	@$(MAKE) PHP_VERSION=8.2 test
test-8.3:
	@$(MAKE) PHP_VERSION=8.3 test

test-all: test-7.1 test-7.2 test-7.3 test-7.4 test-8.0 test-8.1 test-8.2 test-8.3

test-all-supported: test-7.1 test-7.2 test-7.3 test-7.4

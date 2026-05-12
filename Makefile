# Lightpack Framework - Development Commands

.PHONY: help test cs fix stan install clean

help: ## Show this help message
	@echo "Lightpack Framework - Available Commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
	@echo ""

install: ## Install Composer dependencies
	composer install

test: ## Run PHPUnit tests (all suites)
	./vendor/bin/phpunit --testsuite All

test-verbose: ## Run PHPUnit tests with full output
	./run-tests.sh --verbose

cs: ## Check code style with PHP-CS-Fixer (dry-run)
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

fix: ## Fix code style automatically
	vendor/bin/php-cs-fixer fix --verbose

stan: ## Run PHPStan static analysis
	vendor/bin/phpstan analyse --no-progress

qa: ## Run all quality checks (tests + static analysis + code style)
	@echo "Running tests..."
	./vendor/bin/phpunit --testsuite All
	@echo ""
	@echo "Running PHPStan..."
	vendor/bin/phpstan analyse --no-progress
	@echo ""
	@echo "Checking code style..."
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

clean: ## Clean up generated files and caches
	@rm -rf coverage/
	@rm -rf .phpunit.result.cache
	@rm -rf .phpstan.cache
	@rm -rf vendor/
	@echo "Cleaned generated files."

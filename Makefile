.DEFAULT_GOAL := help

.PHONY: help up down restart install install-v12 install-v13 ssh docs \
        cgl cgl-fix phpstan rector lint mutation \
        test test-unit test-functional ci clean

# ===== DDEV Environment =====

up: ## Start DDEV, install dependencies, TYPO3 instances, and render docs
	ddev start
	ddev setup
	ddev install-all
	ddev render-docs

down: ## Stop DDEV environment
	ddev stop

restart: ## Restart DDEV environment
	ddev restart

install: ## Install TYPO3 v12 and v13 with extension
	ddev install-all

install-v12: ## Install TYPO3 v12 with extension
	ddev install-v12

install-v13: ## Install TYPO3 v13 with extension
	ddev install-v13

ssh: ## Open shell in web container
	ddev ssh

docs: ## Render extension documentation
	ddev render-docs

# ===== Code Quality =====

cgl: ## Check code style (dry-run)
	ddev exec composer ci:test:php:cgl

cgl-fix: ## Fix code style
	ddev exec composer ci:cgl

phpstan: ## Run PHPStan static analysis
	ddev exec composer ci:test:php:phpstan

rector: ## Run Rector dry-run
	ddev exec composer ci:test:php:rector

lint: ## Run PHP syntax lint
	ddev exec composer ci:test:php:lint

# ===== Testing =====

test: test-unit test-functional ## Run all tests

test-unit: ## Run unit tests
	ddev exec composer ci:test:php:unit

test-functional: ## Run functional tests
	ddev exec composer ci:test:php:functional

mutation: ## Run mutation testing
	ddev exec composer test:mutation

# ===== CI (all checks) =====

ci: cgl phpstan rector lint test-unit test-functional ## Run all CI checks

# ===== Cleanup =====

clean: ## Remove .Build, caches, vendor
	ddev exec rm -rf .Build .php-cs-fixer.cache Build/.phpunit.cache vendor composer.lock
	@echo "Clean complete. Run 'make up' to reinstall."

# ===== Help =====

help: ## Show this help
	@echo "Usage: make [target]"
	@echo ""
	@echo "DDEV Environment:"
	@grep -E '^(up|down|restart|install|install-v12|install-v13|ssh|docs):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Code Quality:"
	@grep -E '^(cgl|cgl-fix|phpstan|rector|lint):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Testing:"
	@grep -E '^(test|test-unit|test-functional|mutation):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Other:"
	@grep -E '^(ci|clean):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

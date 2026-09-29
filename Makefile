.PHONY: help up down logs build test test-backend test-frontend lint analyse typecheck check verify

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Start all Docker services
	docker compose up -d

down: ## Stop all Docker services
	docker compose down

logs: ## Tail service logs
	docker compose logs -f

build: ## Build the Docker image
	docker compose build

test: test-backend test-frontend ## Run all tests

test-backend: ## Run backend (PHPUnit) tests
	docker compose run --rm -T app php artisan test

test-frontend: ## Run frontend (Vitest) tests
	docker compose run --rm -T app npm run test

lint: ## Run PHP Pint style check
	docker compose run --rm -T app ./vendor/bin/pint --test

analyse: ## Run Larastan static analysis
	docker compose run --rm -T app composer analyse

typecheck: ## Run vue-tsc type check
	docker compose run --rm -T app npm run typecheck

check: lint analyse typecheck ## Run all quality checks (no tests)
	docker compose run --rm -T app npm run lint
	docker compose run --rm -T app npm run format:check

verify: check test ## Run all checks and tests
	docker compose run --rm -T app npm run build

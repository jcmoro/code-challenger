.PHONY: help install install-dev update composer-validate test test-unit test-integration test-coverage phpstan rector rector-fix cs-check cs-fix serve bash quality quality-fix ci setup clean cache-clear api-test-* docker-build docker-up docker-down docker-restart docker-status docker-logs

# Variables
CURRENT_DIR := $(shell pwd)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

# Docker image
CODE_CHALLENGER_IMAGE := code-challenger
PHP_DOCKER := docker run --rm -v $(CURRENT_DIR):/app -w /app -u $(USER_ID):$(GROUP_ID) $(CODE_CHALLENGER_IMAGE)
COMPOSER_DOCKER := docker run --rm -v $(CURRENT_DIR):/app -w /app -u $(USER_ID):$(GROUP_ID) $(CODE_CHALLENGER_IMAGE) composer

# Docker Compose
DOCKER_COMPOSE := docker compose
CONTAINER_NAME := CODE_CHALLENGER

# Colors
GREEN := \033[0;32m
YELLOW := \033[1;33m
BLUE := \033[0;34m
RED := \033[0;31m
NC := \033[0m

##@ 📋 Help

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*##"; printf "\n\033[1mUsage:\033[0m\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ 🐳 Docker Management

start: docker-build install-dev

docker-build: ## Build Docker image and install PHP dependencies
	@echo "${GREEN}Building Docker image...${NC}"
	docker build -t $(CODE_CHALLENGER_IMAGE) .
	@echo "${GREEN}Installing PHP dependencies inside container...${NC}"
	$(PHP_DOCKER) composer install --no-dev --optimize-autoloader --no-scripts
	@echo "${GREEN}✅ Docker image ready with dependencies${NC}"

docker-up: ## Start Docker containers
	@echo "${GREEN}Starting Docker containers...${NC}"
	UID=$(USER_ID) GID=$(GROUP_ID) $(DOCKER_COMPOSE) up -d
	@echo "${GREEN}✅ Containers started. API available at: http://localhost:8000${NC}"

docker-down: ## Stop Docker containers
	@echo "${YELLOW}Stopping Docker containers...${NC}"
	$(DOCKER_COMPOSE) down

docker-restart: docker-down docker-up ## Restart Docker containers

docker-status: ## Check containers status
	@echo "${BLUE}Containers status:${NC}"
	$(DOCKER_COMPOSE) ps

docker-logs: ## Show containers logs
	@echo "${GREEN}Showing Docker logs (Ctrl+C to exit)...${NC}"
	$(DOCKER_COMPOSE) logs -f

##@ 📦 Dependencies Management

install: ## Install production dependencies
	@echo "${GREEN}Installing production dependencies...${NC}"
	$(COMPOSER_DOCKER) install --no-dev --optimize-autoloader

install-dev: ## Install all dependencies (including dev)
	@echo "${GREEN}Installing all dependencies...${NC}"
	$(COMPOSER_DOCKER) install

update: ## Update dependencies
	@echo "${GREEN}Updating dependencies...${NC}"
	$(COMPOSER_DOCKER) update

composer-validate: ## Validate composer.json
	@echo "${GREEN}Validating composer.json...${NC}"
	$(COMPOSER_DOCKER) validate --strict

##@ 🧪 Testing

test: ## Run all tests
	@echo "${GREEN}Running all tests...${NC}"
	$(PHP_DOCKER) ./vendor/bin/phpunit

test-unit: ## Run unit tests only
	@echo "${GREEN}Running unit tests...${NC}"
	$(PHP_DOCKER) ./vendor/bin/phpunit --testsuite=unit

test-integration: ## Run integration tests only
	@echo "${GREEN}Running integration tests...${NC}"
	$(PHP_DOCKER) ./vendor/bin/phpunit --testsuite=integration

##@ 🔍 Code Quality

phpstan: ## Run PHPStan static analysis
	@echo "${GREEN}Running PHPStan...${NC}"
	$(PHP_DOCKER) ./vendor/bin/phpstan analyse --memory-limit=-1

phpstan-baseline: ## Generate PHPStan baseline
	@echo "${GREEN}Generating PHPStan baseline...${NC}"
	$(PHP_DOCKER) ./vendor/bin/phpstan analyse --generate-baseline

rector: ## Run Rector (dry-run)
	@echo "${GREEN}Running Rector (dry-run)...${NC}"
	$(PHP_DOCKER) ./vendor/bin/rector process --dry-run

rector-fix: ## Apply Rector fixes
	@echo "${YELLOW}Applying Rector fixes...${NC}"
	$(PHP_DOCKER) ./vendor/bin/rector process

cs-check: ## Check code style
	@echo "${GREEN}Checking code style...${NC}"
	$(PHP_DOCKER) /bin/bash -c "vendor/bin/phpcs -d memory_limit=-1 --standard=./phpcs.xml"

cs-fix: ## Fix code style
	@echo "${GREEN}Fixing code style...${NC}"
	$(PHP_DOCKER) /bin/bash -c "vendor/bin/phpcbf -d memory_limit=-1 --standard=./phpcs.xml"

##@ 🌐 Development Server

serve: docker-up ## Start development server on http://localhost:8000
	@echo "${GREEN}Development server running at http://localhost:8000${NC}"
	@echo "${YELLOW}Use 'make docker-down' to stop or 'make docker-logs' to see logs${NC}"

bash: ## Access container bash
	@if [ $$(docker ps -q -f name=$(CONTAINER_NAME)) ]; then \
		echo "${GREEN}Accessing $(CONTAINER_NAME) container...${NC}"; \
		docker exec -it $(CONTAINER_NAME) bash; \
	else \
		echo "${RED}❌ Container $(CONTAINER_NAME) is not running${NC}"; \
		echo "${YELLOW}💡 Start it with: make docker-up${NC}"; \
	fi

##@ 🚀 Combined Commands

quality: phpstan cs-check ## Run all quality tools
	@echo "${GREEN}✅ All quality checks completed!${NC}"

quality-fix: cs-fix rector-fix ## Fix all code quality issues
	@echo "${GREEN}✅ All fixes applied!${NC}"

ci: install-dev test quality ## Run CI pipeline (install + test + quality)
	@echo "${GREEN}✅ CI pipeline completed successfully!${NC}"

##@ 🧹 Cache & Cleanup

clean: ## Clean cache and temporary files
	@echo "${GREEN}Cleaning cache and temporary files...${NC}"
	rm -rf var/cache/* var/log/* var/coverage/*
	rm -rf .php-cs-fixer.cache

cache-clear: ## Clear Symfony cache
	@echo "${GREEN}Clearing Symfony cache...${NC}"
	$(PHP_DOCKER) php bin/console cache:clear
	$(PHP_DOCKER) php bin/console cache:clear --env=test

##@ 🌐 API Testing

api-test-stats: ## Test /stats endpoint
	@echo "${GREEN}Testing /stats endpoint...${NC}"
	$(PHP_DOCKER) bash -c "\
	curl -s -X POST http://host.docker.internal:8000/stats \
	-H 'Content-Type: application/json' \
	-d '[{\"request_id\":\"test_1\",\"check_in\":\"2020-01-01\",\"nights\":1,\"selling_rate\":50,\"margin\":20}]' \
	| jq . \
	"

api-test-maximize: ## Test /maximize endpoint
	@echo "${GREEN}Testing /maximize endpoint...${NC}"
	$(PHP_DOCKER) bash -c "\
	curl -s -X POST http://host.docker.internal:8000/maximize \
	-H 'Content-Type: application/json' \
	-d '[{\"request_id\":\"test_1\",\"check_in\":\"2020-01-01\",\"nights\":1,\"selling_rate\":50,\"margin\":20}]' \
	| jq . \
	"

api-test-complex: ## Test with complex booking scenario
	@echo "${GREEN}Testing /maximize endpoint with complex scenario...${NC}"
	$(PHP_DOCKER) bash -c "\
	curl -s -X POST http://host.docker.internal:8000/maximize \
	-H 'Content-Type: application/json' \
	-d '[{\"request_id\":\"booking_A\",\"check_in\":\"2020-01-01\",\"nights\":5,\"selling_rate\":200,\"margin\":20},{\"request_id\":\"booking_B\",\"check_in\":\"2020-01-04\",\"nights\":4,\"selling_rate\":156,\"margin\":5},{\"request_id\":\"booking_C\",\"check_in\":\"2020-01-10\",\"nights\":4,\"selling_rate\":160,\"margin\":30}]' \
	| jq . \
	"

##@ 📖 API Documentation

api-docs: ## Start Swagger UI with OpenAPI docs (http://localhost:8081)
	@echo "${GREEN}Starting Swagger UI on http://localhost:8081 ...${NC}"
	docker run --rm -p 8081:8080 \
		-v $(CURRENT_DIR)/docs/openapi.yaml:/docs/openapi.yaml \
		-e SWAGGER_JSON=/docs/openapi.yaml \
		swaggerapi/swagger-ui
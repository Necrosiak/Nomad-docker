# =============================================================================
# NetworkMemories — Metal Gear Online 2 — Makefile
# =============================================================================

DC = docker compose -f docker-compose.yml

.PHONY: help init build run run-daemon stop down logs shell-db backup restore \
        disable-systemd-resolved enable-systemd-resolved

help: ## Show all available commands
	@grep -E '^[a-zA-Z_-]+:.*##' $(MAKEFILE_LIST) | \
	  awk 'BEGIN {FS=":.*##"}; {printf "  \033[36m%-28s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "  \033[33m⚠️  RPCS3 NOTE:\033[0m DNS redirect works for real PS3 only."
	@echo "  \033[33m   RPCS3 requires patched plugin.sprx — see docs/rpcs3-plugin-blocker.md\033[0m"

init: ## First-time setup
	@if [ ! -f .env ]; then cp .env.example .env; \
	  echo "✅ .env created — edit it before continuing!"; \
	else echo "⚠️  .env already exists, skipping."; fi
	@mkdir -p dbdata backups
	@echo "✅ Init done. Edit .env then run: make build"
	@echo ""
	@echo "⚠️  RPCS3 users: read docs/rpcs3-plugin-blocker.md before proceeding."

build: ## Build all containers
	$(DC) build

run: ## Start all services (foreground)
	$(DC) up

run-daemon: ## Start all services (background)
	$(DC) up -d

stop: ## Stop services (keep data)
	$(DC) stop

down: ## Remove containers (keep volumes)
	$(DC) down

down-volumes: ## ⚠️  Remove containers AND volumes (data loss!)
	$(DC) down -v

logs: ## Follow all logs
	$(DC) logs -f

logs-server: ## Nomad Java server logs only
	$(DC) logs -f nomad-server

logs-web: ## Web API / admin logs only
	$(DC) logs -f nomad-web

logs-dns: ## DNS logs only
	$(DC) logs -f nomad-dns

shell-db: ## Open MySQL shell
	$(DC) exec nomad-mysql mysql \
	  -u$$(grep ^MYSQL_USER .env | cut -d= -f2) \
	  -p$$(grep ^MYSQL_PASSWORD .env | cut -d= -f2) \
	  $$(grep ^MYSQL_DATABASE .env | cut -d= -f2)

backup: ## Backup database and data
	@bash scripts/backup.sh

restore: ## Restore from latest backup
	@bash scripts/restore.sh

disable-systemd-resolved: ## Free port 53 (Linux)
	sudo systemctl stop systemd-resolved && sudo systemctl disable systemd-resolved
	@echo "✅ systemd-resolved disabled"

enable-systemd-resolved: ## Re-enable systemd-resolved after shutdown
	sudo systemctl enable systemd-resolved && sudo systemctl start systemd-resolved
	@echo "✅ systemd-resolved re-enabled"

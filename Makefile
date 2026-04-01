# ============================================================
#  Makefile untuk Laravel 12
#  Penggunaan: make <perintah>
# ============================================================

# --- Variabel ---
PHP        := php
COMPOSER   := composer
ARTISAN    := $(PHP) artisan
NPM        := npm

APP_NAME   := laravel-app
ENV_FILE   := .env
ENV_EXAMPLE := .env.example

# Warna untuk output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
CYAN   := \033[0;36m
RESET  := \033[0m

.DEFAULT_GOAL := help

# ============================================================
#  HELP
# ============================================================

.PHONY: help
help: ## Tampilkan daftar perintah yang tersedia
	@echo ""
	@echo "$(CYAN)╔══════════════════════════════════════════╗$(RESET)"
	@echo "$(CYAN)║        Laravel 12 — Makefile Help        ║$(RESET)"
	@echo "$(CYAN)╚══════════════════════════════════════════╝$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-22s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ============================================================
#  INSTALASI & SETUP
# ============================================================

.PHONY: install
install: ## Instalasi lengkap (composer + npm + env + key + migrate)
	@echo "$(CYAN)▶ Instalasi Laravel 12...$(RESET)"
	$(COMPOSER) install --no-interaction --prefer-dist --optimize-autoloader
	@$(MAKE) env
	@$(MAKE) key
	$(NPM) install
	$(NPM) run build
	@$(MAKE) migrate
	@echo "$(GREEN)✔ Instalasi selesai!$(RESET)"

.PHONY: install-dev
install-dev: ## Instalasi untuk lingkungan development
	@echo "$(CYAN)▶ Instalasi mode development...$(RESET)"
	$(COMPOSER) install --no-interaction
	@$(MAKE) env
	@$(MAKE) key
	$(NPM) install
	@$(MAKE) migrate-fresh-seed
	@echo "$(GREEN)✔ Siap untuk development!$(RESET)"

.PHONY: env
env: ## Salin .env.example ke .env (jika belum ada)
	@if [ ! -f $(ENV_FILE) ]; then \
		cp $(ENV_EXAMPLE) $(ENV_FILE); \
		echo "$(GREEN)✔ File .env dibuat dari .env.example$(RESET)"; \
	else \
		echo "$(YELLOW)⚠ File .env sudah ada, dilewati.$(RESET)"; \
	fi

.PHONY: key
key: ## Generate application key
	@echo "$(CYAN)▶ Membuat application key...$(RESET)"
	$(ARTISAN) key:generate
	@echo "$(GREEN)✔ Application key berhasil dibuat$(RESET)"

# ============================================================
#  SERVER DEVELOPMENT
# ============================================================

.PHONY: serve
serve: ## Jalankan server development Laravel (port 8000)
	$(ARTISAN) serve

.PHONY: serve-port
serve-port: ## Jalankan server di port kustom (make serve-port PORT=9000)
	$(ARTISAN) serve --port=$(PORT)

.PHONY: dev
dev: ## Jalankan Laravel + Vite secara bersamaan (development)
	@echo "$(CYAN)▶ Menjalankan server development...$(RESET)"
	$(NPM) run dev &
	$(ARTISAN) serve

# ============================================================
#  DATABASE
# ============================================================

.PHONY: migrate
migrate: ## Jalankan migrasi database
	@echo "$(CYAN)▶ Menjalankan migrasi...$(RESET)"
	$(ARTISAN) migrate --force
	@echo "$(GREEN)✔ Migrasi selesai$(RESET)"

.PHONY: migrate-fresh
migrate-fresh: ## Reset dan jalankan ulang semua migrasi
	@echo "$(YELLOW)⚠ Mereset database...$(RESET)"
	$(ARTISAN) migrate:fresh
	@echo "$(GREEN)✔ Database berhasil direset$(RESET)"

.PHONY: migrate-fresh-seed
migrate-fresh-seed: ## Reset database + jalankan seeder
	@echo "$(YELLOW)⚠ Mereset database dan menjalankan seeder...$(RESET)"
	$(ARTISAN) migrate:fresh --seed
	@echo "$(GREEN)✔ Database direset dan seeder dijalankan$(RESET)"

.PHONY: migrate-rollback
migrate-rollback: ## Rollback migrasi terakhir
	$(ARTISAN) migrate:rollback

.PHONY: migrate-status
migrate-status: ## Cek status migrasi
	$(ARTISAN) migrate:status

.PHONY: seed
seed: ## Jalankan database seeder
	@echo "$(CYAN)▶ Menjalankan seeder...$(RESET)"
	$(ARTISAN) db:seed
	@echo "$(GREEN)✔ Seeder selesai$(RESET)"

.PHONY: db-fresh
db-fresh: ## Alias untuk migrate-fresh-seed
	@$(MAKE) migrate-fresh-seed

# ============================================================
#  CACHE & OPTIMASI
# ============================================================

.PHONY: cache-clear
cache-clear: ## Hapus semua cache aplikasi
	@echo "$(CYAN)▶ Membersihkan cache...$(RESET)"
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear
	$(ARTISAN) event:clear
	@echo "$(GREEN)✔ Semua cache dibersihkan$(RESET)"

.PHONY: cache
cache: ## Cache konfigurasi, route, dan view untuk production
	@echo "$(CYAN)▶ Mem-cache konfigurasi untuk production...$(RESET)"
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(ARTISAN) event:cache
	@echo "$(GREEN)✔ Cache production selesai$(RESET)"

.PHONY: optimize
optimize: ## Optimasi aplikasi untuk production
	@echo "$(CYAN)▶ Mengoptimasi aplikasi...$(RESET)"
	$(COMPOSER) install --no-dev --optimize-autoloader --no-interaction
	$(ARTISAN) optimize
	@echo "$(GREEN)✔ Optimasi selesai$(RESET)"

.PHONY: optimize-clear
optimize-clear: ## Hapus semua file optimasi
	$(ARTISAN) optimize:clear

# ============================================================
#  ASET FRONTEND
# ============================================================

.PHONY: npm-install
npm-install: ## Install dependensi npm
	$(NPM) install

.PHONY: build
build: ## Build aset frontend untuk production
	@echo "$(CYAN)▶ Build aset production...$(RESET)"
	$(NPM) run build
	@echo "$(GREEN)✔ Build selesai$(RESET)"

.PHONY: watch
watch: ## Jalankan Vite dalam mode watch (development)
	$(NPM) run dev

# ============================================================
#  ARTISAN GENERATOR
# ============================================================

.PHONY: make-model
make-model: ## Buat model baru (make make-model NAME=Post)
	$(ARTISAN) make:model $(NAME) -m

.PHONY: make-controller
make-controller: ## Buat controller baru (make make-controller NAME=PostController)
	$(ARTISAN) make:controller $(NAME) --resource

.PHONY: make-migration
make-migration: ## Buat migration baru (make make-migration NAME=create_posts_table)
	$(ARTISAN) make:migration $(NAME)

.PHONY: make-seeder
make-seeder: ## Buat seeder baru (make make-seeder NAME=PostSeeder)
	$(ARTISAN) make:seeder $(NAME)

.PHONY: make-factory
make-factory: ## Buat factory baru (make make-factory NAME=PostFactory)
	$(ARTISAN) make:factory $(NAME)

.PHONY: make-request
make-request: ## Buat form request baru (make make-request NAME=StorePostRequest)
	$(ARTISAN) make:request $(NAME)

.PHONY: make-policy
make-policy: ## Buat policy baru (make make-policy NAME=PostPolicy)
	$(ARTISAN) make:policy $(NAME) --model=$(MODEL)

.PHONY: make-job
make-job: ## Buat job baru (make make-job NAME=ProcessPodcast)
	$(ARTISAN) make:job $(NAME)

.PHONY: make-event
make-event: ## Buat event baru (make make-event NAME=OrderShipped)
	$(ARTISAN) make:event $(NAME)

.PHONY: make-listener
make-listener: ## Buat listener baru (make make-listener NAME=SendShipmentNotification)
	$(ARTISAN) make:listener $(NAME)

.PHONY: make-middleware
make-middleware: ## Buat middleware baru (make make-middleware NAME=CheckAge)
	$(ARTISAN) make:middleware $(NAME)

.PHONY: make-command
make-command: ## Buat Artisan command baru (make make-command NAME=SendEmails)
	$(ARTISAN) make:command $(NAME)

.PHONY: make-resource
make-resource: ## Buat API resource baru (make make-resource NAME=PostResource)
	$(ARTISAN) make:resource $(NAME)

# ============================================================
#  TESTING
# ============================================================

.PHONY: test
test: ## Jalankan semua test
	@echo "$(CYAN)▶ Menjalankan test...$(RESET)"
	$(PHP) artisan test
	@echo "$(GREEN)✔ Test selesai$(RESET)"

.PHONY: test-filter
test-filter: ## Jalankan test spesifik (make test-filter FILTER=LoginTest)
	$(PHP) artisan test --filter=$(FILTER)

.PHONY: test-coverage
test-coverage: ## Jalankan test dengan laporan coverage
	$(PHP) artisan test --coverage

.PHONY: test-parallel
test-parallel: ## Jalankan test secara paralel
	$(PHP) artisan test --parallel

.PHONY: dusk
dusk: ## Jalankan Laravel Dusk browser tests
	$(ARTISAN) dusk

# ============================================================
#  QUEUE & SCHEDULER
# ============================================================

.PHONY: queue
queue: ## Jalankan queue worker
	$(ARTISAN) queue:work

.PHONY: queue-restart
queue-restart: ## Restart semua queue worker
	$(ARTISAN) queue:restart

.PHONY: queue-listen
queue-listen: ## Jalankan queue dalam mode listen (auto-reload)
	$(ARTISAN) queue:listen

.PHONY: schedule
schedule: ## Jalankan task scheduler sekali
	$(ARTISAN) schedule:run

.PHONY: schedule-work
schedule-work: ## Jalankan scheduler terus-menerus (tanpa cron)
	$(ARTISAN) schedule:work

# ============================================================
#  TINKER & DEBUG
# ============================================================

.PHONY: tinker
tinker: ## Buka Laravel Tinker (REPL interaktif)
	$(ARTISAN) tinker

.PHONY: routes
routes: ## Tampilkan semua route yang terdaftar
	$(ARTISAN) route:list

.PHONY: routes-api
routes-api: ## Tampilkan route API saja
	$(ARTISAN) route:list --path=api

.PHONY: about
about: ## Tampilkan informasi aplikasi Laravel
	$(ARTISAN) about

.PHONY: env-check
env-check: ## Cek nilai environment yang aktif
	$(ARTISAN) env

# ============================================================
#  STORAGE & PERMISSION
# ============================================================

.PHONY: storage-link
storage-link: ## Buat symlink storage ke public
	$(ARTISAN) storage:link
	@echo "$(GREEN)✔ Storage symlink dibuat$(RESET)"

.PHONY: permissions
permissions: ## Set permission folder storage dan cache
	@echo "$(CYAN)▶ Mengatur permission...$(RESET)"
	chmod -R 775 storage bootstrap/cache
	@echo "$(GREEN)✔ Permission diatur$(RESET)"

# ============================================================
#  DEPLOYMENT PRODUCTION
# ============================================================

.PHONY: deploy
deploy: ## Deploy ke production (pull + install + migrate + cache)
	@echo "$(CYAN)▶ Memulai proses deployment...$(RESET)"
	git pull origin main
	$(COMPOSER) install --no-dev --optimize-autoloader --no-interaction
	$(NPM) ci
	$(NPM) run build
	$(ARTISAN) migrate --force
	$(ARTISAN) optimize
	$(ARTISAN) queue:restart
	@echo "$(GREEN)✔ Deployment selesai!$(RESET)"

.PHONY: maintenance-on
maintenance-on: ## Aktifkan mode maintenance
	$(ARTISAN) down --render="errors::503" --retry=60
	@echo "$(YELLOW)⚠ Aplikasi dalam mode maintenance$(RESET)"

.PHONY: maintenance-off
maintenance-off: ## Nonaktifkan mode maintenance
	$(ARTISAN) up
	@echo "$(GREEN)✔ Aplikasi kembali online$(RESET)"

# ============================================================
#  CLEANUP
# ============================================================

.PHONY: clean
clean: ## Hapus vendor, node_modules, dan file build
	@echo "$(YELLOW)⚠ Membersihkan direktori...$(RESET)"
	rm -rf vendor node_modules public/build bootstrap/cache/*.php
	@echo "$(GREEN)✔ Direktori dibersihkan$(RESET)"

.PHONY: fresh
fresh: clean install-dev ## Instalasi ulang dari awal (clean + install-dev)

# cakephp-workflow demo — one-command spin-up (SQLite, no DB server needed)
.PHONY: demo install setup serve test reset clean

## Spin up everything and serve at http://localhost:8765/releases
demo: install setup serve

install:
	composer install

setup:
	@mkdir -p tmp logs
	@[ -f config/app_local.php ] || cp config/app_local.example.php config/app_local.php
	bin/cake migrations migrate -p Workflow
	bin/cake migrations migrate

serve:
	@echo ""
	@echo "  ▶  Demo running at  http://localhost:8765/releases"
	@echo ""
	bin/cake server -p 8765

test:
	composer test

## Wipe the SQLite database (start fresh)
reset:
	rm -f tmp/demo.sqlite tmp/demo_test.sqlite
	$(MAKE) setup

clean:
	rm -f tmp/demo.sqlite tmp/demo_test.sqlite config/app_local.php

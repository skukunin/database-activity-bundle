run-tests:
	./vendor/bin/simple-phpunit

run-tests-coverage:
	./vendor/bin/simple-phpunit --coverage-html tests/@coverage

watch-tests:
	./vendor/bin/phpunit-watcher watch

watch-tests-coverage:
	./vendor/bin/phpunit-watcher watch --coverage-html tests/@coverage

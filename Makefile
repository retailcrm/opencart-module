FILE = $(TRAVIS_BUILD_DIR)/VERSION
VERSION = `cat $(FILE)`
ARCHIVE_NAME = '/tmp/retailcrm-'$(VERSION)'.ocmod.zip'

all: build_archive send_to_ftp delete_archive

build_archive:
	zip -r $(ARCHIVE_NAME) ./src/*

send_to_ftp:
	curl -T $(ARCHIVE_NAME) -u $(FTP_USER):$(FTP_PASSWORD) ftp://$(FTP_HOST)

delete_archive:
	rm -f $(ARCHIVE_NAME)

before_script:
	mkdir coverage
	# Change MySQL root password
	echo "USE mysql;\nUPDATE user SET password=PASSWORD('root') WHERE user='root';\nFLUSH PRIVILEGES;\n" | mysql -u root
	composer require --dev beyondit/opencart-test-suite ~$(TEST_SUITE)
	composer require --dev opencart/opencart $(OPENCART)
	composer setup
	bin/robo --load-from tests/RoboFile.php project:deploy
	(php -S localhost:80 -t www &) 2> /dev/null > /dev/null
	sleep 2

coverage:
	wget https://phar.phpunit.de/phpcov-2.0.2.phar && php phpcov-2.0.2.phar merge coverage/ --clover coverage.xml

robo_deploy:
	bin/robo --load-from tests/RoboFile.php project:deploy

run_test:
	composer require --dev beyondit/opencart-test-suite ~$(TEST_SUITE)
	composer require --dev opencart/opencart $(OPENCART)
	composer setup
	bin/robo --load-from tests/RoboFile.php project:deploy
	(php -S localhost:80 -t www &) 2> /dev/null > /dev/null
	sleep 2
	composer test

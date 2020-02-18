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
	# Change MySQL root password
	echo "USE mysql;\nUPDATE user SET password=PASSWORD('root') WHERE user='root';\nFLUSH PRIVILEGES;\n" | mysql -u root
	composer install
	composer setup
	bin/robo --load-from tests/RoboFile.php project:deploy
	(php -S localhost:8000 -t www &) 2> /dev/null > /dev/null
	sleep 2

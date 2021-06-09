## Developing locally

### Prerequisites

1. `sudo apt-get update`
1. `sudo apt-get install composer`
1. `sudo apt-get install php7.3-xml`
1. `sudo apt-get install php7.3-mysql`
1. `sudo apt-get install php7.3-zip`
1. `sudo apt-get install php-mbstring`
1. `sudo apt-get install subversion`
1. `sudo apt-get install mysql-server`
1. `sudo apt-get install php-pear`
1. `sudo pear install PHP_CodeSniffer`

### Bootstrap & Setup

1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `cd wordpress-to-jekyll-exporter`
3. `script/bootstrap`
4. `script/setup`

### Running tests

`script/cibuild`

## Testing locally via Docker

1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `docker-compose up`
3. `open localhost:8088`
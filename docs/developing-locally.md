## Developing locally

### Prerequisites
1. `sudo apt-get update`
1. `sudo apt install composer`
1. `sudo apt install php7.0-xml`
1. `sudo apt install php7.0-mysql`
1. `sudo apt install php7.0-zip`
1. `sudo apt install php-mbstring`
1. `sudo apt install subversion`
1. `sudo apt install mysql-server`
1. `sudo apt install php-pear`
1. `sudo pear install PHP_CodeSniffer`

### Bootstrap & Setup
1. `git clone https://github.com/benbalter/wordpress-to-jekyll-exporter`
2. `cd wordpress-to-jekyll-exporter`
3. `script/bootstrap`
4. `script/setup`

### Running tests
`script/cibuild`

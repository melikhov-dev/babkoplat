Babkoplat
===

1. Install node + npm http://nodejs.org/download/
2. From project root `php -r "eval('?>'.file_get_contents('https://getcomposer.org/installer'));"` (installs `http://getcomposer.org/download/`)
3. Install php vendors `php composer.phar install`
4. Install frontend vendors `cd frontend && npm install`
5. Setup environment and database: `vendor/phing/phing/bin/phing build -Denv=dev -Ddb.file=office3306.ini`

`phing -l` - show available tasks

Configs for application is situated in app/config folder


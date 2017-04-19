# PiggyAuth [![Poggit-CI](https://poggit.pmmp.io/ci.badge/MCPEPIG/PiggyAuth/PiggyAuth/master)](https://poggit.pmmp.io/ci.badge/MCPEPIG/PiggyAuth/PiggyAuth/master)
PiggyAuth is a safe and feature-rich auth plugin for PMMP.

## Information
 - The many features of this plugin can be viewed [here.](https://github.com/MCPEPIG/PiggyAuth/wiki/Features)
 - The changelog of this plugin can be viewed [here.](https://github.com/MCPEPIG/PiggyAuth/wiki/Changelog)
 - You can suggest features and report bugs in [here.](https://github.com/MCPEPIG/PiggyAuth/issues/39)
 - If you want to use the converter, you must change the password length to 200 or more for your database. 

## Installation Guide
 - **Windows & Linux**
   - (WINDOWS) Make sure you have the `php_sqlite3.dll` package installed in the bins folder and `extension=php_sqlite3.dll` is in `php.ini`.
   - (LINUX) Make sure you have the sqlite3 package installed. If you don't, run: `sudo apt-get php7.0-sqlite3`
   - Download PiggyAuth.phar from [Poggit](https://poggit.pmmp.io/ci/MCPEPIG/PiggyAuth) and put it in your plugins folder.
   - Startup the server, and everything should be working.
 - Optional Windows Setup
   - Open `plugins/PiggyAuth/config.yml` and edit to your desired settings.
   - If you are planning to use MySQL, follow [this guide.](https://github.com/MCPEPIG/PiggyAuth/wiki/Databases) guide to setup.
   - If you are planning to use Mailgun, follow [this guide.](https://github.com/MCPEPIG/PiggyAuth/wiki/MailGun) guide to setup.
<!-- 
 - **Linux (TERMINAL)**
   - Run `cd ~/*/plugins` to enter your plugins directory
   - Run `wget ...` to download PiggyAuth.phar
     -  or `cd ~/*/plugins && wget ...`
   - Run `cd ..` to get back into your server directory
   - Run `./start.sh` to startup the server, and everything should be working.
     - or `cd .. && ./start.sh`
 - Optional Linux Setup (TERMINAL)
   - Run `nano ~/*/plugins/PiggyAuth/config.yml` to open the configuration file and edit to your desired settings.
   - If you are planning to use MySQL, follow [this guide.](https://github.com/MCPEPIG/PiggyAuth/wiki/Databases) guide to setup.
   - If you are planning to use Mailgun, follow [this guide.](https://github.com/MCPEPIG/PiggyAuth/wiki/MailGun) guide to setup. 
-->

## Credits
* @thebigsmileXD for fake attributes
* @lolmanz0 for czech translations
* @SleepSpace9 for german translations
* @miguel456 for portuguese translations.
* @Thunder33345 & mojlna for chinese translations.
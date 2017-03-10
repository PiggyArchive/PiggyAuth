# PiggyAuth
PiggyAuth a safe and feature-rich auth plugin for Minecraft: PE v1.0.4, with Async. <br>

## Information
 - The many features of this plugin can be viewed [here](https://github.com/MCPEPIG/PiggyAuth/wiki/Features)
 - The changelog of this plugin can be viewed [here](https://github.com/MCPEPIG/PiggyAuth/wiki/Changelog)
 - You can suggest features and report bugs in [this](https://github.com/MCPEPIG/PiggyAuth/issues/10) issue.
 
## Installation Guide (may move to wiki?)
 - **Windows**
   - Make sure you have the `php_sqlite3.dll` package installed in the bins folder or SQLite3 will not work, which is a package needed by PiggyAuth to save player data.
   - Download PiggyAuth.phar from [Poggit](https://poggit.pmmp.io/ci/MCPEPIG/PiggyAuth) and put it in your plugins folder.
   - Startup the server, and everything should be working.
 - Optional Windows Setup
   - Open `plugins/PiggyAuth/config.yml` and edit to your desired settings.
   - If you are planning to use MySQL, follow [this](https://github.com/MCPEPIG/PiggyAuth/wiki/Databases) guide to setup.
   - If you are planning to use Mailgun, follow [this](https://github.com/MCPEPIG/PiggyAuth/wiki/MailGun) guide to setup. 
 - **Linux**
   - Download PiggyAuth.phar from [Poggit](https://poggit.pmmp.io/ci/MCPEPIG/PiggyAuth) and put it in your plugins folder.
   - Startup the server, and everything should be working.
 - Optional Linux Setup
   - Open `plugins/PiggyAuth/config.yml` and edit to your desired settings.
   - If you are planning to use MySQL, follow [this](https://github.com/MCPEPIG/PiggyAuth/wiki/Databases) guide to setup.
   - If you are planning to use Mailgun, follow [this](https://github.com/MCPEPIG/PiggyAuth/wiki/MailGun) guide to setup. 
 - **Linux (TERM. ONLY)**
   - Run `cd ~/*/plugins` to enter your plugins directory
   - Run `wget ...` to download PiggyAuth.phar (... is placeholder for now)
     -  or `cd ~/*/plugins && wget ...`
   - Run `cd ..` to get back into your server directory
   - Run `./start.sh` to startup the server, and everything should be working.
     - or 'cd .. && ./start.sh`
 - Optional Linux (TERM. ONLY) Setup
   - Run `nano ~/*/plugins/PiggyAuth/config.yml` to open the configuration file and edit to your desired settings.
   - If you are planning to use MySQL, follow [this](https://github.com/MCPEPIG/PiggyAuth/wiki/Databases) guide to setup.
   - If you are planning to use Mailgun, follow [this](https://github.com/MCPEPIG/PiggyAuth/wiki/MailGun) guide to setup. 

## Credits
* @thebigsmileXD for fake attributes

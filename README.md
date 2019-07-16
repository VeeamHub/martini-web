Project Martini: Additional helpdesk and central management feature for Veeam Backup for Microsoft Office 365
==================

## About
Project Martini adds an extra layer on top of existing Veeam Backup for Microsoft Office 365 installations. This can be used as a helpdesk and central management solution. 

It allows the following features:
- Create tenants/GEO based locations
- Deploy Veeam Backup for Microsoft Office 365 in AWS via Terraform
- Manage 1 or more Veeam Backup for Microsoft Office 365 installations
- A web interface which provides central management and self-service restore capabilities
- A command line for automation and initial setup
- An API for integration with 3rd party solutions

## Requirements
- Linux VM (Ubuntu/Debian are fully tested and supported)

## Installation
Project Martini can be installed by leveraging the CLI as explained in [The installation blog](http://blog.dewin.me/2019/06/installing-project-martini.html)

## Configuration
Follow the setup via the CLI.

## Usage
Open a webbrowser and go to index.php. From here you can either login as an admin or a tenant.

## Dependencies for the web interface
Make sure you download dependencies using `composer`. 

For more information on how to install `composer`:
- Linux (https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- Windows (https://getcomposer.org/doc/00-intro.md#installation-windows)

This project leverages a mixture HTML, PHP and Javascript. The following libraries are used:
- [Flatpickr.js](http://flatpickr.js.org/)
- [Font Awesome](http://fontawesome.com/)
- [GuzzleHTTP](https://github.com/guzzle/guzzle)
- [jQuery](https://jquery.com/) 
- [SweetAlert2](https://sweetalert2.github.io)
- [Twitter Bootstrap](http://getbootstrap.com/)

It is required to have a webserver running with PHP5 or higher and the mod_rewrite module enabled. The easiest way to do this is leverage a Linux VM with Apache2.

As an example you can use the following [Linux Ubuntu with Apache guide](https://www.linode.com/docs/web-servers/lamp/install-lamp-stack-on-ubuntu-16-04).

This portal leverages rewrite rules via .htaccess and therefor mod_rewrite needs to be enabled in Apache. More information on this can be found via [Enabling mod_rewrite for Apache running on Linux Ubuntu](https://www.digitalocean.com/community/tutorials/how-to-rewrite-urls-with-mod_rewrite-for-apache-on-ubuntu-16-04).

**Important step**

Disable MultiView within the directory document root for Apache. This can be done my modifying the default site configuration and set it as below:
```
<Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
</Directory>
```
**It is advised to increase or disable the PHP maximum execution time limit.**
This can modified in the php.ini file as described per [changing the maximum execution time limit](https://www.simplified.guide/php/increase-max-execution-time)

## Questions and feature request
Please use the GitHub issue tracker(https://github.com/veeamhub/martini/issues) for any questions or feature requests.

## Distributed under MIT license
Copyright (c) 2019 VeeamHUB

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
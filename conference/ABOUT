 copyright (c) 2013 SUBNETS.RU project (Moscow, Russia)
 Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
 ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 SUCH DAMAGE.

##    INTRODUCTION
Asterisk version 12 and 13 have [ARI] (https://wiki.asterisk.org/wiki/pages/viewpage.action?pageId=29395573)

This is an example how to made conference between multiply users using ARI and nodeJS.

## Required
* Asterisk 12 or Asterisk 13
* nodeJS
* npm

## Install
* configure Asterisk: enable HTTP + configure ARI and dialplan (see examples of configuration files in "asterisk" directory)
* download project zip archive
* extract files to www folder (ex. /usr/local/www/conf)
* install nodeJS
```html
Debian: apt-get install nodejs
FreeBSD: cd /usr/ports/www/node && make install clean
```
* install nmp
```html
Debian: apt-get install npm
FreeBSD: cd /usr/ports/www/npm && make install clean
```
* install required javascript packages with npm
```html
cd /usr/local/www/conf/ws_server
nmp install ws
nmp install request
```
* edit file js/options.js (replace localhost with your FQDN where webp page will be located)
* edit file ws_server/ws_server.options.js (edit ARI info)

## Use
* start server
```html
Debian:
cd /usr/local/www/conf/ws_server
nodejs ws_server.js
FreeBSD:
cd /usr/local/www/conf/ws_server
node ws_server.js
```
* open web page http://yourFQDN/
* call to you conference exten

---
With best regards, 
Meganet-2003 IT team
WWW: www.mega-net.ru

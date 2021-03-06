INTRODUCTION

This package provides a basic router for the PHP CLI web server.  You might
use it as follows:

  php -S localhost:8000 src/router.php

FEATURES

  * index.xhtml is a possible directory index.

  * More content types are available.
  
  /etc/mime.types is used, on systems that have it, to map extensions to
  content types.  On systems that lack this file, an internal copy is used.

OPERATING SYSTEMS

The router should work on all OSes supported by PHP.  However, at time of
writing, the software has only been tested on Unix.

INSTALLATION

Install with Composer (the package name is phormio/cli-server-router) or
just clone the repository.

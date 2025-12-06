#ddev-generated
# Install pcov for fast code coverage (faster than Xdebug)
ARG BASE_IMAGE
FROM $BASE_IMAGE

# Install pcov using apt (available in Debian packages for PHP 8.x)
RUN apt-get update && apt-get install -y --no-install-recommends php${DDEV_PHP_VERSION}-pcov && apt-get clean && rm -rf /var/lib/apt/lists/*

# Custom Dockerfile for TYPO3 contexts extension development
# Adds additional tools needed for extension development

# Install additional PHP extensions and tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    graphviz \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install global composer tools for extension development
RUN composer global require --no-interaction \
    friendsofphp/php-cs-fixer \
    phpstan/phpstan \
    rector/rector

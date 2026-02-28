#!/usr/bin/env bash

#
# nr-contexts test runner based on docker/podman.
# Following TYPO3 core testing conventions.
#

trap 'cleanUp;exit 2' SIGINT

waitFor() {
    local HOST=${1}
    local PORT=${2}
    local TESTCOMMAND="
        COUNT=0;
        while ! nc -z ${HOST} ${PORT}; do
            if [ \"\${COUNT}\" -gt 10 ]; then
              echo \"Can not connect to ${HOST} port ${PORT}. Aborting.\";
              exit 1;
            fi;
            sleep 1;
            COUNT=\$((COUNT + 1));
        done;
    "
    ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name wait-for-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_ALPINE} /bin/sh -c "${TESTCOMMAND}"
    if [[ $? -gt 0 ]]; then
        kill -SIGINT -$$
    fi
}

cleanUp() {
    ATTACHED_CONTAINERS=$(${CONTAINER_BIN} ps --filter network=${NETWORK} --format='{{.Names}}' 2>/dev/null)
    for ATTACHED_CONTAINER in ${ATTACHED_CONTAINERS}; do
        ${CONTAINER_BIN} rm -f ${ATTACHED_CONTAINER} >/dev/null 2>&1
    done
    ${CONTAINER_BIN} network rm ${NETWORK} >/dev/null 2>&1
}

cleanCacheFiles() {
    echo -n "Clean caches ... "
    rm -rf \
        .Build/.cache \
        .php-cs-fixer.cache \
        Build/.phpunit.cache
    echo "done"
}

loadHelp() {
    # Load help text into $HELP
    read -r -d '' HELP <<EOF
nr-contexts test runner. Execute tests and code quality tools in Docker containers.

Usage: $0 [options] [file]

Options:
    -s <...>
        Specifies which test suite to run
            - cgl: PHP-CS-Fixer code style check and fix
            - clean: Clean temporary files
            - cleanCache: Clean cache folders
            - composer: "composer" with all remaining arguments dispatched
            - composerUpdate: "composer update" (fresh install)
            - composerValidate: "composer validate"
            - functional: PHP functional tests
            - functionalCoverage: PHP functional tests with coverage (SQLite only)
            - fuzz: PHP fuzz testing with php-fuzzer (discovers Tests/Fuzz/*Target.php)
            - lint: PHP syntax linting
            - mutation: Mutation testing with Infection
            - phpstan: PHPStan static analysis
            - phpstanBaseline: Regenerate PHPStan baseline
            - rector: Apply Rector rules
            - unit: PHP unit tests (default)
            - unitCoverage: PHP unit tests with coverage

    -b <docker|podman>
        Container environment:
            - docker
            - podman

        If not specified, podman will be used if available. Otherwise, docker is used.

    -a <mysqli|pdo_mysql>
        Only with -s functional|functionalCoverage
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql

    -d <sqlite|mariadb|mysql|postgres>
        Only with -s functional
        Specifies on which DBMS tests are performed
            - sqlite: (default): use sqlite
            - mariadb: use mariadb
            - mysql: use MySQL
            - postgres: use postgres

    -i version
        Specify a specific database version
        With "-d mariadb":
            - 10.5   long-term
            - 10.11  long-term (default)
            - 11.4   long-term
        With "-d mysql":
            - 8.0    (default) LTS
            - 8.4    LTS
            - 9.0    Innovation
        With "-d postgres":
            - 15
            - 16     (default)
            - 17

    -p <8.2|8.3|8.4|8.5>
        Specifies the PHP minor version to be used
            - 8.2: (default) use PHP 8.2
            - 8.3: use PHP 8.3
            - 8.4: use PHP 8.4
            - 8.5: use PHP 8.5

    -x
        Only with -s functional|unit
        Send information to host instance for test or system under test break points.
        This is especially useful if a local PhpStorm instance is listening on default
        xdebug port 9003. A different port can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like
        PhpStorm is not listening on default port.

    -n
        Only with -s cgl, rector
        Activate dry-run that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest container images and remove dangling
        local volumes.

    -h
        Show this help.

Examples:
    # Run unit tests using PHP 8.2
    ./Build/Scripts/runTests.sh -s unit

    # Run unit tests with code coverage
    ./Build/Scripts/runTests.sh -s unitCoverage

    # Run functional tests using PHP 8.2 and SQLite (default)
    ./Build/Scripts/runTests.sh -s functional

    # Run functional tests with code coverage
    ./Build/Scripts/runTests.sh -s functionalCoverage

    # Run functional tests using PHP 8.4 and MariaDB 10.11 using pdo_mysql
    ./Build/Scripts/runTests.sh -p 8.4 -s functional -d mariadb -i 10.11 -a pdo_mysql

    # Run functional tests on postgres with xdebug
    ./Build/Scripts/runTests.sh -x -s functional -d postgres -- Tests/Functional/SomeTest.php

    # Run PHPStan analysis
    ./Build/Scripts/runTests.sh -s phpstan

    # Run PHP-CS-Fixer in dry-run mode
    ./Build/Scripts/runTests.sh -s cgl -n

    # Run PHP-CS-Fixer and fix files
    ./Build/Scripts/runTests.sh -s cgl

    # Run Rector in dry-run mode
    ./Build/Scripts/runTests.sh -s rector -n

    # Run mutation testing
    ./Build/Scripts/runTests.sh -s mutation

    # Run lint check
    ./Build/Scripts/runTests.sh -s lint

    # Update container images
    ./Build/Scripts/runTests.sh -u
EOF
}

handleDbmsOptions() {
    # -a, -d, -i depend on each other. Validate input combinations and set defaults.
    case ${DBMS} in
        mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10.11"
            if ! [[ ${DBMS_VERSION} =~ ^(10.5|10.11|11.4)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        mysql)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="8.0"
            if ! [[ ${DBMS_VERSION} =~ ^(8.0|8.4|9.0)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        postgres)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="16"
            if ! [[ ${DBMS_VERSION} =~ ^(15|16|17)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            if [ -n "${DBMS_VERSION}" ]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        *)
            echo "Invalid option -d ${DBMS}" >&2
            echo >&2
            echo "Use \"Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
            exit 1
            ;;
    esac
}

# Test if docker exists, else exit out with error
if ! type "docker" >/dev/null 2>&1 && ! type "podman" >/dev/null 2>&1; then
    echo "This script relies on docker or podman. Please install" >&2
    exit 1
fi

# Option defaults
TEST_SUITE="unit"
DATABASE_DRIVER=""
DBMS="sqlite"
DBMS_VERSION=""
PHP_VERSION="8.2"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
CGLCHECK_DRY_RUN=0
CI_PARAMS="${CI_PARAMS:-}"
CONTAINER_BIN=""
CONTAINER_HOST="host.docker.internal"
EXTRA_TEST_OPTIONS="${EXTRA_TEST_OPTIONS:-}"

# Option parsing updates above default vars
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=()
# Simple option parsing based on getopts (! not getopt)
while getopts "a:b:d:i:s:p:xy:nhu" OPT; do
    case ${OPT} in
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        s)
            TEST_SUITE=${OPTARG}
            ;;
        b)
            if ! [[ ${OPTARG} =~ ^(docker|podman)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            CONTAINER_BIN=${OPTARG}
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            DBMS_VERSION=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.2|8.3|8.4|8.5)$ ]]; then
                INVALID_OPTIONS+=("p ${OPTARG}")
            fi
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        n)
            CGLCHECK_DRY_RUN=1
            ;;
        h)
            loadHelp
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "call \"Build/Scripts/runTests.sh -h\" to display help and valid options"
    exit 1
fi

handleDbmsOptions

COMPOSER_ROOT_VERSION="4.x-dev"
HOST_UID=$(id -u)
USERSET=""
if [ $(uname) != "Darwin" ]; then
    USERSET="--user $HOST_UID"
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called, then go up two dirs.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1
cd ../../ || exit 1
ROOT_DIR="${PWD}"

# Create .cache dir: composer need this.
mkdir -p .Build/.cache
mkdir -p .Build/web/typo3temp/var/tests

IMAGE_PREFIX="docker.io/"
# Non-CI fetches TYPO3 images (php) from ghcr.io
TYPO3_IMAGE_PREFIX="ghcr.io/typo3/"
CONTAINER_INTERACTIVE="-it --init"

IS_CORE_CI=0
# ENV var "CI" is set by gitlab-ci/github-actions. We use it here to distinct 'local' and 'CI' environment.
# Also detect non-TTY environment (piped output, background jobs) and disable interactive mode.
if [ "${CI}" == "true" ] || ! [ -t 0 ]; then
    IS_CORE_CI=1
    IMAGE_PREFIX=""
    CONTAINER_INTERACTIVE=""
fi

# determine default container binary to use: 1. podman 2. docker
if [[ -z "${CONTAINER_BIN}" ]]; then
    if type "podman" >/dev/null 2>&1; then
        CONTAINER_BIN="podman"
    elif type "docker" >/dev/null 2>&1; then
        CONTAINER_BIN="docker"
    fi
fi

IMAGE_PHP="${TYPO3_IMAGE_PREFIX}core-testing-$(echo "php${PHP_VERSION}" | sed -e 's/\.//'):latest"
IMAGE_ALPINE="${IMAGE_PREFIX}alpine:3.8"
IMAGE_MARIADB="docker.io/mariadb:${DBMS_VERSION}"
IMAGE_MYSQL="docker.io/mysql:${DBMS_VERSION}"
IMAGE_POSTGRES="docker.io/postgres:${DBMS_VERSION}-alpine"

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))

SUFFIX=$(echo $RANDOM)
NETWORK="nr-contexts-${SUFFIX}"
${CONTAINER_BIN} network create ${NETWORK} >/dev/null

if [ ${CONTAINER_BIN} = "docker" ]; then
    # docker needs the add-host for xdebug remote debugging. podman has host.container.internal built in
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host "${CONTAINER_HOST}:host-gateway" ${USERSET} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
else
    # podman
    CONTAINER_HOST="host.containers.internal"
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm --network ${NETWORK} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
fi

if [ ${PHP_XDEBUG_ON} -eq 0 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=" "
else
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="client_port=${PHP_XDEBUG_PORT} client_host=${CONTAINER_HOST}"
fi

# PHP CLI performance options: enable opcache and JIT for faster execution
PHP_OPCACHE_OPTS="-d opcache.enable_cli=1 -d opcache.jit=1255 -d opcache.jit_buffer_size=128M"

# Suite execution
case ${TEST_SUITE} in
    cgl)
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            COMMAND="php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/php-cs-fixer fix -v --config=Build/php-cs-fixer.php --dry-run --diff"
        else
            COMMAND="php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/php-cs-fixer fix -v --config=Build/php-cs-fixer.php"
        fi
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    clean)
        cleanCacheFiles
        echo -n "Clean .Build ... "
        rm -rf .Build
        echo "done"
        SUITE_EXIT_CODE=0
        ;;
    cleanCache)
        cleanCacheFiles
        SUITE_EXIT_CODE=0
        ;;
    composer)
        COMMAND=(composer "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    composerUpdate)
        rm -rf vendor/ composer.lock
        COMMAND=(composer install --no-ansi --no-interaction --no-progress)
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-install-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    composerValidate)
        COMMAND=(composer validate "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-command-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    functional)
        COMMAND=(php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/phpunit -c Build/phpunit/FunctionalTests.xml --exclude-group not-${DBMS} ${EXTRA_TEST_OPTIONS} "$@")
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                ${CONTAINER_BIN} run --rm ${CI_PARAMS} --name mariadb-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MARIADB} >/dev/null
                waitFor mariadb-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                ${CONTAINER_BIN} run --rm ${CI_PARAMS} --name mysql-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MYSQL} >/dev/null
                waitFor mysql-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                ${CONTAINER_BIN} run --rm ${CI_PARAMS} --name postgres-func-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid ${IMAGE_POSTGRES} >/dev/null
                waitFor postgres-func-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # create sqlite tmpfs mount typo3temp/var/tests/functional-sqlite-dbs/ to avoid permission issues
                mkdir -p "${ROOT_DIR}/.Build/web/typo3temp/var/tests/functional-sqlite-dbs/"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${ROOT_DIR}/.Build/web/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;
    functionalCoverage)
        mkdir -p .Build/coverage
        # Coverage requires xdebug, no JIT (JIT is incompatible with xdebug coverage)
        COMMAND=(php -d opcache.enable_cli=1 vendor/bin/phpunit -c Build/phpunit/FunctionalTests.xml --coverage-clover=.Build/coverage/functional.xml --coverage-html=.Build/coverage/html-functional --coverage-text ${EXTRA_TEST_OPTIONS} "$@")
        # Functional coverage only runs with SQLite for simplicity
        mkdir -p "${ROOT_DIR}/.Build/web/typo3temp/var/tests/functional-sqlite-dbs/"
        CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${ROOT_DIR}/.Build/web/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-coverage-${SUFFIX} -e XDEBUG_MODE=coverage ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    fuzz)
        FUZZ_MAX_RUNS="${FUZZ_MAX_RUNS:-10000}"
        COMMAND="
            FUZZ_EXIT=0;
            for target in Tests/Fuzz/*Target.php; do
                if [ -f \"\${target}\" ]; then
                    target_name=\$(basename \"\${target}\" .php);
                    corpus_name=\$(echo \"\${target_name}\" | sed 's/Target\$//' | tr '[:upper:]' '[:lower:]');
                    corpus_dir=\"Tests/Fuzz/corpus/\${corpus_name}\";
                    echo \">>> Fuzzing: \${target_name}\";
                    if [ -d \"\${corpus_dir}\" ]; then
                        php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/php-fuzzer fuzz \"\${target}\" \"\${corpus_dir}\" --max-runs ${FUZZ_MAX_RUNS} || FUZZ_EXIT=1;
                    else
                        php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/php-fuzzer fuzz \"\${target}\" --max-runs ${FUZZ_MAX_RUNS} || FUZZ_EXIT=1;
                    fi;
                fi;
            done;
            exit \${FUZZ_EXIT};
        "
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name fuzz-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lint)
        COMMAND="find . -name \\*.php ! -path \"./.Build/*\" ! -path \"./vendor/*\" -print0 | xargs -0 -n1 -P\$(nproc) php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off -l >/dev/null"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    mutation)
        # Mutation testing requires coverage, no JIT (JIT is incompatible with xdebug coverage)
        COMMAND=(php -d opcache.enable_cli=1 vendor/bin/infection --configuration=infection.json5 --threads=4 "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name mutation-${SUFFIX} -e XDEBUG_MODE=coverage ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    phpstan)
        COMMAND="php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/phpstan analyse -c Build/phpstan.neon"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    phpstanBaseline)
        COMMAND="php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/phpstan analyse -c Build/phpstan.neon --generate-baseline -v"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    rector)
        if [ "${CGLCHECK_DRY_RUN}" -eq 1 ]; then
            COMMAND=(php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/rector --config=Build/rector.php --dry-run --clear-cache "$@")
        else
            COMMAND=(php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/rector --config=Build/rector.php --clear-cache "$@")
        fi
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name rector-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    unit)
        COMMAND=(php ${PHP_OPCACHE_OPTS} -dxdebug.mode=off vendor/bin/phpunit -c Build/phpunit/UnitTests.xml ${EXTRA_TEST_OPTIONS} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    unitCoverage)
        mkdir -p .Build/coverage
        # Coverage requires xdebug, no JIT (JIT is incompatible with xdebug coverage)
        COMMAND=(php -d opcache.enable_cli=1 vendor/bin/phpunit -c Build/phpunit/UnitTests.xml --coverage-clover=.Build/coverage/unit.xml --coverage-html=.Build/coverage/html-unit --coverage-text ${EXTRA_TEST_OPTIONS} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-coverage-${SUFFIX} -e XDEBUG_MODE=coverage ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    update)
        # pull typo3/core-testing-* versions of those ones that exist locally
        echo "> pull ${TYPO3_IMAGE_PREFIX}core-testing-* versions of those ones that exist locally"
        ${CONTAINER_BIN} images "${TYPO3_IMAGE_PREFIX}core-testing-*" --format "{{.Repository}}:{{.Tag}}" | xargs -I {} ${CONTAINER_BIN} pull {}
        echo ""
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        echo "> remove \"dangling\" ${TYPO3_IMAGE_PREFIX}/core-testing-* images (those tagged as <none>)"
        ${CONTAINER_BIN} images --filter "reference=${TYPO3_IMAGE_PREFIX}/core-testing-*" --filter "dangling=true" --format "{{.ID}}" | xargs -I {} ${CONTAINER_BIN} rmi -f {}
        echo ""
        SUITE_EXIT_CODE=0
        ;;
    *)
        loadHelp
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
        ;;
esac

cleanUp

# Print summary
echo "" >&2
echo "###########################################################################" >&2
echo "Result of ${TEST_SUITE}" >&2
echo "Container runtime: ${CONTAINER_BIN}" >&2
if [[ ${IS_CORE_CI} -eq 1 ]]; then
    echo "Environment: CI" >&2
else
    echo "Environment: local" >&2
fi
echo "PHP: ${PHP_VERSION}" >&2
if [[ ${TEST_SUITE} =~ ^functional(Coverage)?$ ]]; then
    case "${DBMS}" in
        mariadb|mysql)
            echo "DBMS: ${DBMS}  version ${DBMS_VERSION}  driver ${DATABASE_DRIVER}" >&2
            ;;
        postgres)
            echo "DBMS: ${DBMS}  version ${DBMS_VERSION}  driver pdo_pgsql" >&2
            ;;
        sqlite)
            echo "DBMS: ${DBMS}  driver pdo_sqlite" >&2
            ;;
    esac
fi
if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
    echo "SUCCESS" >&2
else
    echo "FAILURE" >&2
fi
echo "###########################################################################" >&2
echo "" >&2

# Exit with code of test suite - This script return non-zero if the executed test failed.
exit $SUITE_EXIT_CODE

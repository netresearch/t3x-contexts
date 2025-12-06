#!/usr/bin/env bash

#
# TYPO3 extension test runner script
#
# Usage: ./Build/Scripts/runTests.sh [options] [suite]
#
# Options:
#   -p <version>  PHP version (8.2, 8.3, 8.4) - default: 8.2
#   -t <version>  TYPO3 version (12, 13) - default: 12
#   -x            Enable xdebug for debugging
#   -v            Verbose output
#   -h            Show this help
#
# Suites:
#   unit          Run unit tests (default)
#   functional    Run functional tests
#   lint          Run linting (PHPStan, PHP-CS-Fixer)
#   cgl           Run PHP-CS-Fixer (check only)
#   cglfix        Run PHP-CS-Fixer (fix)
#   phpstan       Run PHPStan static analysis
#   rector        Run Rector (dry-run)
#   rectorfix     Run Rector (apply changes)
#   mutation      Run mutation testing with Infection
#   fuzz          Run fuzz testing with php-fuzzer
#   all           Run all test suites
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"

# Default values
PHP_VERSION="8.2"
TYPO3_VERSION="12"
XDEBUG=""
VERBOSE=""
SUITE="unit"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_help() {
    echo "TYPO3 Extension Test Runner"
    echo ""
    echo "Usage: $0 [options] [suite]"
    echo ""
    echo "Options:"
    echo "  -p <version>  PHP version (8.2, 8.3, 8.4) - default: 8.2"
    echo "  -t <version>  TYPO3 version (12, 13) - default: 12"
    echo "  -x            Enable xdebug for debugging"
    echo "  -v            Verbose output"
    echo "  -h            Show this help"
    echo ""
    echo "Suites:"
    echo "  unit          Run unit tests (default)"
    echo "  functional    Run functional tests"
    echo "  lint          Run all linting tools"
    echo "  cgl           Run PHP-CS-Fixer (check only)"
    echo "  cglfix        Run PHP-CS-Fixer (fix)"
    echo "  phpstan       Run PHPStan static analysis"
    echo "  rector        Run Rector (dry-run)"
    echo "  rectorfix     Run Rector (apply changes)"
    echo "  mutation      Run mutation testing with Infection"
    echo "  fuzz          Run fuzz testing with php-fuzzer"
    echo "  all           Run all test suites"
    echo ""
}

# Parse command line arguments
while getopts "p:t:xvh" opt; do
    case ${opt} in
        p)
            PHP_VERSION="${OPTARG}"
            ;;
        t)
            TYPO3_VERSION="${OPTARG}"
            ;;
        x)
            XDEBUG="-dxdebug.mode=debug -dxdebug.start_with_request=yes"
            ;;
        v)
            VERBOSE="--verbose"
            ;;
        h)
            print_help
            exit 0
            ;;
        \?)
            print_help
            exit 1
            ;;
    esac
done

shift $((OPTIND - 1))
SUITE="${1:-unit}"

cd "${ROOT_DIR}"

# Ensure dependencies are installed
if [[ ! -d vendor ]]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install --prefer-dist --no-progress
fi

echo -e "${GREEN}Running suite: ${SUITE}${NC}"
echo -e "PHP: ${PHP_VERSION}, TYPO3: ${TYPO3_VERSION}"
echo ""

# Set database credentials for functional tests (DDEV or CI environment)
setup_database_env() {
    if [[ -z "${typo3DatabaseDriver:-}" ]]; then
        # Check if running in DDEV
        if [[ -n "${DDEV_PROJECT:-}" ]] || [[ -f /.dockerenv && -n "${DDEV_HOSTNAME:-}" ]]; then
            export typo3DatabaseDriver="pdo_mysql"
            export typo3DatabaseHost="db"
            export typo3DatabaseUsername="db"
            export typo3DatabasePassword="db"
            export typo3DatabaseName="db"
        # Check for GitHub Actions or other CI
        elif [[ -n "${CI:-}" ]]; then
            export typo3DatabaseDriver="pdo_sqlite"
        fi
    fi
}

case ${SUITE} in
    unit)
        echo -e "${GREEN}>>> Running Unit Tests${NC}"
        php ${XDEBUG} vendor/bin/phpunit -c Build/phpunit/UnitTests.xml ${VERBOSE}
        ;;
    functional)
        echo -e "${GREEN}>>> Running Functional Tests${NC}"
        setup_database_env
        php ${XDEBUG} vendor/bin/phpunit -c Build/phpunit/FunctionalTests.xml ${VERBOSE}
        ;;
    lint)
        echo -e "${GREEN}>>> Running PHPStan${NC}"
        vendor/bin/phpstan analyse -c phpstan.neon ${VERBOSE}
        echo ""
        echo -e "${GREEN}>>> Running PHP-CS-Fixer (check)${NC}"
        vendor/bin/php-cs-fixer fix --dry-run --diff ${VERBOSE}
        ;;
    cgl)
        echo -e "${GREEN}>>> Running PHP-CS-Fixer (check)${NC}"
        vendor/bin/php-cs-fixer fix --dry-run --diff ${VERBOSE}
        ;;
    cglfix)
        echo -e "${GREEN}>>> Running PHP-CS-Fixer (fix)${NC}"
        vendor/bin/php-cs-fixer fix ${VERBOSE}
        ;;
    phpstan)
        echo -e "${GREEN}>>> Running PHPStan${NC}"
        vendor/bin/phpstan analyse -c phpstan.neon ${VERBOSE}
        ;;
    rector)
        echo -e "${GREEN}>>> Running Rector (dry-run)${NC}"
        vendor/bin/rector process --dry-run ${VERBOSE}
        ;;
    rectorfix)
        echo -e "${GREEN}>>> Running Rector (apply)${NC}"
        vendor/bin/rector process ${VERBOSE}
        ;;
    mutation)
        echo -e "${GREEN}>>> Running Mutation Testing${NC}"
        vendor/bin/infection --threads=4 --min-msi=75 --min-covered-msi=80
        ;;
    fuzz)
        FUZZ_TARGET="${2:-all}"
        FUZZ_MAX_RUNS="${3:-10000}"
        echo -e "${GREEN}>>> Running Fuzz Testing${NC}"
        if [[ "${FUZZ_TARGET}" == "all" ]]; then
            echo -e "${YELLOW}Running all fuzz targets...${NC}"
            for target in Tests/Fuzz/*Target.php; do
                if [[ -f "${target}" ]]; then
                    target_name=$(basename "${target}" .php)
                    corpus_name=$(echo "${target_name}" | sed 's/Target$//' | tr '[:upper:]' '[:lower:]')
                    corpus_dir="Tests/Fuzz/corpus/${corpus_name}"
                    if [[ -d "${corpus_dir}" ]]; then
                        echo -e "${GREEN}>>> Fuzzing: ${target_name}${NC}"
                        vendor/bin/php-fuzzer fuzz "${target}" "${corpus_dir}" --max-runs "${FUZZ_MAX_RUNS}" || true
                    else
                        echo -e "${YELLOW}Skipping ${target_name}: no corpus at ${corpus_dir}${NC}"
                    fi
                fi
            done
        else
            corpus_dir="Tests/Fuzz/corpus/$(basename "${FUZZ_TARGET}" Target.php | tr '[:upper:]' '[:lower:]')"
            vendor/bin/php-fuzzer fuzz "${FUZZ_TARGET}" "${corpus_dir}" --max-runs "${FUZZ_MAX_RUNS}"
        fi
        ;;
    all)
        echo -e "${GREEN}>>> Running All Suites${NC}"
        echo ""

        echo -e "${GREEN}>>> 1/4 PHPStan${NC}"
        vendor/bin/phpstan analyse -c phpstan.neon || true
        echo ""

        echo -e "${GREEN}>>> 2/4 PHP-CS-Fixer${NC}"
        vendor/bin/php-cs-fixer fix --dry-run --diff || true
        echo ""

        echo -e "${GREEN}>>> 3/4 Unit Tests${NC}"
        php vendor/bin/phpunit -c Build/phpunit/UnitTests.xml || true
        echo ""

        echo -e "${GREEN}>>> 4/4 Functional Tests${NC}"
        setup_database_env
        php vendor/bin/phpunit -c Build/phpunit/FunctionalTests.xml || true
        echo ""

        echo -e "${GREEN}All suites completed${NC}"
        ;;
    *)
        echo -e "${RED}Unknown suite: ${SUITE}${NC}"
        print_help
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}Done!${NC}"

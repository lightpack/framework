#!/bin/bash

# Lightpack Test Runner
# Runs all test suites individually to verify isolation
#
# Setup:
#   chmod +x run-tests.sh    # Make executable (first time only)
#
# Usage:
#   ./run-tests.sh           # Run all suites (summary only)
#   ./run-tests.sh --verbose # Run all suites (show all output)
#   ./run-tests.sh -v        # Same as --verbose

VERBOSE=false
if [[ "$1" == "--verbose" ]] || [[ "$1" == "-v" ]]; then
    VERBOSE=true
fi

SUITES=(
    "AI"
    "Audit"
    "Auth"
    "Cable"
    "Cache"
    "Captcha"
    "Config"
    "Console"
    "Container"
    "Database"
    "Event"
    "Factory"
    "Faker"
    "File"
    "Filters"
    "Http"
    "Jobs"
    "Logger"
    "Mfa"
    "Pdf"
    "Rbac"
    "Redis"
    "Routing"
    "Schedule"
    "Secrets"
    "Session"
    "Settings"
    "SocialAuth"
    "Storage"
    "Tags"
    "Taxonomies"
    "Uploads"
    "Utils"
    "Validation"
    "View"
    "Webhook"
)

echo "========================================="
echo "Running Lightpack Test Suites"
echo "========================================="
echo ""

FAILED_SUITES=()
PASSED_SUITES=()

for suite in "${SUITES[@]}"; do
    echo "Running $suite tests..."
    
    if [ "$VERBOSE" = true ]; then
        # Show full output
        if ./vendor/bin/phpunit --testsuite "$suite"; then
            echo "✓ $suite passed"
            PASSED_SUITES+=("$suite")
        else
            echo "✗ $suite failed"
            FAILED_SUITES+=("$suite")
        fi
    else
        # Suppress output for summary mode
        if ./vendor/bin/phpunit --testsuite "$suite" > /dev/null 2>&1; then
            echo "✓ $suite passed"
            PASSED_SUITES+=("$suite")
        else
            echo "✗ $suite failed"
            FAILED_SUITES+=("$suite")
        fi
    fi
    echo ""
done

echo "========================================="
echo "Test Summary"
echo "========================================="
echo "Passed: ${#PASSED_SUITES[@]}/${#SUITES[@]}"
echo "Failed: ${#FAILED_SUITES[@]}/${#SUITES[@]}"

if [ ${#FAILED_SUITES[@]} -gt 0 ]; then
    echo ""
    echo "Failed suites:"
    for suite in "${FAILED_SUITES[@]}"; do
        echo "  - $suite"
    done
    exit 1
fi

echo ""
echo "All test suites passed! ✓"

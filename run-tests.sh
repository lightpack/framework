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
    # "Http"  # Excluded - integration tests requiring external network
    "Jobs"
    "Logger"
    "Mfa"
    "Mail"
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
FAILURE_DETAILS=()

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
        # Capture output for summary mode
        OUTPUT=$(./vendor/bin/phpunit --testsuite "$suite" 2>&1)
        EXIT_CODE=$?
        
        if [ $EXIT_CODE -eq 0 ]; then
            echo "✓ $suite passed"
            PASSED_SUITES+=("$suite")
        else
            echo "✗ $suite failed"
            FAILED_SUITES+=("$suite")
            
            # Extract failure details (lines with ✘ and following context)
            FAILURES=$(echo "$OUTPUT" | grep -A 5 "✘\|FAILURES!\|Failed asserting")
            if [ -n "$FAILURES" ]; then
                FAILURE_DETAILS+=("=== $suite ===")
                FAILURE_DETAILS+=("$FAILURES")
                FAILURE_DETAILS+=("")
            fi
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
    
    # Show detailed failure information
    if [ ${#FAILURE_DETAILS[@]} -gt 0 ]; then
        echo ""
        echo "========================================="
        echo "Failure Details"
        echo "========================================="
        for detail in "${FAILURE_DETAILS[@]}"; do
            echo "$detail"
        done
    fi
    
    echo ""
    echo "Tip: Run './run-tests.sh -v' for full output or"
    echo "     './vendor/bin/phpunit --testsuite <suite-name>' for specific suite"
    exit 1
fi

echo ""
echo "All test suites passed! ✓"

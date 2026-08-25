#!/bin/bash

# Build script for Orchestra Core plugin
# Creates a distributable zip file for WordPress plugin installation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get plugin version from main plugin file
PLUGIN_FILE="orchestra-core.php"
if [ ! -f "$PLUGIN_FILE" ]; then
    echo -e "${RED}Error: $PLUGIN_FILE not found. Run this script from the plugin root directory.${NC}"
    exit 1
fi

VERSION=$(grep "Version:" "$PLUGIN_FILE" | head -n 1 | sed -E 's/.*Version:[[:space:]]*([0-9.]+).*/\1/')
if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: Could not extract version from $PLUGIN_FILE${NC}"
    exit 1
fi

BUILD_DIR="build"
PLUGIN_NAME="orchestra-core"
ZIP_NAME="${PLUGIN_NAME}-${VERSION}.zip"
DIST_DIR="${BUILD_DIR}/${PLUGIN_NAME}"

echo -e "${YELLOW}Building Orchestra Core v${VERSION}${NC}"
echo ""

# Clean previous build
if [ -d "$BUILD_DIR" ]; then
    echo "Cleaning previous build..."
    rm -rf "$BUILD_DIR"
fi

# Create build directory structure
mkdir -p "$DIST_DIR"

echo "Copying plugin files..."

# Copy only necessary files and directories
cp "$PLUGIN_FILE" "$DIST_DIR/"
cp -r "src" "$DIST_DIR/"
cp -r "vendor" "$DIST_DIR/"
cp "composer.json" "$DIST_DIR/"
cp "composer.lock" "$DIST_DIR/"
cp "README.md" "$DIST_DIR/"
cp "LICENSE" "$DIST_DIR/" 2>/dev/null || true

# Copy docs (optional, but helpful for reference)
if [ -d "docs" ]; then
    cp -r "docs" "$DIST_DIR/"
fi

# Remove unnecessary files from vendor to keep zip size reasonable
echo "Cleaning up vendor directory..."
if [ -d "$DIST_DIR/vendor" ]; then
    # Remove test files and other unnecessary files from dependencies
    find "$DIST_DIR/vendor" -type d -name "tests" -exec rm -rf {} + 2>/dev/null || true
    find "$DIST_DIR/vendor" -type d -name "test" -exec rm -rf {} + 2>/dev/null || true
    find "$DIST_DIR/vendor" -type d -name ".git" -exec rm -rf {} + 2>/dev/null || true
    find "$DIST_DIR/vendor" -name ".gitignore" -delete
    find "$DIST_DIR/vendor" -name ".gitattributes" -delete
    find "$DIST_DIR/vendor" -name "*.md" -path "*/vendor/*" -delete 2>/dev/null || true
fi

# Create zip file
echo "Creating zip file..."
cd "$BUILD_DIR"
zip -r "$ZIP_NAME" "$PLUGIN_NAME" -q \
    -x "$PLUGIN_NAME/.git/*" \
    "$PLUGIN_NAME/.gitignore" \
    "$PLUGIN_NAME/.idea/*" \
    "$PLUGIN_NAME/tests/*" \
    "$PLUGIN_NAME/phpstan.neon" \
    "$PLUGIN_NAME/.DS_Store"

cd ".."

# Get file size
ZIP_SIZE=$(du -h "$BUILD_DIR/$ZIP_NAME" | cut -f1)

echo ""
echo -e "${GREEN}✓ Build successful!${NC}"
echo ""
echo -e "Plugin: ${YELLOW}${PLUGIN_NAME}${NC}"
echo -e "Version: ${YELLOW}${VERSION}${NC}"
echo -e "Location: ${YELLOW}${BUILD_DIR}/${ZIP_NAME}${NC}"
echo -e "Size: ${YELLOW}${ZIP_SIZE}${NC}"
echo ""
echo "Ready to upload to WordPress!"

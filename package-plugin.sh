#!/bin/bash
# WordPress Plugin Packaging Script
# Creates a clean distribution package for wp-cpt-rest-api plugin

PLUGIN_NAME="wp-cpt-rest-api"
VERSION="1.0.0-RC1"
BUILD_DIR="build"
PACKAGE_NAME="${PLUGIN_NAME}-${VERSION}"

echo "Packaging ${PLUGIN_NAME} version ${VERSION}..."

# Clean previous build
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}/${PLUGIN_NAME}"

# Copy plugin files (only what's needed for distribution)
echo "Copying plugin files..."

# Main plugin files
cp src/wp-cpt-rest-api.php "${BUILD_DIR}/${PLUGIN_NAME}/"
cp src/readme.txt "${BUILD_DIR}/${PLUGIN_NAME}/"
cp src/uninstall.php "${BUILD_DIR}/${PLUGIN_NAME}/"
cp LICENSE "${BUILD_DIR}/${PLUGIN_NAME}/"

# Copy directories
cp -r src/admin "${BUILD_DIR}/${PLUGIN_NAME}/"
cp -r src/includes "${BUILD_DIR}/${PLUGIN_NAME}/"
cp -r src/rest-api "${BUILD_DIR}/${PLUGIN_NAME}/"
cp -r src/swagger "${BUILD_DIR}/${PLUGIN_NAME}/"
cp -r src/assets "${BUILD_DIR}/${PLUGIN_NAME}/"
cp -r src/languages "${BUILD_DIR}/${PLUGIN_NAME}/"

# Optional: Copy documentation files (can be excluded for smaller package)
cp src/API_ENDPOINTS.md "${BUILD_DIR}/${PLUGIN_NAME}/"
cp src/OPENAPI.md "${BUILD_DIR}/${PLUGIN_NAME}/"

# Remove development files from build
echo "Removing development files..."
find "${BUILD_DIR}/${PLUGIN_NAME}" -name ".vscode" -type d -exec rm -rf {} + 2>/dev/null
find "${BUILD_DIR}/${PLUGIN_NAME}" -name ".git" -type d -exec rm -rf {} + 2>/dev/null
find "${BUILD_DIR}/${PLUGIN_NAME}" -name ".gitignore" -type f -delete 2>/dev/null
find "${BUILD_DIR}/${PLUGIN_NAME}" -name "tests" -type d -exec rm -rf {} + 2>/dev/null
find "${BUILD_DIR}/${PLUGIN_NAME}" -name "*.log" -type f -delete 2>/dev/null
find "${BUILD_DIR}/${PLUGIN_NAME}" -name ".DS_Store" -type f -delete 2>/dev/null

# Create ZIP package
echo "Creating ZIP package..."
cd "${BUILD_DIR}"
zip -r "../${PACKAGE_NAME}.zip" "${PLUGIN_NAME}" -q

cd ..
echo "Package created: ${PACKAGE_NAME}.zip"
echo "Package size: $(du -h ${PACKAGE_NAME}.zip | cut -f1)"

# List package contents
echo ""
echo "Package contents:"
unzip -l "${PACKAGE_NAME}.zip" | head -30

echo ""
echo "Packaging complete!"

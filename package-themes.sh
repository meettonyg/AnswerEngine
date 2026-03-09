#!/bin/bash
#
# Package both WordPress themes into upload-ready .zip files.
#
# Usage: bash package-themes.sh
#
# Output:
#   answerenginewp.zip        — Product theme (ready to upload)
#   aivisibilityscanner.zip   — Scanner theme (ready to upload)

set -e
cd "$(dirname "$0")"

# ─── Product Theme ────────────────────────────────────────────────
echo "Packaging answerenginewp..."
cd answerenginewp
zip -r ../answerenginewp.zip . \
    -x ".*" \
    -x "__MACOSX/*" \
    -x "*.DS_Store" \
    -x "node_modules/*" \
    -x ".git/*"
cd ..
echo "  → answerenginewp.zip ($(du -h answerenginewp.zip | cut -f1))"

# ─── Scanner Theme ────────────────────────────────────────────────
echo ""
echo "Packaging aivisibilityscanner..."

# Ensure Composer dependencies are installed
if [ ! -f aivisibilityscanner/vendor/autoload.php ]; then
    echo "  Running composer install..."
    cd aivisibilityscanner
    composer install --no-dev --optimize-autoloader --quiet
    cd ..
fi

cd aivisibilityscanner
zip -r ../aivisibilityscanner.zip . \
    -x ".*" \
    -x "__MACOSX/*" \
    -x "*.DS_Store" \
    -x "node_modules/*" \
    -x ".git/*" \
    -x "composer.lock"
cd ..
echo "  → aivisibilityscanner.zip ($(du -h aivisibilityscanner.zip | cut -f1))"

echo ""
echo "Done. Upload each .zip via WordPress → Appearance → Themes → Add New → Upload Theme."

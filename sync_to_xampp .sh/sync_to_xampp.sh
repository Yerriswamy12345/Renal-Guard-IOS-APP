#!/bin/bash

# Sync script to copy PHP files from development directory to XAMPP htdocs
# Run this after making changes to PHP files in the Desktop directory

SOURCE_DIR="/Users/akhilavalluru/Desktop/GuardRenal/GuardRenal/renalguard"
DEST_DIR="/Applications/XAMPP/htdocs/renalguard"

echo "🔄 Syncing PHP files from Desktop to XAMPP..."
echo "Source: $SOURCE_DIR"
echo "Destination: $DEST_DIR"
echo ""

# Copy all PHP files
rsync -av --progress "$SOURCE_DIR"/*.php "$DEST_DIR/"

echo ""
echo "✅ Sync complete!"
echo ""
echo "📝 Files in XAMPP directory:"
ls -lt "$DEST_DIR"/*.php | head -10

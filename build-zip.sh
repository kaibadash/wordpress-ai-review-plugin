#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

npm run build

zip -r ai-review-plugin.zip \
  ai-review-plugin.php \
  inc/ \
  build/ \
  languages/ \
  readme.txt

echo "Created: ai-review-plugin.zip"

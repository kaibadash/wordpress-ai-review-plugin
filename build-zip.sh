#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

npm run build

zip -r ai-review.zip \
  ai-review.php \
  inc/ \
  build/ \
  assets/ \
  languages/ \
  readme.txt

echo "Created: ai-review.zip"

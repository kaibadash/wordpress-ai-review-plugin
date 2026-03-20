#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"

VERSION="${1:-}"
if [ -z "$VERSION" ]; then
  echo "Usage: $0 <version>"
  echo "Example: $0 1.2.3"
  exit 1
fi

ZIPNAME="ai-review-${VERSION}.zip"

sed -i '' "s/^ \* Version: .*/ * Version: ${VERSION}/" ai-review.php
sed -i '' "s/^define( 'AI_REVIEW_VERSION', '.*' );/define( 'AI_REVIEW_VERSION', '${VERSION}' );/" ai-review.php
sed -i '' "s/^Stable tag: .*/Stable tag: ${VERSION}/" readme.txt

for po in languages/*.po; do
  [ -f "$po" ] && msgfmt -o "${po%.po}.mo" "$po"
done

npm run build

zip -r "$ZIPNAME" \
  ai-review.php \
  inc/ \
  build/ \
  assets/ \
  languages/ \
  readme.txt

echo "Created: $ZIPNAME"

echo ""
read -p "Upload to GitHub Releases as v${VERSION}? [y/N] " yn
case "$yn" in
  [yY]*)
    git add -A
    git commit -m "Bump version to ${VERSION}"
    git tag "v${VERSION}"
    git push origin HEAD --tags
    gh release create "v${VERSION}" "$ZIPNAME" --title "v${VERSION}" --generate-notes
    echo "Released: v${VERSION}"
    ;;
  *)
    echo "Skipped release upload."
    ;;
esac

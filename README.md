# AI Review Plugin

A WordPress plugin that uses AI to revise your posts. Enter a prompt in the sidebar of the post editor and let AI refine your content.

## Requirements

- Docker & Docker Compose
- Node.js (npm)

## Build

```bash
npm install
npm run build
```

For development with file watching:

```bash
npm run start
```

### Create ZIP package

```bash
bash build-zip.sh
```

This generates `ai-review-plugin.zip`.

## Getting Started

Start the local environment with Docker Compose:

```bash
docker compose up -d
```

- WordPress: http://localhost:8080
- Complete the WordPress setup wizard on first visit.
- Go to Plugins and activate "AI Review".
- Go to Settings > AI Review and enter your API key.
- Open the post editor — the AI Review panel appears in the sidebar.

To stop:

```bash
docker compose down
```

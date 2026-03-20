# AI Review Plugin

<img width="542" height="532" alt="setting" src="https://github.com/user-attachments/assets/a3f15510-45fb-4559-a81e-06ab2b1f97fa" />
<img width="797" height="344" alt="review" src="https://github.com/user-attachments/assets/2f39d13b-784b-4164-bdc4-abb361487d01" />


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

# Lisence

GPL-2.0-or-later

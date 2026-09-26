# Vercel deployment note for this Laravel project

## Error being fixed

Vercel reported:

> No Output Directory named "dist" found after the Build completed. Configure the Output Directory in your Project Settings. Alternatively, configure vercel.json#outputDirectory.

## What this means

This project is not producing a standard frontend build folder named `dist`.
The Laravel + Vite build output is generated into:

- `public/build`

This is confirmed by the successful local build output:

- `public/build/manifest.json`
- `public/build/assets/app-*.css`
- `public/build/assets/app-*.js`

## Current fix

The project includes a `vercel.json` file with:

```json
{
  "outputDirectory": "public/build"
}
```

This tells Vercel where to look for the generated static files.

## Important limitation

This only fixes the Vercel warning about the missing output folder.
It does not make this Laravel application run properly on Vercel as a full backend.

This app is a traditional Laravel application with:

- PHP runtime
- sessions stored in the database
- queue workers
- scheduler/cron tasks
- SQLite default configuration in `.env.example`
- database-backed cache and session drivers

Those are not ideal for a Vercel serverless deployment.

## Recommended deployment strategy

### Best option
Deploy the Laravel app to a proper PHP host such as:

- Railway
- Render
- VPS / server with PHP + MySQL/PostgreSQL

Use Vercel only if you plan to host only the static frontend assets or a separate frontend app.

### If you still want Vercel
Use Vercel only for the frontend assets while the Laravel backend runs elsewhere.
For the current project, a direct Laravel deployment to Vercel is not recommended.

## Production checklist before real deployment

- switch from SQLite to MySQL or PostgreSQL
- set `SESSION_DRIVER` to `cookie` or `database` in Vercel Project Settings > Environment Variables
- set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Vercel Project Settings to credentials for the same external database; do not put credentials in `vercel.json`
- set `APP_ENV=production`
- set `APP_DEBUG=false`
- set `APP_URL=https://your-domain.com`
- set Firebase credentials
- run migrations on the production database
- configure a scheduler and queue worker on the hosting platform

## Summary

The project currently builds to `public/build`, so Vercel should be pointed there with `vercel.json`.
However, this is only a partial fix; the app itself still needs a real PHP-capable hosting environment for full Laravel functionality.

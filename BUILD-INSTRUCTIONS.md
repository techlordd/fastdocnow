# Build-Only Vite Setup Instructions

This setup allows you to use Vite for building assets without needing to run `npm run dev`.

## How to Use

### 1. Build Assets
```bash
npm run build
```

This will:
- Build all CSS and JS files
- Generate optimized assets in `public/build/`
- Create a manifest file for asset mapping

### 2. Serve Your Application
You can now serve your Laravel application normally without any development server:
```bash
php artisan serve
```

Or use any web server (Apache, Nginx, etc.)

### 3. Asset Usage
The built assets are automatically referenced in your views using the manifest file. No `@vite` directives are needed.

### 4. Environment Variables
Make sure your `.env` file has:
```
APP_ENV=production
```

### 5. Deployment
For deployment, you only need:
- The `public/build/` directory
- The built assets
- No need for `npm run dev` or any development dependencies

## File Structure After Build
```
public/
├── build/
│   ├── manifest.json
│   └── assets/
│       ├── app-XXXX.css
│       ├── app-XXXX.js
│       └── chat-XXXX.js
```

## Troubleshooting

### Assets Not Loading
- Ensure you've run `npm run build`
- Check that `public/build/manifest.json` exists
- Verify the asset paths in your views

### Build Errors
- Run `npm install` to ensure all dependencies are installed
- Check for any syntax errors in your source files

## Notes
- This setup is optimized for production use
- Development server (`npm run dev`) is disabled
- Hot module replacement is disabled
- Assets are optimized and minified

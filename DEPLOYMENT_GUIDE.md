# FastDocNow - Vercel Deployment Guide

## 🚀 Quick Start for Vercel Deployment

### Prerequisites
- Node.js 18+ and npm
- PHP 8.2+
- Composer
- Vercel CLI (`npm i -g vercel`)

### 1. Environment Setup

```bash
# Clone the repository
git clone [your-repo-url]
cd fastdocnow.com

# Install dependencies
npm install
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate
```

### 2. Vercel Deployment

#### Option A: Using Vercel CLI
```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel --prod
```

#### Option B: Using GitHub Integration
1. Push your code to GitHub
2. Connect your GitHub repository to Vercel
3. Vercel will automatically deploy on push

### 3. Environment Variables for Vercel

Add these to your Vercel project settings:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
CACHE_DRIVER=array
SESSION_DRIVER=cookie
QUEUE_CONNECTION=sync
```

### 4. Development Commands

#### Start Development Server
```bash
# Install dependencies
npm run setup

# Start Vite dev server
npm run dev

# Start Laravel server
php artisan serve
```

#### Start Reverb WebSocket Server
```bash
# Start Reverb
./start-reverb.sh
# or
npm run start-reverb
```

#### Start Queue Worker
```bash
# Start queue worker
npm run queue
# or
php artisan queue:work
```

### 5. Build Commands

```bash
# Build for production
npm run build

# Build for Vercel
npm run vercel-build
```

### 6. Troubleshooting

#### Common Issues and Solutions

1. **Build fails on Vercel**
   - Run `./vercel-fix.sh` before deployment
   - Check PHP version compatibility (8.2+ required)

2. **WebSocket connection issues**
   - Ensure Reverb is running on correct port
   - Check firewall settings

3. **Queue not processing**
   - Verify queue connection settings
   - Ensure queue worker is running

4. **Database connection issues**
   - For Vercel, use SQLite or external database
   - Update database credentials in environment variables

### 7. File Structure

```
fastdocnow.com/
├── api/
│   └── index.php          # Vercel entry point
├── config/
│   ├── reverb.php         # WebSocket configuration
│   └── ...               # Other Laravel configs
├── public/
│   ├── build/            # Vite build output
│   └── storage/          # Public storage
├── resources/
│   ├── js/               # JavaScript assets
│   └── views/            # Blade templates
├── vercel.json           # Vercel configuration
├── package.json          # Node.js dependencies
├── composer.json         # PHP dependencies
├── start-reverb.sh       # Reverb startup script
├── vercel-fix.sh         # Vercel deployment fixes
└── dev-setup.sh          # Development setup script
```

### 8. Performance Optimization

#### For Vercel
- Use `npm run vercel-build` for optimized builds
- Enable caching in Vercel settings
- Use CDN for static assets

#### For Development
- Use `npm run dev` for hot module replacement
- Enable Laravel debug mode for development

### 9. Monitoring

- Check Vercel deployment logs
- Monitor application performance
- Set up error tracking (Laravel Telescope)

### 10. Security

- Never commit `.env` file
- Use HTTPS in production
- Regularly update dependencies
- Enable Laravel security features

## 🎯 Quick Commands Reference

| Task | Command |
|------|---------|
| Install dependencies | `npm run setup` |
| Start dev server | `npm run dev` |
| Start Laravel | `php artisan serve` |
| Start Reverb | `./start-reverb.sh` |
| Start queue | `npm run queue` |
| Build for Vercel | `npm run vercel-build` |
| Deploy to Vercel | `vercel --prod` |

## 📞 Support

If you encounter any issues:
1. Check the troubleshooting section above
2. Review Vercel deployment logs
3. Ensure all environment variables are set correctly
4. Verify PHP and Node.js versions

## 🔄 Updates

To update the deployment:
1. Make changes to your code
2. Test locally with `npm run dev`
3. Push to GitHub (auto-deploy) or run `vercel --prod`

#!/bin/bash
echo "🔍 Checking Vercel deployment readiness..."

# Check if package.json has correct vercel-build
if grep -q '"vercel-build": "npm run build"' package.json; then
    echo "✅ package.json vercel-build script is correct"
else
    echo "❌ package.json vercel-build script needs fixing"
fi

# Check if vercel.json has proper configuration
if grep -q "vercel-php@0.6.0" vercel.json; then
    echo "✅ vercel.json has correct PHP runtime"
else
    echo "❌ vercel.json PHP runtime needs fixing"
fi

# Check if API index.php exists and is readable
if [ -f "api/index.php" ]; then
    echo "✅ api/index.php exists"
else
    echo "❌ api/index.php missing"
fi

# Check for any remaining composer commands in build scripts
if grep -q "composer install" package.json; then
    echo "❌ Found composer install in package.json - needs removal"
else
    echo "✅ No composer install commands found in package.json"
fi

# Check vercel.json for build/install commands
if grep -q '"installCommand": "npm install"' vercel.json; then
    echo "✅ vercel.json install command is correct"
else
    echo "❌ vercel.json install command needs fixing"
fi

echo ""
echo "🚀 Vercel deployment readiness check complete!"
echo "Next steps:"
echo "1. Commit these changes"
echo "2. Push to main branch"
echo "3. Vercel will auto-deploy"

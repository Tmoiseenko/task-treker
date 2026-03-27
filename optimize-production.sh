#!/bin/bash

# Скрипт оптимизации для production окружения
# Система управления проектами

echo "=========================================="
echo "Production Optimization Script"
echo "=========================================="
echo ""

# Проверка, что мы в production окружении
if [ "$APP_ENV" != "production" ]; then
    echo "⚠️  WARNING: APP_ENV is not set to 'production'"
    echo "Current APP_ENV: $APP_ENV"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "Step 1: Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "✓ Caches cleared"
echo ""

echo "Step 2: Optimizing Composer autoloader..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "✓ Composer optimized"
echo ""

echo "Step 3: Caching configuration..."
php artisan config:cache
echo "✓ Configuration cached"
echo ""

echo "Step 4: Caching routes..."
php artisan route:cache
echo "✓ Routes cached"
echo ""

echo "Step 5: Caching views..."
php artisan view:cache
echo "✓ Views cached"
echo ""

echo "Step 6: Caching events..."
php artisan event:cache
echo "✓ Events cached"
echo ""

echo "Step 7: Optimizing icons (MoonShine)..."
php artisan moonshine:optimize-icons
echo "✓ Icons optimized"
echo ""

echo "Step 8: Building frontend assets..."
if [ -f "package.json" ]; then
    npm run build
    echo "✓ Frontend assets built"
else
    echo "⚠️  package.json not found, skipping frontend build"
fi
echo ""

echo "Step 9: Setting proper permissions..."
chmod -R 775 storage bootstrap/cache
echo "✓ Permissions set"
echo ""

echo "Step 10: Running database migrations (if needed)..."
read -p "Run migrations? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo "✓ Migrations completed"
else
    echo "⊘ Migrations skipped"
fi
echo ""

echo "=========================================="
echo "Optimization Summary"
echo "=========================================="
echo "✓ All caches cleared and rebuilt"
echo "✓ Composer autoloader optimized"
echo "✓ Configuration, routes, views, and events cached"
echo "✓ Frontend assets built"
echo "✓ Permissions configured"
echo ""
echo "Additional recommendations:"
echo "1. Enable OPcache in php.ini for better performance"
echo "2. Configure Redis for cache and sessions"
echo "3. Set up queue workers for background jobs"
echo "4. Configure cron for scheduled tasks"
echo "5. Enable HTTP/2 in your web server"
echo "6. Use CDN for static assets"
echo ""
echo "Performance tips:"
echo "- Monitor logs: storage/logs/laravel.log"
echo "- Check queue status: php artisan queue:work"
echo "- Monitor scheduled tasks: php artisan schedule:list"
echo ""
echo "=========================================="
echo "Optimization completed successfully! 🚀"
echo "=========================================="

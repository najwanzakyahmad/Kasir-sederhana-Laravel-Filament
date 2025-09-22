#!/bin/bash

echo "🧹 Preparing for Docker build..."

# Remove problematic symlink if exists
if [ -L "public/storage" ]; then
    echo "🔗 Removing existing storage symlink..."
    rm public/storage
    echo "✅ Symlink removed"
elif [ -d "public/storage" ]; then
    echo "📁 Removing storage directory..."
    rm -rf public/storage
    echo "✅ Directory removed"
fi

# Clear Laravel caches that might cause issues
if [ -d "bootstrap/cache" ]; then
    echo "🧹 Clearing bootstrap cache..."
    rm -rf bootstrap/cache/*
    echo "✅ Bootstrap cache cleared"
fi

if [ -d "storage/framework/cache" ]; then
    echo "🧹 Clearing framework cache..."
    rm -rf storage/framework/cache/*
    echo "✅ Framework cache cleared"
fi

if [ -d "storage/framework/sessions" ]; then
    echo "🧹 Clearing sessions..."
    rm -rf storage/framework/sessions/*
    echo "✅ Sessions cleared"
fi

if [ -d "storage/framework/views" ]; then
    echo "🧹 Clearing view cache..."
    rm -rf storage/framework/views/*
    echo "✅ View cache cleared"
fi

if [ -d "storage/logs" ]; then
    echo "🧹 Clearing logs..."
    rm -rf storage/logs/*
    echo "✅ Logs cleared"
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions  
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p docker/nginx

echo "✅ Directories created"

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Build preparation completed!"
echo ""
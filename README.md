# Money Changer — Backend Setup

Laravel REST API. Runs entirely via Docker — no local PHP, Composer, or MySQL install needed.

## Prasyarat

- Docker Desktop terpasang dan berjalan.

## Kenapa Docker

Image PHP resmi (`composer:2`) tidak menyertakan extension `pdo_mysql`, jadi ada image
kecil tambahan (`.docker/Dockerfile.php`) yang menambahkan extension tersebut di atas
`php:8.4-cli`. Semua service (MySQL + PHP) didefinisikan di `../docker-compose.yml`
(satu level di atas folder ini, di root project).

## Setup awal (sekali saja)

Jalankan semua perintah berikut dari folder root project (`money_changer/`, bukan `backend/`).

1. **Nyalakan MySQL & build image PHP:**

   ```bash
   docker compose up -d --build
   ```

2. **Install dependency Composer:**

   ```bash
   docker run --rm -v "$(pwd)/backend:/app" -w /app composer:2 install
   ```

   Di Windows PowerShell, ganti `$(pwd)/backend` dengan path absolut, mis.
   `C:\path\to\money_changer\backend`.

3. **Siapkan `.env`:**

   Pastikan `backend/.env` ada (copy dari `.env.example` kalau belum), lalu set bagian
   database:

   ```
   DB_CONNECTION=mysql
   DB_HOST=money_changer_mysql
   DB_PORT=3306
   DB_DATABASE=money_changer
   DB_USERNAME=root
   DB_PASSWORD=secret
   ```

   Generate app key:

   ```bash
   docker compose run --rm php php artisan key:generate
   ```

4. **Jalankan migration:**

   ```bash
   docker compose run --rm php php artisan migrate
   ```

## Pemakaian sehari-hari

Pola umum untuk semua perintah `artisan` — jalankan dari root project:

```bash
docker compose run --rm php php artisan test
docker compose run --rm php php artisan tinker
docker compose run --rm php php artisan make:model Foo
docker compose run --rm php php artisan migrate:fresh
```

Untuk menambah/mengubah dependency Composer:

```bash
docker run --rm -v "$(pwd)/backend:/app" -w /app composer:2 require nama/package
```

## Mematikan environment

```bash
docker compose down
```

Data MySQL tersimpan di volume `mysql_data` dan tetap ada setelah `down`. Tambahkan
`-v` di akhir perintah kalau memang ingin menghapus data database juga.

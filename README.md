# Web UMKM Lombok Timur

Aplikasi web informasi UMKM di Kabupaten Lombok Timur yang memadukan **Frontend PHP**, **Backend Node.js (Express)**, dan **Database SQLite**.

---

## 🌟 Fitur Utama

1. **Halaman Publik UMKM**:
   - Menampilkan daftar UMKM Lombok Timur terverifikasi (Nama, Lokasi, Kategori, Foto Usaha).
   - Fitur pencarian nama & filter lokasi di wilayah Lombok Timur.
   - Halaman detail profil lengkap UMKM dan informasi kontak.

2. **Autentikasi & Akun**:
   - Register akun khusus **Pemilik UMKM**.
   - Login untuk **Admin** dan **Pemilik UMKM**.
   - Pengaturan akun (Ubah Nama & Ganti Password).
   - Tombol Logout yang aman.

3. **Alur Pemilik UMKM**:
   - Registrasi akun baru.
   - Login dan masuk ke dashboard pemilik.
   - Mengisi semua data usaha (Nama, Kategori, Lokasi, Alamat Lengkap, Kontak, Deskripsi) dan mengunggah **Foto UMKM**.
   - Memantau status verifikasi (*Pending*, *Approved*, *Rejected*).

4. **Panel Administrator**:
   - Dashboard statistik UMKM dan pengguna.
   - Moderasi status UMKM (*Approve* / *Reject* / *Pending*) dengan catatan admin.
   - Melihat daftar seluruh pengguna terdaftar.

---

## 🚀 Cara Menjalankan di Localhost

Aplikasi dirancang agar frontend PHP dan backend Node.js dapat berjalan berdampingan dengan mudah dalam satu perintah.

### 1. Install Dependencies Backend
Buka terminal di root direktori proyek, lalu jalankan:
```bash
cd backend
npm install
cd ..
```

### 2. Jalankan Aplikasi Sekaligus
Jalankan perintah berikut di root folder proyek:
```bash
npm run dev
```
*Script ini otomatis menjalankan backend Node.js (`http://127.0.0.1:3000`) dan PHP built-in server (`http://127.0.0.1:8000`).*

### 3. Buka di Browser
Akses aplikasi melalui browser di:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 🔑 Akun Demo Bawaan (Default Seed)

Database SQLite otomatis di-seed saat pertama kali backend dijalankan dengan akun-akun berikut:

| Peran / Role | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `Admin123!` | Akses penuh verifikasi dan moderasi UMKM |
| **Pemilik UMKM** | `sarilombok` | `Owner123!` | Pemilik UMKM "Sari Lombok Food" |
| **Pemilik UMKM** | `aikmelcraft` | `Owner123!` | Pemilik UMKM "Aikmel Craft Center" |
| **Pemilik UMKM** | `kopitetebatu` | `Owner123!` | Pemilik UMKM "Kopi Tetebatu" |

*Anda juga dapat langsung mendaftarkan akun baru melalui halaman **Register**.*

---

## 📁 Struktur Direktori

```text
WebUMKM/
├── app/                  # Helper PHP, API Client, dan Session Auth
│   ├── ApiClient.php
│   ├── Auth.php
│   └── bootstrap.php
├── backend/              # Node.js Express REST API & SQLite
│   ├── data/
│   │   ├── schema.sql    # Skema SQLite
│   │   └── webumkm.sqlite # File Database
│   ├── src/
│   │   ├── db.js         # Koneksi database & query helper
│   │   ├── server.js     # Entrypoint server Express
│   │   ├── middleware/   # Auth middleware
│   │   └── routes/       # Endpoint API (Auth, UMKM, Admin)
│   └── uploads/          # Folder upload foto UMKM
├── config/
│   └── app.php           # Konfigurasi aplikasi PHP
├── frontend/
│   └── view/             # Layout dan partials Blade/PHP template
├── public/               # Document root PHP Server
│   ├── admin/            # Halaman admin PHP
│   ├── assets/           # CSS & JS
│   ├── dashboard.php     # Dashboard pemilik UMKM
│   ├── detail.php        # Detail UMKM publik
│   ├── index.php         # Halaman utama direktori UMKM
│   ├── login.php         # Login
│   ├── logout.php        # Logout
│   ├── register.php      # Register akun pemilik
│   └── setting.php       # Pengaturan nama & password
├── dev.js                # Runner untuk menyatukan PHP & Node.js
├── package.json          # Root package scripts
└── README.md
```

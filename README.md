# 🌍 Carbonsense

**Carbonsense** adalah sistem **IoT berbasis web** untuk pemantauan kualitas udara dan emisi karbon dari cerobong asap industri.  
Platform ini juga mendukung konsep **Carbon Pay**, yaitu perhitungan kompensasi berdasarkan jumlah emisi yang dikeluarkan.

## 🔎 Fitur Utama
- 🌫 **Air Quality Monitoring** — memantau indeks kualitas udara (AQI)
- 💨 **Gas Leak Detection** — mendeteksi kebocoran gas secara real-time
- 🧪 **CO Level Measurement** — pengukuran konsentrasi gas karbon monoksida (ppm)
- 📈 **Carbon Pay System** — menghitung biaya karbon berdasarkan kadar CO & emisi
- 📊 **Dashboard Analytics** — visualisasi data tren dan laporan otomatis
- 🔐 **User Authentication** — akses aman untuk admin & operator
- ⚙️ **.env Configuration** — konfigurasi environment terpisah menggunakan `vlucas/phpdotenv`

## 🧩 Teknologi yang Digunakan
- **Backend:** PHP Native + REST API  
- **Database:** MySQL (MariaDB)  
- **Frontend:** HTML, CSS, JavaScript  
- **Dependency Management:** Composer  
- **Deployment:** XAMPP / Apache Server  

## 🧠 Parameter yang Dipantau
| Parameter | Satuan | Deskripsi |
|------------|---------|------------|
| **Air Quality Index (AQI)** | - | Indeks kualitas udara berdasarkan gas polutan |
| **Gas Leak Index** | % | Indikator kebocoran gas mudah terbakar |
| **CO Level** | ppm | Konsentrasi gas karbon monoksida |
| **Emission Rate** | mg/m³ | Tingkat emisi dari cerobong |
| **Carbon Pay Value** | Rp | Estimasi biaya kompensasi karbon |

## ⚙️ Instalasi
1. Clone repository:
   ```bash
   git clone https://github.com/alvinputranurtan/carbonsense.git

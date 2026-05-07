# Iseki Stock Opname

Iseki Stock Opname is a web-based inventory management application designed to streamline the stock counting process. It features advanced scanning capabilities, including QR code identification and 7-segment OCR (Optical Character Recognition) for digital scale displays.

## 🚀 Key Features

- **Dual Recording Modes**:
    - **Manual Mode**: Conventional digital entry for stock counts.
    - **Scale (OCR) Mode**: Automates data entry by reading digital scale displays using the camera.
- **Advanced OCR System**:
    - Custom camera interface with a visual guide box for precise alignment.
    - Real-time client-side image processing powered by **OpenCV.js**.
    - Automatic cropping and perspective correction to isolate display digits.
    - Robust 7-segment digit recognition algorithm.
- **QR Code Integration**:
    - Instantly identify Racks, Parts, Areas, and Locations via QR codes.
    - Integrated with `html5-qrcode` for high-performance browser-based scanning.
- **Multi-Photo Records**: Capture and store multiple evidence photos for every stock record.
- **Role-Based Access**:
    - **Admin**: Dashboard for user management and comprehensive record oversight.
    - **Member**: Mobile-optimized interface for on-the-floor stock opname operations.

## 🛠 Technology Stack

- **Backend**: Laravel (PHP)
- **Frontend**: Bootstrap, jQuery, CSS3
- **Computer Vision**: OpenCV.js (WASM/JS)
- **Scanner**: html5-qrcode
- **Database**: MySQL

## 📋 Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd iseki_stockopname
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Environment Configuration**:
   - Copy `.env.example` to `.env`.
   - Configure your database settings.
   - Generate application key: `php artisan key:generate`.

4. **Database Migration**:
   ```bash
   php artisan migrate
   ```

5. **Run the Application**:
   ```bash
   php artisan serve
   ```

## 📸 OCR Usage Guide

1. Navigate to the **Scan Record** page as a Member.
2. Select **Scale (OCR)** mode.
3. Align the digital scale's 7-segment display within the purple guide box.
4. Click the **Capture** button.
5. The system will automatically crop, process, and identify the value.
6. Verify the detected value and confirm the record.

---
*Built for ISEKI Stock Opname Operations.*

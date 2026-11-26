AOVU SHOP - TICH HOP CHUYEN KHOAN BIDV / VIETQR

CAC FILE CAN CHEP VAO DU AN
1. app/Http/Controllers/StorefrontController.php
2. resources/views/storefront/checkout.blade.php
3. resources/views/storefront/checkout-bank-transfer.blade.php (FILE MOI)
4. config/aovu_payment.php (FILE MOI)

THONG TIN TAI KHOAN DA CAU HINH
- Ngan hang: BIDV
- BIN: 970418
- So tai khoan: 8834515636
- Chu tai khoan: HOANG DUC TUE

LUONG HOAT DONG
- Tai checkout, khi chon phuong thuc co ten "Chuyen khoan" / "Ngan hang" / "Bank transfer", hien thong tin tai khoan.
- Khi khach bam Xac nhan dat hang, he thong tao don hang nhu hien tai va giu trang thai thanh toan Chua thanh toan.
- Trang thanh cong chuyen khoan hien QR VietQR voi so tien don hang va noi dung "AOVU <MA_DON_HANG>".
- COD van dung trang checkout-success hien tai, khong bi thay doi.
- Khong tu dong danh dau da thanh toan; admin can doi soat giao dich thuc nhan.

SAU KHI CHEP FILE
Chay:
php artisan optimize:clear
php artisan route:list --name=shop.checkout

KHONG CAN MIGRATE VA KHONG CAN SUA routes/web.php.

CO THE GHI DE CAU HINH BANG .env (khong bat buoc vi da co gia tri mac dinh):
AOVU_BANK_NAME=BIDV
AOVU_BANK_BIN=970418
AOVU_BANK_ACCOUNT=8834515636
AOVU_BANK_ACCOUNT_NAME="HOANG DUC TUE"
AOVU_BANK_QR_TEMPLATE=compact2

LUU Y
QR duoc tao bang VietQR Quick Link. QR chi ma hoa lenh chuyen khoan; no khong doc sao ke hay tu dong xac nhan tien vao tai khoan.

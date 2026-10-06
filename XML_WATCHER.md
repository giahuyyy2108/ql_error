# Tự động quét file XML

Thư mục nhận file mặc định là `storage/xml`. Khi một file `.xml` được chép vào đây, worker sẽ chờ file ghi ổn định, nhập nội dung vào cơ sở dữ liệu rồi di chuyển file:

- Thành công, không có lỗi validation hoặc trùng `MA_LK`: `storage/xml/processed`
- XML không hợp lệ, có lỗi validation hoặc xử lý thất bại: `storage/xml/failed`
- Chi tiết lỗi: file `.error.txt` nằm cạnh XML lỗi

Mỗi file lỗi được chuyển vào một thư mục riêng trong `storage/xml/failed` theo mẫu. Nếu file có nhiều loại lỗi, file chính nằm ở nhóm lỗi đầu tiên và các nhóm còn lại nhận một bản sao:

```text
PASS<số lỗi>_<tên file XML>_<tên lỗi>/
└── PASS<số lỗi>_<tên file XML>_<tên lỗi>_<tên file gốc>.xml
```

Ví dụ: `PASS2_XML2_Ma_hoat_chat/PASS2_XML2_Ma_hoat_chat_hoso.xml`.

Chạy worker liên tục trên Windows/XAMPP:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php
```

Quét một lần, phù hợp với Windows Task Scheduler:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php --once
```

Các tùy chọn:

```text
--interval=2   Khoảng nghỉ giữa hai lần quét, tính bằng giây
--settle=3     Thời gian file phải ổn định trước khi được xử lý
--dir=PATH     Theo dõi một thư mục khác
```

Worker dùng khóa độc quyền; nếu cùng thư mục đã có một worker chạy thì tiến trình thứ hai sẽ thoát.

Database chỉ lưu metadata, đường dẫn file, trạng thái và kết quả validation. Nội dung XML và cây dữ liệu giải mã không được lưu trùng trong database; màn hình xem đọc trực tiếp từ file vật lý.

File không đạt rule vẫn được import với trạng thái `failed`. Mỗi lần người dùng mở file, hệ thống chạy validation lại và tự chuyển file giữa `failed` và `processed` nếu kết quả thay đổi.

Nếu `MA_LK` đã tồn tại, file mới không bị bỏ qua: hệ thống quét lại đúng một lần, cập nhật bản ghi hiện có (giữ nguyên ID) và chuyển phiên bản file cũ vào `deleted` để giữ 30 ngày.

Nút **Quét lại tất cả** đưa các bản ghi vào trạng thái `pending_revalidation` rồi tự xử lý từng lô 2 file ngay từ giao diện. Worker nền cũng có thể nhận cùng hàng đợi; mỗi lô được khóa nhận việc để hai tiến trình không quét trùng một file.

File bị người dùng xóa được chuyển vào `storage/xml/deleted`, giữ 30 ngày rồi worker mới xóa vĩnh viễn.

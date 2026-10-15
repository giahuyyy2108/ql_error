# Tự động quét file XML

Thư mục nhận file mặc định là `storage/xml`. Khi một file `.xml` được chép vào đây, worker sẽ chờ file ghi ổn định, nhập nội dung vào cơ sở dữ liệu rồi di chuyển file:

Chức năng tải file trên giao diện web cũng chỉ ghi nguyên tử file vào thư mục nhận này; việc giải mã, gọi BHYT, validation và lưu database đều do worker thực hiện nền.

- Thành công, không có lỗi validation hoặc trùng `MA_LK`: `storage/xml/processed/YYYYMMDD`
- XML không hợp lệ, có lỗi validation hoặc xử lý thất bại: `storage/xml/failed/YYYYMMDD`
- Chi tiết lỗi: file `.error.txt` nằm cạnh XML lỗi

Các file được phân loại theo ngày xử lý. File lỗi tiếp tục được nhóm theo tên lỗi; tiền tố `PASS` chỉ nằm ở tên file. Nếu một hồ sơ có nhiều loại lỗi, các nhóm còn lại dùng hard link khi hệ thống hỗ trợ để không nhân đôi dữ liệu:

```text
storage/xml/failed/YYYYMMDD/<tên lỗi>/
└── PASS<số lỗi>_<tên file XML>_R<rule>_<hash>_<tên lỗi>_<tên file gốc>.xml
```

Ví dụ: `storage/xml/failed/20261015/Ma_bac_si_khong_ton_tai/PASS2_XML2_R55_41bd91fd_Ma_bac_si_khong_ton_tai_hoso.xml`.

Chạy worker liên tục trên Windows/XAMPP:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php
```

Quét một lần, phù hợp với Windows Task Scheduler:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php --once
```

Worker mặc định nghỉ 1 giây giữa các vòng quét, chờ file ổn định 2 giây và nhận tối đa
20 hồ sơ trong mỗi lô quét lại.

Các tùy chọn:

```text
--interval=2   Khoảng nghỉ giữa hai lần quét, tính bằng giây
--settle=3     Thời gian file phải ổn định trước khi được xử lý
--dir=PATH     Theo dõi một thư mục khác
```

Worker dùng khóa độc quyền; nếu cùng thư mục đã có một worker chạy thì tiến trình thứ hai sẽ thoát.

`WatcherLauncher.exe` mặc định chạy hai worker song song. Mỗi worker dùng lock và thư mục claim riêng (`processing/worker-1`, `processing/worker-2`), vì vậy một file chỉ được một worker nhận. Có thể chạy thủ công từng worker bằng `--worker-id=1` hoặc `--worker-id=2`.

`storage/xml` chỉ chứa bốn thư mục dữ liệu `processing`, `processed`, `failed` và `deleted`. File lock cùng log vận hành được đặt riêng trong `storage/watcher`.

Database chỉ lưu metadata, đường dẫn file, trạng thái và kết quả validation. Nội dung XML và cây dữ liệu giải mã không được lưu trùng trong database; màn hình xem đọc trực tiếp từ file vật lý.

File không đạt rule vẫn được import với trạng thái `failed`. Mỗi lần người dùng mở file, hệ thống chạy validation lại và tự chuyển file giữa `failed` và `processed` nếu kết quả thay đổi.

Nếu `MA_LK` đã tồn tại, file mới không bị bỏ qua: hệ thống quét lại đúng một lần, cập nhật bản ghi hiện có (giữ nguyên ID) và chuyển phiên bản file cũ vào `deleted` để giữ 30 ngày.

Nút **Quét lại tất cả** đưa các bản ghi vào trạng thái `pending_revalidation` rồi tự xử lý từng lô 2 file ngay từ giao diện. Worker nền cũng có thể nhận cùng hàng đợi; mỗi lô được khóa nhận việc để hai tiến trình không quét trùng một file.

File bị người dùng xóa được chuyển vào `storage/xml/deleted`, giữ 30 ngày rồi worker mới xóa vĩnh viễn.

## Log worker

- Nhật ký hoạt động: `storage/watcher/logs/YYYYMMDD/watcher.log`
- Lỗi hệ thống: `storage/watcher/logs/YYYYMMDD/watcher.error.log`
- Lỗi validation ghi kèm tên file, loại XML, mã lỗi (`rule_type`), trường dữ liệu, số lần và nội dung lỗi.
- Thư mục log quá thời hạn `--retention-days` được tự động dọn.

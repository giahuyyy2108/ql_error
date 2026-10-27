# Ghi chú bảo trì source `ql_error`

Tài liệu này mô tả các file do ứng dụng quản lý trực tiếp. Không sửa thư viện bên thứ ba trong `web_src/common/PHPExcel`, jQuery, Bootstrap, DataTables... để thêm logic nghiệp vụ.

## Luồng chính

1. Người dùng thao tác tại `www/file/index.php`; JavaScript chính nằm trong `js/file.js`.
2. AJAX gọi các action trong `web_src/admin/fileAction.php`.
3. `fileAction` gọi `web_src/bean/FilePeer.php` để đọc/ghi DB và trạng thái hàng đợi.
4. File upload được đưa vào `storage/xml`; `scripts/watch_xml.php` nhận file, decode, validate và cập nhật DB.
5. `XmlFileImportService` điều phối import; `XmlFileDecoder` decode XML; `XmlRulesValidator` chạy các rule kiểm tra.

## Bản đồ source

| Thành phần | File chính | Trách nhiệm |
| --- | --- | --- |
| Giao diện danh sách/file | `www/file/index.php` | HTML, modal, nút thao tác, CSRF/context cho JS |
| Logic trình duyệt | `js/file.js` | tải danh sách, upload queue, progress, revalidation, đẩy cổng, lịch sử, xem lỗi |
| HTTP action | `web_src/admin/fileAction.php` | nhận request, kiểm tra tham số, gọi service/peer, trả JSON/download |
| Truy cập DB/file metadata | `web_src/bean/FilePeer.php` | danh sách file, audit, portal, upload/revalidation queue, xóa dữ liệu |
| Lưu trữ file XML | `web_src/common/FileStorageService.php` | chuẩn hóa đường dẫn, thư mục theo ngày, tên file tránh trùng, move/copy file |
| Hàng đợi upload | `web_src/common/UploadQueueService.php` | khóa theo tên file, staging upload, lưu metadata user/khoa và công bố file cho watcher |
| Đẩy cổng | `web_src/common/PortalService.php` | decode hồ sơ, ghi `cong_benhnhan`/`cong_hoso`, lưu bản sao portal và audit |
| Import XML | `web_src/common/XmlFileImportService.php` | decode, validate, BHYT lookup, insert/reimport |
| Decode XML | `web_src/common/XmlFileDecoder.php` | Base64/XMLReader/SimpleXML và trích trường dùng chung |
| Validation | `web_src/common/XmlRulesValidator.php` | chạy rule theo XML type và trả errors/warnings |
| Worker nền | `scripts/watch_xml.php` | claim file, import, archive, progress, revalidation, cleanup |

## Thư mục lưu file

`storage/xml` là thư mục gốc của luồng xử lý:

- `processing/worker-N`: file worker đã claim.
- `processed/YYYYMMDD`: file xử lý thành công.
- `failed/YYYYMMDD`: file lỗi decode/validation và `.error.txt`.
- `archive/YYYYMMDD`: phiên bản cũ khi cùng `MA_LK` được import lại.
- `deleted`: vùng giữ file đã xóa trước khi cleanup theo retention.
- `portal/YYYYMMDD`: bản sao lưu khi đẩy cổng.

Runtime/log của watcher nằm trong `storage/watcher` (hoặc đường dẫn cấu hình tương ứng).

## Hàng đợi và trạng thái

- Upload: `xml_upload_queue`; trình duyệt chỉ upload/đưa queue, watcher xử lý nền.
- `UploadQueueService` lưu metadata `username/id_khoa` trước rồi mới đổi tên file staging sang `.xml`. Thứ tự này bảo đảm watcher không claim file trước khi thông tin người upload đã sẵn sàng.
- Revalidation: trạng thái nằm trên bảng `file`; `FilePeer::markAllForRevalidation()` tạo queue và `claimPendingRevalidation()` claim batch.
- Mutex MySQL của revalidation dùng để tránh nhiều worker/web cùng tạo hoặc claim queue một lúc.
- Progress import dùng file runtime `.xml_import_progress.json`, được lock khi nhiều worker cùng cập nhật.

## Audit và đẩy cổng

- `file_audit_logs`: truy vết import/update/xử lý file.
- `cong_hoso`: dữ liệu hồ sơ đã đẩy cổng; lưu `nguoi_day`, `id_khoa`, `ngay_day` để biết ai đã đẩy hồ sơ nào.
- `cong_benhnhan`: thông tin bệnh nhân dùng chung cho dữ liệu cổng.
- `FilePeer::getCongHosoData()` là nguồn dữ liệu cho modal “Lịch sử đẩy cổng”.

## Quy tắc khi sửa code

- Giữ `MA_LK` là khóa nghiệp vụ khi reimport; kiểm tra kỹ luồng archive trước khi đổi cách lưu file.
- Khi sửa upload/revalidation, kiểm tra cả web (`fileAction.php`, `js/file.js`) và worker (`watch_xml.php`).
- Khi thêm rule XML, ưu tiên cấu hình/rule hiện có trước khi thêm nhánh đặc thù vào `XmlRulesValidator`.
- Không ghi dữ liệu nhạy cảm đầy đủ vào log; log watcher nên tập trung file, rule, trạng thái và lỗi cần vận hành.
- Sau khi sửa PHP/JS, chạy `php -l`, `node --check js/file.js` và `git diff --check`.

# QL Error - Tài liệu source code

Ứng dụng `ql_error` dùng để tiếp nhận hồ sơ XML, giải mã nội dung, kiểm tra theo bộ rule, theo dõi lỗi/cảnh báo, lưu lịch sử xử lý và đẩy hồ sơ hợp lệ sang vùng dữ liệu cổng. Ứng dụng chạy trên PHP/MySQL theo mô hình PHP truyền thống, giao diện web dùng các action trong `web_src/admin`, còn việc import XML nặng được tách sang worker CLI.

Tài liệu này là điểm bắt đầu để đọc source. Hai tài liệu chuyên sâu đã có sẵn:

- [`MAINTENANCE.md`](MAINTENANCE.md): bản đồ source và lưu ý khi bảo trì.
- [`XML_WATCHER.md`](XML_WATCHER.md): cách chạy worker, cơ chế claim file, revalidation, lưu log và dọn file.

## 1. Công nghệ và thành phần chính

- PHP chạy qua XAMPP/Apache.
- MySQL/MariaDB, truy cập chủ yếu bằng `mysqli`.
- PHP CLI cho worker `scripts/watch_xml.php`.
- cURL cho các rule gọi API ngoài.
- `XMLReader`/`SimpleXML` và Base64 cho luồng decode XML.
- JavaScript/jQuery/DataTables cho giao diện quản lý.
- Excel/PHPExcel cũ vẫn tồn tại trong source cho một số chức năng xuất dữ liệu.

Cấu hình hệ thống nằm trong [`conf/config.php`](conf/config.php). Các giá trị cần quan tâm khi triển khai gồm thông tin kết nối database, URL ứng dụng và các đường dẫn worker/XML storage.

## 2. Cách request đi qua ứng dụng

Apache rewrite URL về `index.php` bằng `.htaccess`. Router tự viết trong [`index.php`](index.php) tách URL thành `module/action`, sau đó nạp class tương ứng trong `web_src/admin`.

Ví dụ:

```text
/file/getData
    -> index.php
    -> web_src/admin/fileAction.php
    -> fileAction::getData()
    -> FilePeer/service
    -> MySQL hoặc filesystem
    -> JSON
```

Luồng render trang thông thường:

```text
Browser
  -> index.php
  -> <module>Action::index()
  -> Request::setModel(...)
  -> www/View/_sharedLayout/index.htm
  -> www/<module>/...
```

Luồng AJAX thường trả `null` sau khi action đã ghi JSON trực tiếp ra response.

## 3. Cấu trúc thư mục

| Đường dẫn | Chức năng |
| --- | --- |
| `conf/` | Cấu hình database, URL và đường dẫn runtime |
| `web_src/admin/` | Controller/action nhận request web |
| `web_src/bean/` | Truy cập database, model/peer và logic dữ liệu |
| `web_src/common/` | Service dùng chung: XML, queue, portal, lookup, API, storage |
| `www/` | View PHP/HTML theo từng module |
| `js/` | JavaScript phía trình duyệt |
| `css/` | CSS của giao diện |
| `scripts/` | Worker và script chạy nền/CLI |
| `storage/` | File XML, file runtime và log worker |
| `sql/` | Schema ban đầu và migration bổ sung |
| `tests/` | Test PHP độc lập cho các rule và xử lý XML |
| `template/`, `download/`, `import/` | Tài nguyên/import-export cũ của ứng dụng |

Không nên thêm logic nghiệp vụ vào thư viện bên thứ ba như `web_src/common/PHPExcel`, jQuery, Bootstrap hoặc DataTables.

## 4. Luồng upload và xử lý XML

Điểm vào chính là trang [`www/file/index.php`](www/file/index.php), JavaScript [`js/file.js`](js/file.js) và action [`web_src/admin/fileAction.php`](web_src/admin/fileAction.php).

Luồng xử lý:

```text
Người dùng chọn file XML
  -> fileAction::upload()
  -> UploadQueueService::enqueue()
  -> ghi metadata username/id_khoa vào xml_upload_queue
  -> publish file vào storage/xml
  -> scripts/watch_xml.php claim file
  -> XmlFileImportService
       -> XmlFileDecoder
       -> XmlRulesValidator
       -> BHYT/API/Table lookup nếu rule yêu cầu
       -> FilePeer insert/update DB
  -> FileStorageService chuyển file sang processed/failed
  -> ghi audit/log/progress
```

`UploadQueueService` ghi metadata người upload trước khi đổi tên staging thành file `.xml` có thể được worker nhìn thấy. Vì vậy worker không nhận file trước khi thông tin người upload/khoa đã sẵn sàng.

### Trạng thái file

- `storage/xml/processing/worker-N`: file đã được worker claim.
- `storage/xml/processed/YYYYMMDD`: import thành công và không có lỗi validation.
- `storage/xml/failed/YYYYMMDD`: XML lỗi hoặc validation có lỗi.
- `storage/xml/archive/YYYYMMDD`: bản cũ khi cùng `MA_LK` được import lại.
- `storage/xml/deleted`: file người dùng đã xóa, chờ cleanup theo retention.
- `storage/xml/portal/YYYYMMDD`: bản sao hồ sơ khi đẩy cổng.

Database chủ yếu lưu metadata, trạng thái, đường dẫn file và kết quả validation. Nội dung XML được đọc từ file vật lý khi cần hiển thị.

## 5. Worker nền

Worker chính là [`scripts/watch_xml.php`](scripts/watch_xml.php).

Chạy liên tục trên XAMPP Windows:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php
```

Chạy một vòng rồi thoát:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php --once
```

Hai worker có thể chạy song song bằng `--worker-id=1` và `--worker-id=2`. Mỗi worker có lock và thư mục claim riêng, nên một file chỉ được một worker nhận.

Worker là process độc lập với session trình duyệt. Vì vậy:

- F5/truy cập lại trang không làm dừng worker.
- Đăng xuất khỏi web không làm dừng worker đang chạy.
- Số "Đang xử lý" trên giao diện được đọc lại từ trạng thái queue/runtime; bản thân xử lý không phụ thuộc tab trình duyệt.

Runtime và log mặc định nằm ở `storage/watcher`.

## 6. Import, decode và re-import

### `XmlFileDecoder`

[`web_src/common/XmlFileDecoder.php`](web_src/common/XmlFileDecoder.php) chịu trách nhiệm:

- đọc cấu trúc hồ sơ XML;
- giải mã phần nội dung được Base64 hóa;
- chuẩn hóa dữ liệu XML thành mảng dùng chung;
- trích các trường cần cho danh sách hồ sơ và validation.

### `XmlFileImportService`

[`web_src/common/XmlFileImportService.php`](web_src/common/XmlFileImportService.php) điều phối import:

1. kiểm tra file và dung lượng;
2. decode hồ sơ;
3. chạy validation;
4. thực hiện lookup BHYT khi có cấu hình;
5. insert file mới hoặc cập nhật hồ sơ đã có cùng `MA_LK`;
6. trả kết quả để worker quyết định vị trí lưu file.

Khi `MA_LK` đã tồn tại, hệ thống cập nhật bản ghi hiện có thay vì tạo một hồ sơ nghiệp vụ mới. File trước đó được lưu vào archive/history để vẫn truy vết được phiên bản cũ.

## 7. Validation rule

Trung tâm validation là [`web_src/common/XmlRulesValidator.php`](web_src/common/XmlRulesValidator.php). Rule được đọc từ database qua các peer cấu hình rule.

Các nhóm rule hiện tại có thể bao gồm:

- bắt buộc/nullable;
- so sánh giá trị hoặc ngày;
- công thức;
- substring;
- kiểm tra tồn tại trong bảng danh mục;
- kiểm tra chéo giữa các XML;
- gọi API ngoài;
- các rule nghiệp vụ riêng cho XML2/XML3/XML4/XML7/XML8 và vật tư/thuốc.

Khi bổ sung rule, ưu tiên dùng cấu hình trong DB và rule type đã có trước khi viết thêm nhánh đặc thù trong validator.

Tên trường lỗi nên giữ kèm loại XML, ví dụ `XML7.NGAY_RA`, để người vận hành biết chính xác trường nằm ở file thành phần nào.

## 8. Table lookup

[`web_src/common/TableLookupService.php`](web_src/common/TableLookupService.php) dùng cho rule cần tra cứu danh mục trong database.

Nguồn lookup được cấu hình trong bảng `table_lookup_sources`, gồm:

- `source_key`;
- tên bảng thật;
- danh sách cột được phép tra;
- danh sách cột điều kiện;
- trạng thái active.

Tên bảng/cột động được giới hạn theo cấu hình allow-list, còn giá trị lookup được bind bằng prepared statement. Khi thêm nguồn tra cứu mới, thêm cấu hình vào `table_lookup_sources` thay vì cho phép client truyền tên bảng tùy ý.

## 9. API validation

[`web_src/common/ApiValidationService.php`](web_src/common/ApiValidationService.php) phục vụ rule cần gọi API bên ngoài. Cấu hình nằm trong `api_validation_configs` và màn hình quản trị `apiValidation`.

Một cấu hình API gồm endpoint, HTTP method, headers, timeout và trường response cần đọc. Hệ thống hỗ trợ `GET`, `POST`, `PUT`, `PATCH`, path/query/body parameters và mapping dữ liệu từ XML.

Token hoặc API key, nếu API ngoài yêu cầu, hiện được cấu hình dưới dạng header trong cấu hình API. Không nên ghi token vào source hoặc log. Với môi trường production nên tách secret khỏi dữ liệu cấu hình có thể xem/sửa rộng rãi.

## 10. Đẩy cổng và truy vết người thực hiện

Luồng đẩy cổng nằm ở [`web_src/common/PortalService.php`](web_src/common/PortalService.php), được gọi từ `fileAction::pushPortal()`.

Luồng tổng quát:

```text
fileAction::pushPortal()
  -> PortalService::push()
  -> đọc file đã import
  -> decode hồ sơ
  -> kiểm tra lỗi/cảnh báo
  -> ghi cong_benhnhan
  -> ghi cong_hoso
  -> copy file sang storage/xml/portal/YYYYMMDD
  -> cập nhật trạng thái file
```

`cong_hoso` lưu thông tin như người đẩy, khoa và thời gian đẩy. Giao diện "Lịch sử đẩy cổng" đọc dữ liệu qua `FilePeer::getCongHosoData()` nên có thể truy vết ai đã đẩy hồ sơ nào.

## 11. Audit và lịch sử file

Các bảng quan trọng:

| Bảng | Mục đích |
| --- | --- |
| `file` | Hồ sơ XML hiện tại và trạng thái validation |
| `xml_upload_queue` | Metadata file đang chờ/được xử lý |
| `file_history` | Phiên bản file cũ khi re-import |
| `file_audit_logs` | Audit import/update/xử lý file |
| `cong_benhnhan` | Thông tin bệnh nhân dùng cho vùng dữ liệu cổng |
| `cong_hoso` | Lịch sử hồ sơ đã đẩy cổng |
| `xml_validation_rules` | Rule validation |
| `rule` | Danh mục loại rule |
| `api_validation_configs` | Cấu hình API validation |
| `table_lookup_sources` | Danh sách nguồn lookup được phép |
| `bhyt_response_cache` | Cache kết quả tra cứu BHYT |

Các bảng danh mục khác như ICD-10, khoa, nhân viên y tế, thuốc, dân tộc, đối tượng KCB... nằm trong các migration tương ứng ở `sql/`.

## 12. Revalidation

Nút quét lại trên giao diện không validate toàn bộ hồ sơ trong một request dài. `FilePeer::markAllForRevalidation()` đánh dấu queue, sau đó web/worker claim từng batch bằng `claimPendingRevalidation()`.

Các điểm cần giữ khi sửa luồng này:

- claim phải có mutex để tránh hai process nhận cùng hồ sơ;
- trạng thái progress phải có thể đọc lại sau F5;
- worker và web có thể cùng tham gia xử lý queue;
- việc reset giao diện không được hiểu là reset công việc nền;
- khi worker bị dừng giữa chừng, file trong `processing/worker-N` phải có khả năng được phục hồi ở lần chạy sau.

## 13. Đăng nhập và phân quyền

Ứng dụng web dùng PHP session tên `sAdminID`. Router trong `index.php` kiểm tra `sUserLogin` trước khi cho truy cập module.

Quyền được kiểm tra qua `Request::checkRoles()` và dữ liệu quyền trong session. `AdminType = 1` được xử lý như tài khoản quản trị toàn quyền.

Các action mới cần tuân theo cơ chế role hiện tại và kiểm tra CSRF cho thao tác ghi dữ liệu. Nhiều module mới như file/rule/API lookup đã có token CSRF riêng.

### Lưu ý mật khẩu

`UserPeer` hiện vẫn còn cơ chế mật khẩu legacy dùng MD5. Khi có kế hoạch nâng cấp xác thực, nên chuyển sang `password_hash()`/`password_verify()` và migration theo kiểu nâng cấp hash sau lần đăng nhập thành công để không buộc reset toàn bộ mật khẩu cùng lúc.

## 14. SQL injection và query động

Các peer mới/sửa gần đây ưu tiên prepared statement và ép kiểu ID số trước khi đưa vào query.

Khi viết query mới:

- bind mọi dữ liệu do người dùng hoặc file XML cung cấp;
- với `IN (...)`, tạo placeholder theo số phần tử hoặc ép kiểu chặt nếu danh sách thực sự là số nguyên;
- không bind được table/column name, vì vậy identifier động phải đi qua allow-list;
- không xem `Request::getParameter()` là lớp phòng SQL injection thay cho prepared statement.

`BackupRestorePeer::restore()` là chức năng đặc quyền cao: file SQL restore được thực thi thành các câu SQL trong database. Chức năng restore cần chỉ dành cho quản trị viên tin cậy và file backup đáng tin cậy.

## 15. Các module giao diện chính

Mỗi module thường có ba lớp:

```text
web_src/admin/<module>Action.php   nhận request / kiểm tra input / chọn view
web_src/bean/<Something>Peer.php  đọc ghi database
www/<module>/...                  HTML/PHP view
js/<module>...                    AJAX và tương tác trình duyệt
```

Các module đang có gồm quản lý file XML, rules, API validation, table lookup, BHYT, khoa/khối/khoa phòng, dân tộc, ICD-10, ICD-10 YHCT, nhân viên y tế, thuốc, đối tượng KCB, loại KCB, báo cáo và quản trị người dùng/phân quyền.

## 16. Database và migration

Thư mục [`sql/`](sql/) chứa cả schema cũ và các migration bổ sung theo thời gian. Các file đáng chú ý cho luồng XML hiện tại:

- `create_xml_upload_queue.sql`
- `migrate_file_storage_to_filesystem.sql`
- `add_ma_lk_to_file.sql`
- `add_patient_info_to_file.sql`
- `add_file_history.sql`
- `add_portal_status.sql`
- `add_cong_portal.sql`
- `create_rule_table.sql`
- `create_table_lookup_sources.sql`
- `add_compare_and_api_rules.sql`
- `create_bhyt_response_cache.sql`
- các file `add_*_rule.sql`, `fix_*_rule.sql`, `update_*_rule.sql`

Không nên chạy toàn bộ file SQL theo thứ tự tên một cách mù quáng trên database đã có dữ liệu. Cần kiểm tra schema hiện tại và chỉ áp dụng migration còn thiếu.

## 17. Test và kiểm tra trước khi bàn giao

Các test hiện có trong `tests/` tập trung vào rule và tổ chức file lỗi. Có thể chạy từng file bằng PHP CLI, ví dụ:

```powershell
C:\xampp\php\php.exe tests\Xml1PatientInfoTest.php
C:\xampp\php\php.exe tests\CrossFileAnyMatchRuleTest.php
C:\xampp\php\php.exe tests\XmlErrorArchiveOrganizerTest.php
```

Sau khi sửa PHP/JS nên chạy tối thiểu:

```powershell
C:\xampp\php\php.exe -l web_src\admin\fileAction.php
C:\xampp\php\php.exe -l scripts\watch_xml.php
node --check js\file.js
git diff --check
```

Nếu thay đổi rule hoặc XML service, chạy toàn bộ test trong `tests/`.

## 18. Tìm file cần sửa theo yêu cầu

| Muốn sửa | Bắt đầu từ |
| --- | --- |
| Upload XML | `web_src/admin/fileAction.php`, `UploadQueueService.php`, `js/file.js` |
| Worker không xử lý file | `scripts/watch_xml.php`, `storage/watcher/logs/` |
| Decode XML | `XmlFileDecoder.php` |
| Import/re-import | `XmlFileImportService.php`, `FilePeer.php` |
| Validation/rule | `XmlRulesValidator.php`, `XmlValidationRulePeer.php`, `sql/*rule*.sql` |
| Lookup danh mục | `TableLookupService.php`, `table_lookup_sources` |
| Gọi API validation | `ApiValidationService.php`, `ApiValidationConfigPeer.php` |
| Tra cứu BHYT | `BhytLookupService.php` |
| Đẩy cổng | `PortalService.php`, `FilePeer.php`, `fileAction.php` |
| Lịch sử người upload/đẩy | `xml_upload_queue`, `file_audit_logs`, `cong_hoso` |
| Tiến độ/quét lại | `FilePeer.php`, `fileAction.php`, `watch_xml.php`, `js/file.js` |
| Lưu/chuyển/xóa file vật lý | `FileStorageService.php` |
| Login/phân quyền | `loginAction.php`, `UserPeer.php`, `Request.php` |
| Backup/restore | `backupAction.php`, `BackupRestorePeer.php` |

## 19. Checklist khi bảo trì

1. Xác định thay đổi nằm ở web, worker hay cả hai.
2. Nếu thay đổi cấu trúc dữ liệu, tạo migration riêng trong `sql/`.
3. Nếu thay đổi upload/revalidation, kiểm tra đồng thời `fileAction.php`, `js/file.js`, `FilePeer.php` và `watch_xml.php`.
4. Nếu thay đổi rule, kiểm tra cả dữ liệu rule trong DB và code validator.
5. Không đổi ý nghĩa của `MA_LK` nếu chưa rà luồng re-import/archive/history.
6. Không log toàn bộ dữ liệu bệnh nhân hoặc secret API nếu không thật sự cần.
7. Chạy syntax check, test liên quan và `git diff --check` trước khi bàn giao.

## 20. Tài liệu vận hành liên quan

Đọc tiếp [`XML_WATCHER.md`](XML_WATCHER.md) khi cần chạy/reset worker hoặc phân tích file không được xử lý. Đọc [`MAINTENANCE.md`](MAINTENANCE.md) khi cần bản đồ ngắn gọn của các file đang trực tiếp quản lý luồng XML.

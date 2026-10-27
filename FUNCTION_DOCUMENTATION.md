# QL Error - Tài liệu chức năng

Tài liệu này mô tả các chức năng đang có trong source hiện tại của project `ql_error`, tập trung vào các action web, service xử lý XML và worker nền. Mục tiêu là giúp người bảo trì biết một chức năng nằm ở đâu, nhận dữ liệu gì, trả kết quả gì, chạm vào bảng/service nào và có lưu ý bảo mật nào.

## 1. Quy ước chung

Router trong `index.php` ánh xạ URL theo dạng:

```text
/<module>/<action>
    -> web_src/admin/<module>Action.php
    -> <module>Action::<action>()
```

Ví dụ `/file/getData` gọi `fileAction::getData()`.

Các action render giao diện thường gọi `Request::setModel(...)` và trả `true`. Các action AJAX thường trả JSON thông qua `Request::json_response(...)`. Source hiện tại không khai báo HTTP method ở router, vì vậy tài liệu bên dưới ghi **tham số request** thay cho việc khẳng định GET/POST nếu action không tự ép method.

Quyền truy cập được kiểm tra qua session và cơ chế `Request::checkRoles()`. Nhiều action khai báo `public static $listRole`. Tài khoản có `AdminType = 1` được hệ thống xử lý như quản trị viên toàn quyền.

Các session quan trọng:

| Session | Ý nghĩa |
| --- | --- |
| `sUserID` | ID người dùng đăng nhập |
| `sUserName` | Tên đăng nhập |
| `FullName` | Họ tên hiển thị |
| `AdminType` | Loại tài khoản; `1` là quản trị |
| `sUserLogin` | Trạng thái đăng nhập |
| `sToken` | Token phiên được lưu đồng thời vào bảng `user` |
| `id_khoa` | Khoa hiện tại nếu session có thiết lập |

## 2. Xác thực và phiên đăng nhập

File: `web_src/admin/loginAction.php`

Dữ liệu chính: bảng `user`, session PHP.

### `login/index`

- Mục đích: hiển thị màn hình đăng nhập.
- Xử lý: tạo một tên field mật khẩu ngẫu nhiên trong `$_SESSION['sPassword']`, truyền tên field này cho `www/admin/login.htm`.
- Output: HTML trang đăng nhập.
- Lưu ý: đây là cơ chế legacy; tên field password thay đổi theo session.

### `login/login`

- Input: `txtUserName` và field mật khẩu có tên bằng giá trị `$_SESSION['sPassword']`.
- Xử lý: gọi `UserPeer::getUserLogin()`, kiểm tra tài khoản không bị khóa, tạo session đăng nhập, sinh `sToken`, lưu token vào bảng `user`, nạp quyền người dùng vào `Request`.
- Thành công: chuyển sang action mặc định của hệ thống.
- Thất bại: render lại trang đăng nhập với thông báo lỗi.
- Bảng/service: `user`, `UserPeer`.
- Lưu ý bảo mật: mật khẩu hiện còn tương thích cơ chế MD5 legacy trong `UserPeer`.

### `login/logout`

- Mục đích: kết thúc phiên.
- Xử lý: xóa các session đăng nhập, xóa cookie session, hủy session và redirect về `/login/`.
- Side effect: quyền trong `Request` được đặt lại rỗng.

### `login/changePass`

- Mục đích: mở màn hình đổi mật khẩu cho người đang đăng nhập.
- Dữ liệu: đọc user bằng `sUserName`.
- Output: `www/admin/changePass.htm` hoặc quay lại đăng nhập nếu user không tồn tại.

### `login/savePass`

- Input: `oldpass`, `newpass`, `verifypass`.
- Kiểm tra: mật khẩu cũ phải khớp `md5(oldpass)`, mật khẩu mới không rỗng, `newpass === verifypass`.
- Thành công: gọi `UserPeer::save(..., 1)` để cập nhật mật khẩu và render trang thành công.
- Lưu ý: cơ chế hash đang là legacy; khi nâng cấp nên chuyển sang `password_hash()`/`password_verify()` có chiến lược migration.

## 3. Quản lý người dùng

File: `web_src/admin/userAction.php`

Role: `user,saveUser,deleteUser,phanquyen,lock`

Bảng chính: `user`, `nhomquyen`, `khoa`.

### `user/index`

- Mục đích: hiển thị danh sách người dùng.
- Dữ liệu chuẩn bị: danh sách nhóm quyền và danh sách khoa.
- View: `www/admin/nguoidung/nguoiDung.htm`.

### `user/getData`

- Mục đích: lấy toàn bộ danh sách người dùng.
- Output: `{ "data": [...] }`.
- Nguồn: `UserPeer::getListUser()`.

### `user/saveUser`

- Input:
  - `id`: `0` khi thêm, ID hiện có khi sửa.
  - `data`: JSON array theo thứ tự `[hoTen, username, password, maNQ, id_khoa]`.
- Thêm mới:
  1. kiểm tra dữ liệu bắt buộc;
  2. kiểm tra `maNQ` là số;
  3. kiểm tra username chưa tồn tại;
  4. lấy chuỗi quyền từ nhóm quyền;
  5. lưu user cùng `id_khoa`.
- Cập nhật:
  - giữ username hiện có;
  - giá trị password `***` được hiểu là không đổi mật khẩu;
  - nếu đổi nhóm quyền, quyền của user được đồng bộ từ nhóm mới.
- Output: object có `id` và `message`.
- Lưu ý: action này là code legacy và chưa có CSRF riêng trong chính action.

### `user/deleteUser`

- Input: `id`.
- Xử lý: `UserPeer::deleteUser(id)`.
- Output: JSON boolean `true`.

### `user/lockUser`

- Input: `maUser`, `lock`.
- Xử lý: đảo trạng thái khóa `0 <-> 1`, gọi `UserPeer::updateLock()`.
- Output: trạng thái khóa mới.

### `user/getUsers`

- Input: `searchString`, `listUsers`.
- Mục đích: tìm user phục vụ các màn hình chọn người dùng.
- Output: danh sách từ `UserPeer::getUsers()`.

### `user/phanquyen`

- Input: `maUser`.
- Xử lý: đọc quyền hiện tại của user và toàn bộ danh sách chức năng.
- Output: include trực tiếp `www/admin/phanquyen/viewListPhanQuyen.htm`.

### `user/savePhanquyen`

- Input: `username`, `numCheck`, các mảng `checkbox0 ... checkboxN` trong `$_POST`.
- Xử lý: ghép các quyền thành chuỗi phân tách bằng dấu phẩy, so sánh với quyền cũ và cập nhật `user.quyen`.
- Output: JSON chuỗi `"Đã lưu"`.

## 4. Nhóm quyền

File: `web_src/admin/nhomquyenAction.php`

Role: `nhomquyen,save,delete,phanquyen`

Bảng: `nhomquyen`.

### `nhomquyen/index`

- Hiển thị trang quản lý nhóm quyền.
- View: `www/admin/nguoidung/nhomquyen.htm`.

### `nhomquyen/getData`

- Input: `tenNQ`.
- Output: `{ "data": [...] }` từ `NhomQuyenPeer::getListNQ()`.

### `nhomquyen/save`

- Input: `id`, `data` JSON array; phần tử đầu là tên nhóm quyền.
- Hiện trạng: chỉ cho cập nhật role đã có; `id <= 0` bị từ chối.
- Output: object `message` và `id` khi thành công.

### `nhomquyen/delete`

- Input: `id`.
- Xử lý: xóa khỏi bảng `nhomquyen`.
- Output: JSON boolean `true`.

### `nhomquyen/phanquyen`

- Input: `maNQ`.
- Xử lý: đọc quyền của nhóm và danh sách chức năng.
- Output: include `viewListPhanQuyen.htm`.

### `nhomquyen/savePhanquyen`

- Input: `username` thực tế đang chứa mã nhóm quyền, `numCheck`, các `checkboxN`.
- Xử lý: ghép chuỗi quyền và cập nhật `nhomquyen.quyen`.
- Output: JSON chuỗi `"Đã lưu"`.

## 5. Hồ sơ XML - `fileAction`

File: `web_src/admin/fileAction.php`

Service: `UploadQueueService`, `FileStorageService`, `PortalService`, `XmlFileDecoder`, `XmlRulesValidator`, `ApiValidationService`, `TableLookupService`, `BhytLookupService`

Bảng chính: `file`, `xml_upload_queue`, `file_history`, `file_audit_logs`, `cong_benhnhan`, `cong_hoso`.

### `file/index`

- Mục đích: mở màn hình quản lý XML.
- Xử lý: lấy khoa hiện tại, tạo `xml_file_csrf`, nạp `css/file.css`, `js/file.js`.
- View: `www/file/index.php`.

### `file/getData`

- Input: `date` bắt buộc dạng `Y-m-d`.
- Xử lý: gọi `FilePeer::getList(date)`.
- Output thành công: `{ "data": [...] }`.
- Lỗi ngày: `{ "data": [], "error": "..." }`.

### `file/upload`

- Mục đích: đưa XML vào hàng đợi để worker xử lý.
- Input:
  - `csrf_token`;
  - file upload `xmlFile`;
  - `id_khoa` tùy chọn.
- Kiểm tra:
  - CSRF hợp lệ;
  - file tồn tại và PHP upload không lỗi;
  - dung lượng `1 byte .. 5 MB`;
  - tên file tối đa 180 ký tự, chỉ nhận `.xml`, không chấp nhận path traversal.
- Xử lý: `UploadQueueService::enqueue(tmp, fileName, id_khoa, username)` ghi metadata trước rồi publish file vào vùng worker quan sát.
- Output ví dụ:

```json
{
  "success": true,
  "queued": true,
  "name": "hoso.xml",
  "message": "Đã đưa file XML vào hàng đợi xử lý."
}
```

### `file/getContent`

- Input: `id` file.
- Xử lý:
  1. đọc record bằng `FilePeer::getById()`;
  2. resolve `file_path` an toàn;
  3. đọc XML vật lý;
  4. decode bằng `XmlFileDecoder::decodeDanhSachHoSo()`;
  5. decode `validation_result`;
  6. đọc cấu hình hiển thị XML từ `HeThongPeer`.
- Output: `name`, `decoded`, `validation`, `display_settings`.

### `file/getCongHosoData`

- Mục đích: lịch sử hồ sơ đã đẩy cổng.
- Input: `date` tùy chọn `Y-m-d`, `malk` tùy chọn, `limit` tùy chọn; mặc định `1000`.
- Output: `{ "data": [...] }` từ `FilePeer::getCongHosoData()`.
- Bảng: `cong_hoso`.

### `file/pushPortal`

- Mục đích: đẩy một hoặc nhiều hồ sơ hợp lệ sang vùng dữ liệu cổng.
- Input:
  - `ids`: chuỗi JSON array các file ID;
  - `confirm_warning=1` nếu người dùng xác nhận tiếp tục khi có warning.
- Xử lý: `PortalService::push(ids, confirmedWarning, username, idKhoa)`.
- Side effect:
  - ghi/cập nhật `cong_benhnhan`;
  - ghi `cong_hoso`;
  - copy XML sang `storage/xml/portal/YYYYMMDD`;
  - đánh dấu file đã đẩy cổng;
  - ghi log/audit.
- Output: `{ "success": true, "done": [...], "skipped": [...] }`.
- Lưu ý bảo mật: action hiện tại không gọi `validCsrf()` trước side effect; đây là điểm cần lưu ý khi bảo trì.

### `file/getAuditLogs`

- Input: `ma_lk`.
- Output: `{ "success": true, "data": [...] }`.
- Bảng: `file_audit_logs`.

### `file/download`

- Input: `id`.
- Xử lý: resolve file vật lý, chuẩn hóa tên tải xuống, trả nội dung XML.
- Response header chính: `Content-Type: application/xml`, `Content-Disposition: attachment`, `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store`.
- Lỗi: HTTP `400` nếu ID sai, `404` nếu file/record không tồn tại.

### `file/getErrorReport`

- Mục đích: tổng hợp lỗi/cảnh báo theo ngày, khoảng ngày hoặc tháng.
- Input:
  - `period=month` và `date=Y-m`; hoặc
  - `from_date`, `to_date`; hoặc
  - `date=Y-m-d`;
  - `report_scope`: `hospital` hoặc `khoa`;
  - `report_khoa`: ID khoa khi admin chọn báo cáo theo khoa.
- Phân quyền dữ liệu:
  - admin có thể chọn khoa;
  - user thường bị giới hạn theo khoa gắn với tài khoản.
- Output: `{ "success": true, "data": <rows lỗi>, "files": <rows hồ sơ> }`.
- Nguồn: `FilePeer::getErrorReportRows()`, `FilePeer::getAllFileReportRows()`.

### `file/delete`

- Input: `csrf_token`, `id`.
- Xử lý: chuyển file vật lý sang `storage/xml/deleted`, sau đó xóa record DB. Nếu file vật lý đã mất, record DB vẫn có thể bị xóa.
- Nếu xóa DB lỗi sau khi đã move file, code cố gắng rollback file về vị trí cũ.
- Output: JSON success/message.

### `file/deleteMany`

- Input: `csrf_token`, `ids` dạng array hoặc chuỗi CSV.
- Giới hạn: tối đa 500 ID hợp lệ mỗi request.
- Xử lý: dùng cùng `deleteFiles()` như xóa một file.
- Output: `deleted` và message số lượng đã xóa.

### `file/revalidateAll`

- Mục đích: đưa hồ sơ hiện có vào hàng đợi quét lại rule.
- Input: `csrf_token`, `date=Y-m-d`.
- Phân quyền dữ liệu: user thường chỉ queue hồ sơ của khoa mình; admin không bị giới hạn khoa.
- Xử lý: `FilePeer::markAllForRevalidation()` và đọc progress ngay sau đó.
- Output: `queued`, `total`, `progress`, `message`.
- Khi queue khác đang chạy, `queued` có thể là `-1` ở tầng peer và action chuyển thành thông báo hàng đợi đang được xử lý.

### `file/processRevalidationBatch`

- Input: `csrf_token`, `date`.
- Xử lý mỗi request claim tối đa `2` hồ sơ bằng `claimPendingRevalidation()`.
- Mỗi hồ sơ được decode, chạy rule mới, chuyển sang `processed` hoặc `failed`, cập nhật `validation_result` và `file_path`.
- Output: `processed`, `remaining`, `progress`, `errors`.

### `file/revalidationProgress`

- Input: `date`.
- Output: progress hiện tại từ DB.
- Mục đích: giao diện có thể khôi phục tiến độ sau F5 thay vì giữ state chỉ ở browser.

### `file/uploadProgress`

- Input:
  - `date`;
  - `files` tùy chọn là JSON array tên file vừa upload.
- Nếu có `files`: đếm số file trong queue còn lại cho đúng batch upload đó.
- Nếu không có `files`: đếm queue upload đang active trong ngày.
- Output: `remaining` và `statistics`.

### `file/revalidationStream`

- Mục đích: stream tiến độ quét lại bằng Server-Sent Events.
- Input: `date`.
- Response: `text/event-stream`.
- Event:
  - `progress`: JSON progress;
  - `error`: thông báo exception.
- Chu kỳ: tối đa khoảng 30 vòng, mỗi vòng nghỉ 1 giây và gửi heartbeat; stream dừng sớm khi `remaining <= 0`.

### Helper quan trọng của `fileAction`

| Helper | Vai trò |
| --- | --- |
| `revalidateQueuedFile()` | Quét lại một record đã claim, cập nhật validation và vị trí file |
| `deleteFiles()` | Move file vào `deleted`, xóa DB, rollback move nếu DB lỗi |
| `safeFileName()` | Chặn path traversal và tên XML không hợp lệ |
| `createValidationCallback()` | Ghép `XmlRulesValidator` với rule DB, API service và table lookup |
| `validationHasErrors()` | Xác định validation có lỗi severity error hay không dựa trên `error_count` |
| `currentUsername()` | Lấy `sUserName` |
| `currentUserKhoa()` | Lấy khoa từ DB hoặc session tùy ngữ cảnh |
| `validCsrf()` | So sánh `csrf_token` với `xml_file_csrf` bằng `hash_equals()` |
| `selectedDate()` | Chỉ nhận ngày đúng định dạng `Y-m-d` |

## 6. Báo cáo

File: `web_src/admin/baocaoAction.php`.

### `baocao/index`

- Kế thừa `fileAction`, vì vậy dùng lại các endpoint báo cáo trong `fileAction`.
- Đọc danh sách khoa từ bảng `khoa` để admin chọn phạm vi báo cáo.
- View: `www/baocao/index.php`.
- JS: dùng cả `js/file.js` và `js/baocao.js`.

Endpoint dữ liệu báo cáo chính là `file/getErrorReport` hoặc route tương đương khi router gọi method kế thừa.

## 7. Quản lý rule validation

### 7.1. Rule nghiệp vụ XML

File: `web_src/admin/rulesAction.php`

Role: `rules,save,update,delete`

Bảng: `xml_validation_rules`, `rule`

Audit: `LogPeer`.

#### `rules/index`

- Tạo CSRF cho màn hình rule.
- Render giao diện quản lý rule.

#### `rules/getData`

- Trả danh sách rule cấu hình hiện tại.

#### `rules/getRuleTypes`

- Trả danh mục loại rule đang active để UI chọn.

#### `rules/save`

- Input chính: `file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `huongxuly`, `severity`, `is_active`, `csrf_token`.
- Xử lý: validate cấu hình theo `rule_type`, thêm vào `xml_validation_rules`, ghi log.

#### `rules/update`

- Input: như `save` cộng `id`.
- Xử lý: đọc bản cũ, validate bản mới, update và ghi audit thay đổi.

#### `rules/delete`

- Input: `id`, `csrf_token`.
- Xử lý: xóa rule và ghi log.

#### `rules/updateActive`

- Input: `id`, `is_active`, `csrf_token`.
- Mục đích: bật/tắt rule nhanh.

#### Nhóm `rule_type` được `readRule()` xử lý

Source hiện có nhánh kiểm tra cho các loại cấu hình như:

- rule cơ bản bắt buộc/định dạng/giá trị;
- `FIELD_COMPARE`;
- `CONDITIONAL_FIELD`;
- `CROSS_FILE_REQUIRED_BY_KEY`;
- `TABLE_EXISTS`;
- `API`;
- `SUBSTRING`;
- `CCCD_GENDER_CENTURY`;
- `FORMULA`.

Giá trị `rule_value` của các rule phức tạp thường là JSON và được action kiểm tra cấu trúc trước khi lưu.

### 7.2. Danh mục loại rule

File: `web_src/admin/ruleAction.php`

Role: `rule,save,update,delete`

Bảng: `rule`.

#### `rule/index`

- Tạo CSRF và hiển thị danh mục loại rule.

#### `rule/getData`

- Trả danh sách loại rule.

#### `rule/save`

- Input: `code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`, `csrf_token`.
- `code` được chuẩn hóa uppercase và chỉ chấp nhận chữ in hoa, số, dấu gạch dưới.

#### `rule/update`

- Input: như `save` cộng `id`.
- Cập nhật metadata loại rule.

#### `rule/delete`

- Input: `id`, `csrf_token`.
- Xóa loại rule nếu tầng peer cho phép.

## 8. Cấu hình API validation

File: `web_src/admin/apiValidationAction.php`

Role: `apiValidation,save,update,delete,test`

Bảng: `api_validation_configs`.

### `apiValidation/index`

- Tạo `api_validation_csrf`.
- View: `www/apiValidation/index.php`.

### `apiValidation/getData`

- Output: `{ "data": [...] }` từ `ApiValidationConfigPeer::getList()`.

### `apiValidation/save`

- Input:
  - `name`;
  - `endpoint`;
  - `method`: chỉ `GET`, `POST`, `PUT`, `PATCH`;
  - `headers`: JSON object;
  - `timeout_seconds`: `1..30`;
  - `response_field`;
  - `is_active`;
  - `csrf_token`.
- Endpoint được kiểm tra phải là URL HTTP/HTTPS; placeholder dạng `{parameter}` được cho phép khi validate URL.
- Output: `success`, `id`, `message`.

### `apiValidation/update`

- Input: như `save` cộng `id > 0`.
- Output: success/message.

### `apiValidation/delete`

- Input: `id`, `csrf_token`.
- Peer kiểm tra tồn tại và có thể kiểm tra cấu hình đang được rule tham chiếu.

### `apiValidation/test`

- Input:
  - `id` cấu hình;
  - `path_parameters`: JSON object;
  - `query_parameters`: JSON object;
  - `body_parameters`: JSON object;
  - `csrf_token`.
- Xử lý: gọi `ApiValidationService::call()` với payload `path/query/body/auto`.
- Output ví dụ:

```json
{
  "success": true,
  "message": "Gọi API thành công.",
  "response": {}
}
```

- Lưu ý: header có thể chứa token/API key; không nên ghi secret vào log hoặc commit source.

## 9. Nguồn đối chiếu `TABLE_EXISTS`

File: `web_src/admin/tablelookupAction.php`

Role: `tablelookup,save,update,delete,toggleStatus`

Bảng: `table_lookup_sources`.

### `tablelookup/index`

- Tạo CSRF.
- Đọc schema database để UI cho chọn bảng/cột.
- View: `www/tablelookup/index.php`.

### `tablelookup/getData`

- Trả danh sách nguồn lookup.

### `tablelookup/save`

- Input:
  - `source_key`: chữ thường/số/underscore, bắt đầu bằng chữ;
  - `display_name`;
  - `table_name`;
  - `allowed_columns`;
  - `condition_columns`;
  - `is_active`;
  - `csrf_token`.
- `table_name` và tên cột được kiểm tra regex identifier.
- Phải có ít nhất một `allowed_columns`.

### `tablelookup/update`

- Input như `save` cộng `id`.

### `tablelookup/delete`

- Input: `id`, `csrf_token`.
- Peer kiểm tra nguồn có đang được `xml_validation_rules` loại `TABLE_EXISTS` tham chiếu hay không.

### `tablelookup/toggleStatus`

- Input: `id`, `is_active`, `csrf_token`.
- Bật/tắt nguồn tra cứu.

### Cơ chế an toàn của lookup

`TableLookupService` không cho client tự do đưa tên bảng/cột vào SQL. Tên bảng/cột phải nằm trong allow-list của `table_lookup_sources`; giá trị cần tra được bind bằng prepared statement.

## 10. Danh mục nhóm BHYT

File: `web_src/admin/bhytAction.php`

Role: `bhyt,save,delete`

Bảng: `nhom_BHYT`, `nhom_BHYT_dien`.

### `bhyt/index`

- Tạo `bhyt_csrf` và hiển thị danh mục nhóm BHYT.

### `bhyt/getData`

- Trả danh sách nhóm và các diện tương ứng.

### `bhyt/save`

- Input:
  - `original_id`: rỗng khi thêm, có giá trị khi sửa;
  - `id`: mã nhóm, `2..10` ký tự uppercase/số;
  - `ten`;
  - `mota`;
  - `dien`: array gồm 1 hoặc 2 giá trị trong `1..5`;
  - `csrf_token`.
- Xử lý: insert/update `nhom_BHYT`, sau đó thay danh sách `nhom_BHYT_dien`.

### `bhyt/delete`

- Input: `id`, `csrf_token`.
- Xóa nhóm BHYT.

## 11. Cấu hình hệ thống

File: `web_src/admin/hethongAction.php`

Role: `hethong`

Bảng: `hethong`.

### `hethong/index`

- Tạo `hethong_csrf`.
- View: `www/hethong/index.php`.

### `hethong/getData`

- Output: danh sách `{id, chucnang, trangthai}`.

### `hethong/updateStatus`

- Input: `id`, `trangthai` chỉ nhận `0` hoặc `1`, `csrf_token`.
- Output: trạng thái mới và message.
- `HeThongPeer::getXmlDisplaySettings()` hiện đọc các ID cấu hình `1` và `2` để điều khiển hiển thị XML.

## 12. Log người dùng

File: `web_src/admin/logAction.php`

Role: `log,deleteLog`

Bảng: `log`.

### `log/index`

- Input: `page`, `select_user`, `txtChucNang`.
- Xử lý: lọc log theo user/chức năng, phân trang bằng `PageNav`.
- View: `www/admin/log/viewListLog.htm`.

### `log/deleteLog`

- Input: `id`.
- Xử lý: `LogPeer::deleteLog(id)`.
- Output: JSON `true`.
- Lưu ý: action legacy, hiện không có CSRF riêng trong method.

## 13. Backup và restore database

File: `web_src/admin/backupAction.php`

Role: `backup`

Peer: `BackupRestorePeer`.

### `backup/index`

- View: `www/admin/backuprestore/viewBackupRestore.htm`.

### `backup/backup`

Input chính là tham số `function`:

#### `function=backup`

- Gọi `BackupRestorePeer::backup()` để sinh SQL dump.
- Trả file tải xuống có đuôi `.sql`.
- Tên file dùng `$db_name` + timestamp.

#### `function=restore`

- Input: `$_FILES['file']`.
- Kiểm tra dung lượng theo `_BACKUP_SIZE_` và extension `.sql`.
- Upload file vào `restore/`.
- Trước khi restore, hệ thống tự backup database hiện tại vào thư mục restore.
- Sau đó gọi `BackupRestorePeer::restore()`.
- Output: render lại trang với message.

Lưu ý bảo trì:

- đây là code legacy;
- thông báo lỗi extension vẫn nhắc `.xls/.xlsx` dù code chỉ nhận `.sql`;
- action không có CSRF riêng;
- restore là thao tác ảnh hưởng toàn database nên khi sửa cần kiểm tra kỹ quyền truy cập, nguồn file và transaction/rollback.

## 14. Dashboard

File: `web_src/admin/dashboardAction.php`.

### `dashboard/index`

- Chỉ render `www/admin/dashboard/viewDashboard.htm`.
- Không có xử lý dữ liệu riêng trong action.

## 15. Các danh mục tra cứu

Các module dưới đây chủ yếu là **read-only ở action hiện tại**: `index()` render trang, `getData()` trả dữ liệu. Nhiều endpoint dùng cấu trúc DataTables với `draw`, `start`, `length`, `searchText` hoặc `search[value]`.

### `danhmuc_thuoc`

- File: `web_src/admin/danhmuc_thuocAction.php`.
- Role: `danhmuc_thuoc`.
- Bảng: `danhmuc_thuoc`.
- `index()`: render danh mục thuốc.
- `getData()`: phân trang/tìm kiếm, có thêm lọc tỉnh/thành theo implementation của peer.

### `dantoc`

- File: `web_src/admin/dantocAction.php`.
- Role: `dantoc`.
- Bảng: `dan_toc`.
- `getData()`: DataTables phân trang, tìm theo mã/tên.

### `doituong_kcb`

- File: `web_src/admin/doituong_kcbAction.php`.
- Role: `doituong_kcb`.
- Bảng: `doituong_kcb`.
- `getData()`: DataTables phân trang và tìm kiếm.

### `icd10`

- File: `web_src/admin/icd10Action.php`.
- Role: `icd10`.
- Bảng: `icd10`.
- `getData()`: DataTables phân trang/tìm kiếm.
- `Icd10Peer` còn có lookup mã active để validation.

### `icd10_yhct`

- File: `web_src/admin/icd10_yhctAction.php`.
- Role: `icd10_yhct`.
- Bảng: `icd10_yhct`.
- `getData()`: DataTables phân trang/tìm kiếm.

### `khoa`

- File: `web_src/admin/khoaAction.php`.
- Role: `khoa`.
- Bảng thực tế trong peer: `khoa_phong`, self join qua `ma_khoa_goc`.
- `getData()`: DataTables phân trang/tìm kiếm.

### `khoaphong`

- File: `web_src/admin/khoaphongAction.php`.
- Role: `khoaphong`.
- Bảng thực tế trong `KhoaPhongCatalogPeer`: `khoa`, join `khoi`.
- `getData()`: trả danh sách khoa/phòng theo `searchText`.

### `khoi`

- File: `web_src/admin/khoiAction.php`.
- Role: `khoi`.
- Bảng: `khoi`.
- `getData()`: DataTables phân trang/tìm kiếm.

### `loai_kcb`

- File: `web_src/admin/loai_kcbAction.php`.
- Role: `loai_kcb`.
- Bảng: `loai_kcb`.
- `getData()`: trả `{ "data": [...] }`, không phân trang ở action.

### `nhanvienyte`

- File: `web_src/admin/nhanvienyteAction.php`.
- Role: `nhanvienyte`.
- Bảng: `nhanvienyte`.
- `getData()`: DataTables phân trang/tìm kiếm, dữ liệu chính gồm `id`, `ho_ten`, `macchn`.

### `tinhthanh`

- File: `web_src/admin/tinhthanhAction.php`.
- Role: `tinhthanh`.
- Bảng: `tinhthanh`.
- `getData()`: DataTables phân trang/tìm kiếm.

## 16. `UploadQueueService`

File: `web_src/common/UploadQueueService.php`.

### `enqueue($temporaryUploadPath, $fileName, $postedKhoa = null, $username = '')`

- Mục đích: publish file upload vào hàng đợi worker theo thứ tự an toàn.
- Luồng:
  1. xác định khoa từ request/user;
  2. tạo file staging;
  3. ghi metadata vào `xml_upload_queue`;
  4. đổi tên staging thành file XML cuối cùng để worker nhìn thấy.
- Ý nghĩa: worker chỉ có thể claim file sau khi metadata người upload/khoa đã sẵn sàng.

## 17. `XmlFileImportService`

File: `web_src/common/XmlFileImportService.php`.

### `importPath($path, $storedPath = null)`

- Kiểm tra file tồn tại/kích thước.
- Đọc nội dung và chuyển cho `importContent()`.

### `importContent($fileName, $content, $size, $filePath, $decodedResult = null)`

- Mục đích: import một hồ sơ XML hoàn chỉnh.
- Luồng chính:
  1. chuẩn hóa tên file và dung lượng;
  2. decode XML;
  3. lấy thông tin hồ sơ/bệnh nhân;
  4. chạy validation callback;
  5. thực hiện BHYT lookup nếu cấu hình yêu cầu;
  6. insert hồ sơ mới hoặc update hồ sơ cùng `MA_LK`;
  7. lưu validation result, audit/history;
  8. trả `processing_status` để worker quyết định nơi lưu file.
- Re-import cùng `MA_LK`: record hiện tại được cập nhật, bản cũ được lưu history/archive.

## 18. `XmlFileDecoder`

File: `web_src/common/XmlFileDecoder.php`.

Chức năng chính:

- đọc XML hồ sơ tổng;
- giải mã Base64 của các XML thành phần;
- chuẩn hóa dữ liệu thành array thống nhất;
- trích các trường khóa như `MA_LK`, thông tin bệnh nhân và metadata cần cho import/validation;
- cung cấp dữ liệu đã decode cho UI `file/getContent` và validator.

Decoder là lớp nền tảng: thay đổi format output của decoder có thể ảnh hưởng đồng thời import, rule validation, báo cáo và đẩy cổng.

## 19. `XmlRulesValidator`

File: `web_src/common/XmlRulesValidator.php`.

### `validate(...)`

- Input: dữ liệu XML đã decode, danh sách rule từ DB và các service phụ trợ.
- Output: kết quả validation theo từng XML/nhóm rule, có `error_count`, warning và chi tiết lỗi.
- Service phụ trợ có thể gồm:
  - `ApiValidationService` cho rule `API`;
  - `TableLookupService` cho `TABLE_EXISTS`;
  - các validator đặc thù XML2/XML3/XML4/XML7/XML8 và nghiệp vụ thuốc/vật tư.

Khi thêm rule mới, nên ưu tiên mở rộng bằng cấu hình DB nếu rule type hiện có đáp ứng được; chỉ thêm logic hard-code khi nghiệp vụ thật sự đặc thù.

## 20. `FileStorageService`

File: `web_src/common/FileStorageService.php`.

| Method | Chức năng |
| --- | --- |
| `resolveStoredFilePath()` | Resolve path lưu trong DB về file thật và kiểm soát phạm vi storage/project |
| `datedDirectory()` | Tạo/lấy thư mục theo ngày |
| `storageDirectory()` | Lấy thư mục con dưới storage XML |
| `statusDirectory()` | Lấy thư mục theo trạng thái `processed/failed/...` |
| `uniqueDestination()` | Sinh tên đích không đè file có sẵn |
| `relativeToProject()` | Chuyển absolute path thành path lưu DB tương đối |
| `moveValidatedFile()` | Chuyển XML sang `processed` hoặc `failed`, xử lý file lỗi đi kèm |
| `moveToDeleted()` | Chuyển file sang vùng `deleted` |
| `copyToPortal()` | Copy hồ sơ sang `portal/YYYYMMDD` |

Các vùng lưu chính:

```text
storage/xml/processing/worker-N
storage/xml/processed/YYYYMMDD
storage/xml/failed/YYYYMMDD
storage/xml/archive/YYYYMMDD
storage/xml/deleted
storage/xml/portal/YYYYMMDD
```

## 21. `PortalService`

File: `web_src/common/PortalService.php`.

### `push(array $ids, $confirmedWarning, $username, $idKhoa)`

- Đọc từng file đã import.
- Bỏ qua hồ sơ không đủ điều kiện hoặc đã đẩy tùy trạng thái.
- Decode nội dung XML.
- Kiểm tra lỗi và warning; warning cần xác nhận nếu flow yêu cầu.
- Ghi `cong_benhnhan`.
- Ghi `cong_hoso` gồm người đẩy, khoa, thời gian.
- Copy XML sang vùng portal.
- Cập nhật cờ `da_day_cong` của bảng `file`.
- Ghi audit/log.
- Output: danh sách `done` và `skipped`.

## 22. `ApiValidationService`

File: `web_src/common/ApiValidationService.php`.

### `call($configId, array $payload)`

- Đọc cấu hình active từ `api_validation_configs`.
- Thay placeholder path.
- Ghép query string.
- Gửi body cho `POST/PUT/PATCH`.
- Gắn headers cấu hình.
- Áp dụng timeout.
- Parse response và lấy field theo `response_field` phục vụ rule.
- Exception được đẩy lên action/validator để chuyển thành lỗi phù hợp.

## 23. `TableLookupService`

File: `web_src/common/TableLookupService.php`.

### `exists($value, array $config)`

- Trả boolean cho biết giá trị có tồn tại theo nguồn lookup hay không.

### `preload(array $values, array $config)`

- Nạp nhiều giá trị trước để giảm số query khi validate batch XML.

### `lookup($value, array $config)`

- Trả row/value tra cứu theo cấu hình source/column/condition.
- Có cache trong process để tránh query lặp.
- Tên bảng/cột chỉ được lấy từ nguồn đã allow-list.

## 24. `BhytLookupService`

File: `web_src/common/BhytLookupService.php`.

### `traCuuThongTin($maThe, $hoTen, $ngaySinh)`

- Mục đích: tra cứu thông tin BHYT cho hồ sơ.
- Có cache để tránh gọi lặp, gồm cache runtime và persistent cache trong `bhyt_response_cache`.
- Cache key được xây dựng từ dữ liệu đầu vào đã chuẩn hóa.
- Kết quả sau khi tra cứu được lọc trước khi lưu/đưa vào hồ sơ.

## 25. Worker XML

File: `scripts/watch_xml.php`.

Lệnh cơ bản:

```powershell
C:\xampp\php\php.exe scripts\watch_xml.php
C:\xampp\php\php.exe scripts\watch_xml.php --once
C:\xampp\php\php.exe scripts\watch_xml.php --worker-id=1
```

Các option được source hỗ trợ gồm `--once`, `--worker-id`, `--interval`, `--settle`, `--retention-days`, `--dir`.

Luồng worker:

```text
storage/xml/*.xml
  -> kiểm tra file đã ổn định
  -> claim sang processing/worker-N
  -> XmlFileImportService
  -> XmlFileDecoder
  -> XmlRulesValidator
  -> FilePeer
  -> move processed/failed
  -> audit/progress
```

Worker còn xử lý queue revalidation bằng `FilePeer::claimPendingRevalidation()` và có logic phục hồi file đã bị claim dở sau khi worker trước bị gián đoạn. Cleanup vùng `deleted` chạy theo retention.

## 26. Trạng thái hồ sơ và queue

Các trạng thái/vùng dữ liệu quan trọng:

| Trạng thái/vùng | Ý nghĩa |
| --- | --- |
| `xml_upload_queue` | Metadata file mới chờ worker |
| `processing/worker-N` | File đã được một worker claim |
| `processed` | Import/validation không có lỗi |
| `failed` | XML lỗi hoặc validation có error |
| `pending_revalidation` | Chờ quét lại rule |
| `revalidating` | Đã được process claim để quét lại |
| `archive` | Phiên bản file cũ khi re-import |
| `deleted` | File đã xóa logic, chờ cleanup |
| `portal` | Bản sao đã đẩy sang vùng cổng |

Queue revalidation sử dụng MySQL named lock `ql_error_revalidation_queue` để hạn chế web và worker claim trùng cùng hồ sơ.

## 27. Bảng dữ liệu chính

| Bảng | Vai trò |
| --- | --- |
| `file` | Hồ sơ XML hiện tại, trạng thái, validation result, file path |
| `xml_upload_queue` | Metadata file đang chờ xử lý |
| `file_history` | Lịch sử phiên bản khi re-import |
| `file_audit_logs` | Audit thao tác file |
| `cong_benhnhan` | Thông tin bệnh nhân dùng cho vùng cổng |
| `cong_hoso` | Lịch sử đẩy hồ sơ sang cổng |
| `xml_validation_rules` | Rule validation thực thi |
| `rule` | Danh mục loại rule |
| `api_validation_configs` | Cấu hình API bên ngoài |
| `table_lookup_sources` | Allow-list nguồn tra cứu DB |
| `bhyt_response_cache` | Cache tra cứu BHYT |
| `hethong` | Cấu hình bật/tắt chức năng hệ thống |
| `user` | Người dùng, token, quyền, khoa, trạng thái khóa |
| `nhomquyen` | Nhóm quyền |
| `log` | Log thao tác người dùng |

## 28. Điểm cần chú ý khi bảo trì

1. `fileAction`, worker và `XmlFileImportService` cùng tham gia luồng xử lý XML; sửa một tầng cần kiểm tra tác động đến các tầng còn lại.
2. Progress revalidation phải đọc được từ DB sau F5; không nên chuyển trạng thái tiến độ thành biến chỉ tồn tại ở browser/session.
3. Web và worker có thể xử lý revalidation đồng thời; không bỏ mutex/claim logic nếu chưa có cơ chế thay thế tương đương.
4. `pushPortal()` đang có side effect nhưng chưa kiểm tra CSRF ngay trong action.
5. Các action legacy như `user`, `nhomquyen`, `log`, `backup` chưa dùng CSRF đồng nhất như các module mới.
6. Mật khẩu vẫn có phần tương thích MD5 legacy.
7. Backup/restore là code cũ và có message extension không khớp implementation.
8. Với dynamic SQL lookup, phải giữ nguyên nguyên tắc allow-list identifier + prepared statement cho value.
9. Không lưu API token/BHYT credential trực tiếp vào log hoặc commit source.
10. Khi thêm chức năng mới, nên khai báo role rõ ràng, dùng CSRF cho thao tác ghi, validate input tại action và dùng prepared statement tại peer.

## 29. Ví dụ lần theo một chức năng từ UI đến dữ liệu

Ví dụ upload XML:

```text
www/file/index.php
  -> js/file.js
  -> /file/upload
  -> fileAction::upload()
  -> UploadQueueService::enqueue()
  -> xml_upload_queue + storage/xml
  -> scripts/watch_xml.php
  -> XmlFileImportService::importPath()
  -> XmlFileDecoder
  -> XmlRulesValidator
  -> FilePeer
  -> FileStorageService
  -> processed/failed + audit/progress
```

Ví dụ cấu hình rule API:

```text
/apiValidation/save
  -> apiValidationAction::save()
  -> ApiValidationConfigPeer::insert()
  -> api_validation_configs

/rules/save (rule_type=API)
  -> rulesAction::readRule()
  -> xml_validation_rules

worker/import/revalidate
  -> XmlRulesValidator
  -> ApiValidationService::call()
  -> API ngoài
  -> validation_result
```

Tài liệu tổng quan kiến trúc xem thêm `README.md`; tài liệu vận hành worker xem `XML_WATCHER.md`; bản đồ bảo trì source xem `MAINTENANCE.md`.

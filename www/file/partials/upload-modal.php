<div class="modal fade" id="modal-upload-xml" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form-upload-xml" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm file XML</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <?php if ($request->getAttribute('canChooseUploadKhoa')): ?>
                        <?php $uploadKhoaOptions = $request->getAttribute('uploadKhoaOptions'); ?>
                        <?php $currentUploadKhoa = (string) $request->getAttribute('currentUserKhoa'); ?>
                        <div class="form-group">
                            <label for="upload-id-khoa">Khoa nhập hồ sơ <span class="text-danger">*</span></label>
                            <select id="upload-id-khoa" class="form-control" required>
                                <option value="">-- Chọn khoa --</option>
                                <?php foreach (is_array($uploadKhoaOptions) ? $uploadKhoaOptions : array() as $khoa): ?>
                                    <option value="<?=htmlspecialchars((string) $khoa['id'], ENT_QUOTES, 'UTF-8')?>"
                                        <?=((string) $khoa['id'] === $currentUploadKhoa) ? ' selected' : ''?>>
                                        <?=htmlspecialchars($khoa['ten'], ENT_QUOTES, 'UTF-8')?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="xmlFile">Chọn file <span class="text-danger">*</span></label>
                        <div id="xml-drop-zone" class="xml-drop-zone" role="button" tabindex="0"
                             aria-label="Kéo thả hoặc chọn file XML để tải lên">
                            <i class="fa fa-cloud-upload xml-drop-zone-icon" aria-hidden="true"></i>
                            <strong>Kéo và thả một hoặc nhiều file XML vào đây</strong>
                            <span>hoặc bấm để chọn các file</span>
                            <span id="xml-drop-file-name" class="xml-drop-file-name"></span>
                            <input type="file" id="xmlFile" name="xmlFile"
                                   accept=".xml,text/xml,application/xml" multiple required>
                        </div>
                        <small class="form-text text-muted">Các file được tải lần lượt và đưa vào worker xử lý nền. Dung lượng tối đa mỗi file là 5 MB.</small>
                    </div>
                    <div id="upload-message" class="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" id="btn-upload-xml" class="btn btn-primary"><i class="fa fa-upload"></i> Tải lên</button>
                </div>
            </form>
        </div>
    </div>
</div>

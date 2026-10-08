(function ($) {
    'use strict';

    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('loai-kcb-text').attr('title', text).text(text).prop('outerHTML');
    }

    var table = $('#datatable-loai-kcb').DataTable({
        ajax: { url: $('#ULocal').val() + 'loai_kcb/getData/', type: 'POST' },
        paging: false,
        ordering: false,
        responsive: false,
        autoWidth: false,
        columns: [
            { data:'ma', width:'6%', className:'text-center' },
            { data:'truong_hop', width:'27%', render:compact },
            { data:'quy_dinh', width:'18%', defaultContent:'', render:compact },
            { data:'muc_huong', width:'17%', defaultContent:'', render:compact },
            { data:'ghi_chu', width:'16%', defaultContent:'', render:compact },
            { data:'ngay_hieu_luc', width:'8%', className:'text-center' },
            {
                data:'is_active', width:'8%', className:'text-center',
                render:function (value) {
                    return Number(value) === 1
                        ? '<i class="fa fa-check-circle text-success loai-kcb-status" title="Hiện hành"></i>'
                        : '<i class="fa fa-ban text-danger loai-kcb-status" title="Ngừng dùng"></i>';
                }
            }
        ],
        language: {
            search:'Tìm kiếm:', zeroRecords:'Không tìm thấy loại khám chữa bệnh',
            info:'Hiển thị _START_ đến _END_ trong _TOTAL_ mã', infoEmpty:'Không có dữ liệu'
        }
    });

    function search() { table.search($.trim($('#loai-kcb-search').val())).draw(); }
    $('#btn-search-loai-kcb').on('click', search);
    $('#btn-reset-loai-kcb').on('click', function () {
        $('#loai-kcb-search').val('').focus();
        table.search('').draw();
    });
    $('#loai-kcb-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            search();
        }
    });
})(jQuery);

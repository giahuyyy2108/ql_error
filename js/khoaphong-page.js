$(function () {
    var table = $('#datatable-khoaphong').DataTable({
        processing: true,
        serverSide: false,
        ajax: { url: $('#ULocal').val() + 'khoaphong/getData/', type: 'POST', data: function (data) { data.searchText = $('#khoaphong-search').val(); } },
        columns: [
            { data: 'id' },
            { data: 'ten' },
            { data: 'ten_khoi', defaultContent: '' }
        ]
    });
    $('#khoaphong-find').on('click', function () { table.ajax.reload(); });
});

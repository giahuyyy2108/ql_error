$(function () {
    var table = $('#datatable-khoi').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: $('#ULocal').val() + 'khoi/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#khoi-search').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'ten' }
        ]
    });

    $('#khoi-find').on('click', function () {
        table.ajax.reload();
    });
});

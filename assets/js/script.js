$(function(){
    // bagian new menu
    $('.modalTambahMenu').on('click', function(){
        $('#newMenuModalLabel').html('Add New Menu');
        $('.modal-footer button[type=submit]').html('Add');
        $('#menu').val('');
    });


    $('.modalUbahMenu').on('click', function(){
        $('#newMenuModalLabel').html('Edit Menu');
        $('.modal-body form').attr('action', 'http://localhost/ci-login/menu/edit');
        $('.modal-footer button[type=submit]').html('Edit');

        const id = $(this).data('id');

        $.ajax({
            url: 'http://localhost/ci-login/menu/editmenu',  
            data: {id : id},
            method: 'post',
            dataType: 'json',
            success: function(data) {
                $('#menu').val(data.menu);
                $('#id').val(data.id);
            }
        });
    });
});
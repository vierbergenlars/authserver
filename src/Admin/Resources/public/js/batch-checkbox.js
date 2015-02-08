$('.js--batch-checkbox').change(function() {
    if(this.checked) {
        $('.js--batch-form').collapse('show');
    } else if(!$('.js--batch-checkbox:checked').length) {
        $('.js--batch-form').collapse('hide');
    }
});

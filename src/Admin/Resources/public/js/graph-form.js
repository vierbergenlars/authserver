$(function() {
    $('.js--vizjs-load-graph').on('click', function() {
        var depth = $('#graph_depth').val();
        var direction = $('#graph_direction').val();
        var url = $(this).parents('form').attr('action');
        if(/\?/.test(url)) {
            url+='&depth='+depth+'&direction='+direction;
        } else {
            url+='?depth='+depth+'&direction='+direction;
        }
        $.get(url, function(data) {
            $('.js--vizjs-content').siblings().not('.panel-heading').addClass('hidden');
            $('.js--vizjs-content').removeClass('hidden');
            $('.js--vizjs-target').html(Viz(data, {format: 'svg', engine:'fdp'})).find('svg').attr('class','img-responsive');
        });
    });

    $('.js--vizjs-close-graph').on('click', function() {
        $('.js--vizjs-content').addClass('hidden').siblings().removeClass('hidden');
    });
});

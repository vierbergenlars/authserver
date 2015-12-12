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
            var $content = $('.js--vizjs-content');
            $content.siblings().not('.panel-heading').addClass('hidden');
            $content.removeClass('hidden');
            var imgData = Viz(data, {format: 'svg', engine:'fdp'});
            if(window.Blob&&window.URL.createObjectURL) {
                var imgBlob = new Blob([imgData], {type: 'image/svg+xml'});
                var imgUrl = URL.createObjectURL(imgBlob);
                $content.find('.js--vizjs-download-graph-svg')
                    .removeClass('hidden')
                    .attr('href', imgUrl);
            } else {
                $content.find('.js--vizjs-download-graph-svg').remove();
            }
            var pngImage = Viz.svgXmlToPngImageElement(imgData);
            pngImage.onload = function() {
                $content.find('.js--vizjs-download-graph-png')
                    .removeClass('hidden')
                    .attr('href', pngImage.src);
            }
            $('.js--vizjs-target').html(imgData).find('svg').attr('class','img-responsive');
        });
    });

    $('.js--vizjs-close-graph').on('click', function() {
        $('.js--vizjs-content').addClass('hidden').siblings().removeClass('hidden');
    });
});

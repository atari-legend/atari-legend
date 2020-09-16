dynamicResize = function(bound) {

    var monitor_width, monitor_height,
        margin_x, margin_y,
        padding_x, padding_y,
        canvas_width, canvas_height,
        body_width  = $('body').width(),
        body_height = $('body').height();
  
    if ((body_width / body_height) < 1.4) {
        monitor_width    = body_width;
        monitor_height   = Math.floor(monitor_width * 789 / 1104);
        margin_x = 0;
        margin_y = Math.floor((body_height - monitor_height) / 2);
    } else {
        monitor_height   = body_height;
        monitor_width    = Math.floor(monitor_height * 1104 / 789);
        margin_x = Math.floor((body_width - monitor_width) / 2);
        margin_y = 0;
    }

    $('#monitor').show().width(monitor_width).height(monitor_height)
        .css('margin', margin_y + 'px ' + margin_x + 'px');

    padding_x = Math.floor(120 * monitor_width / 1104);
    padding_y = Math.floor(106 * monitor_height / 789);
    canvas_width      = monitor_width  - (2 * padding_x);
    canvas_height     = monitor_height - (2 * padding_y);

    $('#main').width(canvas_width).height(canvas_height)
        .css('margin', (margin_y + padding_y) + 'px ' + (margin_x + padding_x) + 'px');

    padding_x = Math.floor(24 * monitor_width / 1104);
    padding_y = Math.floor(24 * monitor_height / 789);
    canvas_width  -= 2 * Math.floor(padding_x );
    canvas_height -= 2 * Math.floor(padding_y );

    $('#main canvas').width(canvas_width).height(canvas_height)
        .css('margin', padding_y + 'px ' + padding_x + 'px');

}

// When all assets such as images have been completely received, runs the demo
$(window).load(function() {
    init();
    dynamicResize(true);
});

// When document is ready
$(document).ready(function() {
    $(window).resize(function(){
        dynamicResize(true);
    });
});

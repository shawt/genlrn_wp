// Barts html5 starter kit
(function($) {
//------------------------On Dom Ready
$(document).ready(function(){
    
    //readMore
    $('#readMoreButton').click(function(){
        $('.shortContent').hide();
       $('.allContent').slideToggle(1000); 
    });
    $('#readLessButton').click(function(){
        $('.allContent').slideToggle(1000);
       $('.shortContent').show(); 
    });
    
 // Scroll the whole document
    scroll_if_anchor(window.location.hash);
$('a[href^="#"]').pageScroll({
offset : 100
});

    //external links
    $('a').each(function() {
   var a = new RegExp('/' + window.location.host + '/');
   if (!a.test(this.href)) {
       $(this).attr("target","_blank");
   }
});
    //set initial offset if mobile
    topOffset();

    //setbg
    setBg();

    //switchImg
    switchImg();

    //navigation toggle
    $('#menuToggle').click(function(){
            $('#mainNav').fadeToggle(300);
        });

if($(window).width()>=800 && $('body.home').length != 0){
    //if 800px or larger
    //sticky
	if($('div.sticky.full')){
      var sticky = new Waypoint.Sticky({
        element: $('.sticky')[0]
          //,
       // element: $('.group.blue')[0],
       // element: $('.col.blue')[0]
		})
      };

    //logo flyin
    $('.home #afterLogo').waypoint(function(direction) {
        if (direction === 'down') {
            $('#logo').removeClass('fadeInUp').addClass('fadeOutDown')//.on('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
            $('#mainNav #logoHolder a').removeClass('fadeOutUp').addClass('fadeInDown');
            //});


        }
        else {
            $('#mainNav #logoHolder a').removeClass('fadeInDown').addClass('fadeOutUp');
          $('#logo').removeClass('fadeOutDown').addClass('fadeInUp')

        }
     });
}else{
    //if smaller then 800px
}

});

//------------------------On Resize
$(window).smartresize(function(){
    topOffset();
    setBg();
    switchImg();
});

$(window).load(function() {
    $('.marquee').marquee({
    //speed in milliseconds of the marquee
    duration: 15000,
    //gap in pixels between the tickers

    //time in milliseconds before the marquee will start animating
    delayBeforeStart: 0,
    //'left' or 'right'
    direction: 'left',
    //true or false - should the marquee be duplicated to show an effect of continues flow
    duplicated: true
});
});

//------------------------Functions
function topOffset() {
    if($(window).width()<800){
        var offset = $('header .top').height();
        $('header .middle').css('margin-top',offset);
    }else{

        $('header .middle').css('margin-top','0px');
    }

}
function setBg() {
    //set backgrounds


    var image = '';
    if($(window).width()>=800){
        $('[data-background]').each(function(index, element) {
            var image = $(this).data('background');
            var modifiedString = image.replace(/,\s*$/, '');
            var arrayF = modifiedString.split(',');

            $(this).backstretch(arrayF, {duration: 4500, fade: 750});
        });
    }else{
        $('[data-background]').each(function(index, element) {
            var image = ''
            $(this).backstretch("destroy")
        });
    }


}
function switchImg() {
    //switch images
    if($(window).width()<800){

        $('[data-alternative]').each(function(index, element) {
            var oldimage = $(this).attr('src');
            var newimage = $(this).data('alternative');
            $(this).attr('src',newimage).addClass('changedImg').data('alternative',oldimage);
        });
    }else{
        $('.changedImg').each(function(index, element) {
            var oldimage = $(this).attr('src');
            var newimage = $(this).data('alternative');
            $(this).attr('src',newimage).removeClass('changedImg').data('alternative',oldimage);
        });
    }
}
    function scroll_if_anchor(href) {
    href = typeof(href) == "string" ? href : $(this).attr("href");

    // If href missing, ignore
    if(!href) return;

    // You could easily calculate this dynamically if you prefer
    var fromTop = 120;

    // If our Href points to a valid, non-empty anchor, and is on the same page (e.g. #foo)
    // Legacy jQuery and IE7 may have issues: http://stackoverflow.com/q/1593174
    var $target = $(href);

    // Older browsers without pushState might flicker here, as they momentarily
    // jump to the wrong position (IE < 10)
    if($target.length) {
        $('html, body').animate({ scrollTop: $target.offset().top - fromTop });
        if(history && "pushState" in history) {
            history.pushState({}, document.title, window.location.pathname + href);
            return false;
        }
    }
}

// When our page loads, check to see if it contains and anchor

})(jQuery);

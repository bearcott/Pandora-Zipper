$(function() {
    $('.station').click(function(){
        $this = $(this);
        if (!confirm('Are you sure? Once downloaded, Pandora will not play music for about 15~ minutes as all 6 of your skips will be used to download 28 songs.')) return;
        if ($this.hasClass('deactivated')) return;
        window.console.log('began downloading');
        $('.loader > *').stop().fadeOut(500).removeClass('error').delay(500).parent().find('.spin').fadeIn().attr('id','get');
		$("html, body").stop().animate({scrollTop : 0},  600);
        $('.station').addClass('deactivated');
        var tok = $this.find('input[name="token"]').val();
        var nam = $this.find('input[name="name"]').val();
        //thx this guy: http://www.dave-bond.com/blog/2010/01/JQuery-ajax-progress-HMTL5/
        //for providing this progress event listener method!
        $('.loader').slideDown();
        $.ajax({
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = e.loaded / e.total * 100;
                        $('.spin').fadeOut(function() { $('.progress').fadeIn() });
                        $('.fluid').fadeIn().css('width',percentComplete + "%");
                        window.console.log('loaded ' + percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            type : 'POST',
            url : 'request.php',
            data: {
                'token' : tok,
                'name' : nam,
                'what' : 'get'
            }
        }).always(function() {

        }).done(function(d) {
            $('.loader > *').stop().fadeOut(500);
            zipsongs(tok, nam);
        }).fail(function(d) {
            $('.loader').addClass('error');
            setTimeout(function() {
                $('.loader').removeClass('error');
            },2000)
            $('.loader > *').stop().fadeOut(500).delay(500).parent().find('.fail').fadeIn();
            window.console.log("either out of skips or server error.. heh we really didn't make a difference")
        });
        function zipsongs(tok, nam){
            window.console.log('began zipping');
            $this = $(this);
            if ($this.hasClass('deactivated')) return;
            $('.loader > *').stop().fadeOut(500).removeClass('error').delay(500).parent().find('.spin').fadeIn().attr('id','zip');
            $("html, body").stop().animate({scrollTop : 0},  600);
            $('.station').addClass('deactivated');
            //thx this guy: http://www.dave-bond.com/blog/2010/01/JQuery-ajax-progress-HMTL5/
            //for providing this progress event listener method!
            $('.loader').slideDown();
            $.ajax({
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.addEventListener("progress", function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = e.loaded / e.total * 100;
                            $('.spin').fadeOut(function() { $('.progress').fadeIn() });
                            $('.fluid').fadeIn().css('width',percentComplete + "%");
                            window.console.log('loaded ' + percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                type : 'POST',
                url : 'request.php',
                data: {
                    'token' : tok,
                    'name' : nam,
                    'what' : 'zip'
                }
            }).always(function() {
                $('.station').removeClass('deactivated');
            }).done(function(d) {
                $('.loader > *').stop().fadeOut(500).delay(500).parent().find('.done').fadeIn(500);
                window.location.replace('download.php');
            }).fail(function(d) {
                $('.loader').addClass('error');
                setTimeout(function() {
                    $('.loader').removeClass('error');
                },2000)
                $('.loader > *').stop().fadeOut(500).delay(500).parent().find('.fail').fadeIn();
                window.console.log("either out of skips or server error.. heh we really didn't set a difference between the two.")
            });
        }
    });
});

$('.load-more').on('click', function(event) {

    // get target path
    var target = $(this).attr("data-target");
    var value = $(this).attr("data-value");

    if(!target) {
        alert('Error: target attribute not defined. Please define target attribute.');
        return false;
    }

    var route = $("#"+target).attr('data-route');

    if(!route) {
        alert('Error: route not defined.');
        return false;
    }
    $.ajax({
        type: "POST",
        data : {
            'v':value
        },
        url : route,
        beforeSend: function() {
           $(".backDrop").fadeIn( 100, "linear" );
           $(".loader").fadeIn( 100, "linear" );
        },
        success:function(data, textStatus, jqXHR)
        {
            if ( data != '' )
            {
                var obj = jQuery.parseJSON( data );
                if ( obj.status == 1)
                {
                    if (obj.data != '')
                    {
                        $('.load-more').attr("data-value", obj.val);
                        $("#"+target).find(".load-more").parent().before(obj.data);
                    }
                } else {
                  alert( obj.message );
                }
            }

          setTimeout(function () {
            $(".backDrop").fadeOut( 100, "linear" );
            $(".loader").fadeOut( 100, "linear" );
          }, 80);

        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            $(".backDrop").fadeOut( 100, "linear" );
            $(".loader").fadeOut( 100, "linear" );
            alert('You have ' + errorThrown + ' request cannot processing..');
        }
    });
    return false;
    event.preventDefault(); //STOP default action
    event.unbind(); //unbind. to stop multiple form submit. 
});

function toggleDetail(classNameOrId) {
    if ($("." + classNameOrId).hasClass('hidden')) {
        $("." + classNameOrId).fadeIn().removeClass('hidden');
    } else {
        $("." + classNameOrId).fadeOut('slow', function(){
            $("." + classNameOrId).addClass('hidden');
        });
    }
}

function reportPrint(id, cssPath) {
    data = $('#'+id).html();
    var linkCss = cssPath.split('|');
    if(linkCss.length < 1) {
        alert('Error: Print css not found. please define css');
    } else {
        var css = '';
        for(i=0; i < linkCss.length ; i++) {
            css +='<link href="'+linkCss[i]+'" media="all" rel="stylesheet" type="text/css">';
        }
    }
    var print = '<html><head>'+css+'<style>table { clear: both;font-size:12px; } body {background: #fff !important;} table tr th.print_hide, table tr td.print_hide { display: none; }</style></head><body>'+data+'</body></html>'
    //alert(print); return false;
    var newwindow = window.open('', 'Report', 'width=600,height=600');
    newwindow.document.write(print);
    setTimeout(function() {
        //your code to be executed after 1 second
    }, '100');
    //newwindow.document.close();
    newwindow.focus();
    newwindow.print();
    //newwindow.close();
    return false;
}


$('body').on('click', 'a.dEdit', function(event) { //due to ajax data request with datatable grids

        //path of the route
        var route = $(this).data("route");
        var name = $(this).data("title");
        var setting = $(this).data("setting");

        if (!$(this).data("route")) {
            alert('Error: route attribute not defined. Please define route attribute.');
            return false;
        }

        $.ajax({
            type: "GET",
            url : route,
            async : true,
            beforeSend: function()
            {
                $(".backDrop").fadeIn( 100, "linear" );
                $(".loader").fadeIn( 100, "linear" );
                $('#dynamicEdit').modal('show');
                $("#dynamicEdit").find("#formTitle").html(name);
                if(typeof setting !== "undefined") {
                    $("#dynamicEdit").find('.modal-dialog').addClass(setting);
                }
                setTimeout(function () {
                    //$('#loader').html() defined in layout before editing model
                    $("#dynamicEdit").find("#dataResult").html($('#loader').html());
                } , 10);
            },
            success:function(data, textStatus, jqXHR)
            {
                setTimeout(function () {
                    $(".backDrop").fadeOut( 100, "linear" );
                    $(".loader").fadeOut( 100, "linear" );
                }, 80);
                $("#dynamicEdit").find("#dataResult").html(data);
            },
            error: function(jqXHR, textStatus, errorThrown)
            {
                $('#dynamicEdit').modal('hide');
                alert('You have '+ thrownError +', so request cannot processing..'); //alert with HTTP error
            }
        });
    return false;
    event.preventDefault(); //STOP default action
    event.unbind(); //unbind. to stop multiple form submit.
}).ajaxComplete(function () {

    /*$('select').addClass('chosen');
    $('.chosen').chosen({
        disable_search_threshold: 15
    });*/

}).on('click', 'a.toggle-status', function(event) {

    var $this = $(this);
    //path of the route
    var route = $(this).data("route");
    var realod = $(this).data("realod");

    //alert(realod);
    //confirm message
    var message = $(this).data("message");
    var src = $(this).children('img').attr('src');

    var ll = src.length;
    var path = src.substring(-0, Number(ll) - 5);

    if (!$(this).data("route")) {
        alert('Error: route attribute not defined. Please define data-route attribute.');
        return false;
    }

    if (!$(this).data("message")) {
        alert('Error: message attribute not defined. Please define data-message attribute.');
        return false;
    }

    $(".backDrop").fadeIn( 100, "linear" );
    $(".loader").fadeIn( 100, "linear" );

    $.get(route, function( data ) {

        var obj = jQuery.parseJSON( data );
        if ( obj.status == 1)
        {
            var imgsrc = obj.data;
            $this.html("<img src='" + path + imgsrc + "' />");

            if (realod == 1) {
                window.location.reload();
            }

            else if(realod.length > 5){
                window.location.href = realod;
            }
            else{
                window.location.reload();
            }

        } else {
            alert('Internal server error.');
            window.location.reload();
        }
    });
    setTimeout(function () {
        $(".backDrop").fadeOut( 100, "linear" );
        $(".loader").fadeOut( 100, "linear" );
    }, 280);

}).on('click', 'a.__drop', function(event) {

    var $this = $(this);
    //path of the route
    var route = $(this).data("route");
    var redirectPath = $(this).data("redirect");

    //confirm message
    var message = $(this).data("message");

    if (!$(this).data("route")) {
        alert('Error: route attribute not defined. Please define data-route attribute.');
        return false;
    }

    redirect = 0;
    if ($(this).data("redirect")) {
        redirect = 1;
    }


    if (!$(this).data("message")) {
        alert('Error: message attribute not defined. Please define data-message attribute.');
        return false;
    }

    if(confirm(message)) {
        $.get(route, function( data ) {
            var obj = jQuery.parseJSON(data);
            if(obj.status == 1) {
                alert(obj.message);
                if (redirect == 1) {
                    window.location = redirectPath;
                    return false;
                }
                window.location.reload();
            } else {
               alert(obj.message);
            }
        });
    }

}).on('click', '.check-all', function(event) {

    var checkAll = $(this);
    $('.table .check-one').each(function(){

        if(checkAll.val() == '1')
            this.checked = false;
        else
            this.checked = true;

    });

    if(checkAll.val() == '1')
        checkAll.val('0');
    else
        checkAll.val('1');
}).on('click', '._back', function(event) {
    history.back(1);
}).on('click', '._confirm', function(event) {
    //confirm message
    var message = $(this).data("message");
    $.messager.confirm('Success', message, function(isConfirm) {
        if(!isConfirm) {
            return false;
        }
    });
}).on('click', '._modify', function(event) {
    $(".backDrop").fadeIn( 100, "linear" );
    $(".loader").fadeIn( 100, "linear" );
    
    $('.panel-edit').removeClass('hidden');
    $('.panel-view').addClass('hidden');
    
    $(".backDrop").fadeOut( 100, "linear" );
    $(".loader").fadeOut( 100, "linear" );
}).on('click', '._cancel', function(event) {
    $(".backDrop").fadeIn( 100, "linear" );
    $(".loader").fadeIn( 100, "linear" );

    $('.panel-view').removeClass('hidden');
    $('.panel-edit').addClass('hidden');

    $(".backDrop").fadeOut( 100, "linear" );
    $(".loader").fadeOut( 100, "linear" );
});

//always first input be focus.
$('form:first *:input[type!=hidden]:first').focus();
$('select.ajaxChange').on('change', function(event)
{
    var val = $(this).val();
    //work with both the diffrenace is matter performance and speed
    // first one is fastest and very reliable as compare to second one
    // for more info read-out this artical @link

    var target = $(this).data("target"); //$(this).attr("data-target");
    var route = $(this).data("route");   //$(this).attr("data-route");
    var isVal = $(this).data("value");   //$(this).attr("data-route");

    if (val != '') {

        if (!$(this).data("target")) {
            alert('Error: target attribute not defined. Please define target attribute.');
            return false;
        }

        if (!$(this).data("route")) {
            alert('Error: route attribute not defined. Please define route attribute.');
            return false;
        }

        $.ajax({
            type: "GET",
            url : route + '/' + val,
            beforeSend: function() {
                //$(".backDrop").fadeIn( 100, "linear" );
                //$(".loader").fadeIn( 100, "linear" );
            },
            success:function(data, textStatus, jqXHR) {
                if (isVal == 1) {
                    $("#" + target).val(data);
                } else {
                    $("#" + target).html(data);
                }
                $(".select2").trigger('select2:updated');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('You have '+errorThrown+' request cannot processing..');
            }
        });
        return false;
        event.preventDefault(); //STOP default action
        event.unbind(); //unbind. to stop multiple form submit.
    }
}).ajaxComplete(function () {

    $('select').addClass('select2');
    $(".select2").select2({
        allowClear: true
    });

});

$(function() {

    // lazy load code start
    var loading = false;
    $(window).scroll(function() {

        if ($(window).scrollTop() == $(document).height() - $(window).height()) {

            if(loading == false) {

                // enable the lazy load get dynamic data & render
                var length = $("#lazy-load").length;

                if (length === 1) {

                    // get route path
                    var route = $("#lazy-load").attr("data-route");
                    var value = $("#lazy-load").attr("data-value");
                    var end = $("#lazy-load").attr("data-end");

                    if(!route) {
                        alert('Error: route attribute not defined. Please define data-route attribute.');
                        return false;
                    }
                    if(!value) {
                        alert('Error: value attribute not defined. Please define data-value attribute.');
                        return false;
                    }
                    if(!end) {
                        alert('Error: end attribute not defined. Please define data-end attribute.');
                        return false;
                    }

                    if ( end == 0 ) {

                        $(".backDrop").fadeIn( 100, "linear" );
                        $(".loader").fadeIn( 100, "linear" );

                        loading = true; //prevent further ajax loading

                        //load data from the server using a HTTP POST request
                        $.post(route, {'v': value }, function(data) {

                            if ( data != '' )
                            {
                                var obj = jQuery.parseJSON( data );
                                if ( obj.status == 1)
                                {
                                    $('#lazy-load').attr("data-end", obj.end);
                                    if (obj.data != '')
                                    {
                                        $('#lazy-load').attr("data-value", obj.val);
                                        $("#lazy-load tbody").append(obj.data);
                                    }
                                } else {
                                  alert( obj.message );
                                }
                            }

                            setTimeout(function (){
                                $(".backDrop").fadeOut( 100, "linear" );
                                $(".loader").fadeOut( 100, "linear" );
                            }, 80);

                            loading = false;

                        }).fail(function(xhr, ajaxOptions, thrownError) { //any errors?

                            $(".backDrop").fadeOut( 100, "linear" );
                            $(".loader").fadeOut( 100, "linear" );
                            alert('You have '+ thrownError +', so request cannot processing..'); //alert with HTTP error
                            loading = false;

                        });
                    }
                }
            }
        }
    })
    // lazy load code end.

    //Datemask dd-mm-yyyy
    $(".date-mask").inputmask("dd-mm-yyyy");
    //Mobile-mask
    $(".mobile-mask").inputmask("(999)-999-9999", {"placeholder": "(999)-999-9999"});
    //Input-mask
    $("[data-mask]").inputmask();
    // dateticker
    $(".date-picker").datepicker({
        showButtonPanel: false,
        dateFormat: "dd-mm-yy",
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        changeMonth: true,
        changeYear: true,
        //showMonthAfterYear: true,
        defaultDate: new Date()
    });

    $(".date-future").datepicker({
        showButtonPanel: false,
        dateFormat: "dd-mm-yy",
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        changeMonth: true,
        changeYear: true,
        showMonthAfterYear: true,
        minDate: +1
    });

    $(".date-past").datepicker({
        showButtonPanel: false,
        dateFormat: "dd-mm-yy",
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        changeMonth: true,
        changeYear: true,
        showMonthAfterYear: true,
        defaultDate: new Date(1985, 00, 01),
        maxDate: -1
    });


    //$(".select2").select2("destroy");
    // select2
    $(".select2").select2({
        placeholder: "Select Option",
        allowClear: true
    });
    $(".country").select2({
        tags: false,
        allowClear: false
    });

    $('.fSelect').fSelect({
        placeholder: '-Select-',
        numDisplayed: 10,
        overflowText: '{n} selected',
        searchText: 'Search',
        showSearch: true
    });

    $(".select2-ajax").each(function(){
        $(this).select2({
            ajax: {
                url: $(this).data('select2-ajax'),
                dataType: 'json',
                delay: 100,
                data: function (params) {
                    return {
                        keyword: params.term // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            //placeholder: "Please enter one charater",
            minimumInputLength: 1,
            containerCssClass: 'removeIt',
        });
    });

    



    var length = $("#scrollIt").length;
    if (length === 1) {
        $('html, body').animate({
            scrollTop: $('#scrollIt').offset().top
        }, 'slow');
    }

    $(".focusIt").focus();

    //$('.model').modal();

    /**
     * Function is used to appear confirm dialog box.
     */
    function confirmation(message)
    {
        $.messager.confirm('Confirm', message, function(isConfirm) {
            if(isConfirm) {

            }
        });
    }

    /**
     * Function is used to appear alert dialog box.
     */
    function verify(heading, message, priority)
    {
        alert(message); return false;
        $.messager.alert(heading, message, priority);
    }

    //post data for sorting data..
    function sortData(route, data) {
        $.post(route, data, function(response) {
            if(response == 1) {
                $('.sortalert').fadeIn( 250, "linear" );
            }
            setTimeout(function () {
                $('.sortalert').fadeOut( 250, "linear" );
            }, 4000);
        });
    }

    // submit form with ajax
    function submitForm(form, pageNumber, perPage, liveSearch, sortAction, sortEntity) {

        //var token = $('meta[name="_token"]').attr('content');
        var postData = $(form).serializeArray();
        var formMethod = $(form).attr("method");
        var formUrl = $(form).attr("action");
        var token = $('meta[name="csrf-token"]').attr('content');
        /*alert(" FFFF " + $('#page-no').val());
        if (typeof $('#page-no').val() !== "undefined") {
            pageNumber = $('#page-no').val();
        }*/
        postData.push(
            { name: 'page', value: pageNumber },
            { name: 'perpage', value: perPage },
            { name: '_token', value: token },
            { name: 'keyword', value: liveSearch },
            { name: 'sort_action', value: sortAction },
            { name: 'sort_entity', value: sortEntity }
        );

        $.ajax(
        {

            url : formUrl,
            type: formMethod,
            data : postData,
            beforeSend: function() {
                $(".backDrop").fadeIn( 100, "linear" );
                $(".loader").fadeIn( 100, "linear" );
            },
            success:function(data, textStatus, jqXHR)
            {
                if ( data != '' )
                {
                    $("#paginate-load").html(data);
                    // for sort records
                    if(sorting)
                    {
                        // refresh sorting here.
                        $( "table tbody" ).sortable({ update: function() {
                            var order = $(this).sortable("serialize") + '&update=update';
                                sortData(sorting, order);
                            }
                        });
                    }
                }
                setTimeout(function (){
                    $(".backDrop").fadeOut( 100, "linear" );
                    $(".loader").fadeOut( 100, "linear" );
                }, 80);
            },
            error: function(jqXHR, textStatus, thrownError)
            {
                $(".backDrop").fadeOut( 100, "linear" );
                $(".loader").fadeOut( 100, "linear" );
                alert('You have '+ thrownError +', so request cannot processing..'); //alert with HTTP error
            }
        });
        return false;
    }

    var ajaxFilter = $("#ajaxForm").length;
    //Filter form onsubmit function
    $("#ajaxForm").submit(function() {
        submitForm("#ajaxForm", "", "", "");
        return false;
    });

    // enable the paginate load get dynamic data & render
    var length = $("#paginate-load").length;
    if (length === 1) {

        // get route path
        var route = $("#paginate-load").attr("data-route");
        var sorting = $("#paginate-load").attr("data-sorting");

        if(!route) {
            alert('Error: route attribute not defined. Please define data-route attribute.');
            return false;
        }

        //ajax pagination code start
        function loadData(page, perpage, liveSearch, sortAction, sortEntity)
        {
            if (typeof sortAction === "undefined") {
                sortAction = '';
            }

            if (typeof sortEntity === "undefined") {
                sortEntity = '';
            }

            if (typeof $('#page-no').val() !== "undefined") {
                page = $('#page-no').val();
            }

            var token = $('meta[name="csrf-token"]').attr('content');
            $.ajax
            ({
                type: "POST",
                data : {'_token' : token, 'page': page, 'perpage': perpage, 'keyword': liveSearch, 'sort_action': sortAction, 'sort_entity': sortEntity},
                url: route,
                beforeSend: function() {
                    $(".backDrop").fadeIn( 100, "linear" );
                    $(".loader").fadeIn( 100, "linear" );
                },
                success: function(data)
                {
                    if ( data != '' ) {
                        $("#paginate-load").html(data);
                        //for sort news
                        if(sorting) {
                            // refresh sorting here.
                            $( "table tbody" ).sortable({ update: function() {
                                var order = $(this).sortable("serialize") + '&update=update';
                                    sortData(sorting, order);
                                }
                            });
                        }
                    }
                    setTimeout(function (){
                        $(".backDrop").fadeOut( 100, "linear" );
                        $(".loader").fadeOut( 100, "linear" );
                    }, 40);
                },
                error: function(jqXHR, textStatus, thrownError) {
                    $(".backDrop").fadeOut( 100, "linear" );
                    $(".loader").fadeOut( 100, "linear" );
                    alert('You have '+ thrownError +', so request cannot processing..'); //alert with HTTP error
                }
            });
        }

        loadData(1, '', '');  // For first time page load default results
        $('body').on('click', '.pagination li._paginate', function() {
            var page = $(this).attr('p');
            var livSearch = $('.live-search').val();
            var perPage = parseInt($('#per-page').val());
            var sortAction = $('#sort_action').val();
            var sortEntity = $('#sort_entity').val();

            if (ajaxFilter === 1) {
                submitForm("#ajaxForm", page, perPage, livSearch, sortAction, sortEntity);
            } else {
                loadData(page, perPage, livSearch, sortAction, sortEntity);
            }
        });

        $('body').on('keyup', 'input.live-search', function (event) {
            var livSearch = $(this).val();
            var perPage = parseInt($('#per-page').val());
            if (livSearch.length % 2 == 0) {
                if (ajaxFilter === 1) {
                    submitForm("#ajaxForm", 1, perPage, livSearch);
                } else {
                    loadData(1, perPage, livSearch);
                }
            }
        });

        $('body').on('click', '.sort', function() {
            var sortAction = $(this).data('sort-action');
            var sortEntity = $(this).data('sort-entity');
            var livSearch = $('.live-search').val();
            var perPage = parseInt($('#per-page').val());

            if (ajaxFilter === 1) {
                submitForm("#ajaxForm", 1, perPage, livSearch, sortAction, sortEntity);
            } else {
                loadData(1, perPage, livSearch, sortAction, sortEntity);
            }
        });

        $('body').on('change', '#per-page', function() {
            var livSearch = $('.live-search').val();
            var perPage = parseInt($(this).val());
            var sortAction = $('#sort_action').val();
            var sortEntity = $('#sort_entity').val();

            if (ajaxFilter === 1) {
                submitForm("#ajaxForm", 1, perPage, livSearch, sortAction, sortEntity);
            } else {
                loadData(1, perPage, livSearch, sortAction, sortEntity);
            }
        });

        $('body').on('click', '#go_btn', function() {
            var page = parseInt($('.goto').val());
            var livSearch = $('.live-search').val();
            var perPage = parseInt($('#per-page').val());
            var noOfPages = parseInt($('._total').html());
            var sortAction = $('#sort_action').val();
            var sortEntity = $('#sort_entity').val();

            if (page != 0 && page <= noOfPages) {

                if (ajaxFilter === 1) {
                    submitForm("#ajaxForm", page, perPage, livSearch, sortAction, sortEntity);
                } else {
                    loadData(page, perPage, livSearch, sortAction, sortEntity);
                }

            } else {
                alert('Enter a page between 1 and ' + noOfPages);
                $('.goto').val("").focus();
                return false;
            }
        });
    }

    /*$(".dropzone").dropzone({
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize: 500.0, // MB
        addRemoveLinks: true
    });*/

    var options = {
        beforeSubmit:  showRequest,  // pre-submit callback
        success:       showResponse  // post-submit callback
    };
    // bind form using 'ajaxForm'
    $('#ajaxSave,.ajaxSaveAll').ajaxForm(options);

    // pre-submit callback
    function showRequest(formData, jqForm, options) {
        //var queryString = $.param(formData);
        $(".backDrop").fadeIn(100, "linear");
        $(".loader").fadeIn(100, "linear");
        $('.error-messages').parent().parent().addClass('hidden');
        $('.single-response').parent().addClass('hidden');
        return true;
    }

    // post-submit callback
    function showResponse(data, statusText, xhr, $form)
    {
        $(".alert").css('display', 'block');
        var errors = '';
        if (data.status == 206) {
            $.each(data.errors, function (i, value) {
                errors +='<li>' + value + ' </li>';
            });
            $('.error-messages').parent().parent().removeClass('hidden');
            $('.error-messages').html(errors);
            /*$(".alert").delay(2000).slideUp(500, function() {
                $(this).fadeOut(100, "linear").addClass('hidden');
            });*/
        } else if(data.status == 207) {
            $('.single-response').parent().removeClass('hidden').addClass('alert-danger');
            $('.single-response').html(data.message);
            //$.messager.alert('Error', data.message, 'error');
            /*$(".alert").delay(2000).slideUp(500, function() {
                $(this).fadeOut(100, "linear").addClass('hidden');
            });*/
        } else if(data.status == 201) {
            $('.single-response').parent().removeClass('hidden').addClass('alert-success');
            $('.single-response').html(data.message);
            /*$.messager.confirm('Success', data.message, function(isConfirm) {
                if(isConfirm) {

                }
                if(data.hasOwnProperty('url')) {
                    window.location = data.url;
                }
            });*/
        }
        $(".backDrop").fadeOut(100, "linear");
        $(".loader").fadeOut(100, "linear");
        setTimeout(function () {
            if(data.hasOwnProperty('url')) {
                window.location = data.url;
            }
        }, 1000);
    }
});

$('.date-picker').datepicker({ dateFormat: 'dd-mm-yy' });
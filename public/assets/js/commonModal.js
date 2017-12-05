// Submit modal form with ajax
function submitModalForm(modalForm) {
    var postData = $(modalForm).serializeArray();
    var formMethod = $(modalForm).attr("method");
    var formUrl = $(modalForm).attr("action");
    var dropDownID = $(modalForm).attr("data-dropdown");
    var token = $('meta[name="csrf-token"]').attr('content');
    postData.push(
        { name: '_token', value: token }
    );
    $.ajax(
        {
            url : formUrl,
            type: formMethod,
            data : postData,
            success:function(data, textStatus, jqXHR)
            {
                errorList = '';
                if ( data.success == false )
                {
                    errorList = '<div class="alert alert-danger"><ul>';
                    for(x in data.errors){
                        errorList += '<li>' + data.errors[x] +'</li>';
                    }
                    errorList += '</ul>';
                    $(".errorList").html(errorList).show().delay(5000).fadeOut();
                }
                if ( data.success == true ){
                    alert(data.message);
                    $("[data-dismiss=modal]").trigger({ type: "click" });
                    var selectedOption = '';
                    if(data.data != ''){
                        selectedOption = '<option ';
                        selectedOption += 'value = "' + data.data.id + '" selected="selected" >';
                        selectedOption +=  data.data.name;
                        selectedOption += '</option>';
                    }
                    $(".select2#"+dropDownID).append(selectedOption);
                    $(".select2#"+dropDownID).select2().select2(data.data.id,data.data.name);
                }
            },
            error: function(jqXHR, textStatus, thrownError)
            {
                alert('You have '+ thrownError +', so request cannot processing..'); //alert with HTTP error
            }
        });
    return false;
}
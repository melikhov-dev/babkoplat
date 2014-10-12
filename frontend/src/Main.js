//Alerts
$('.alert-help-message').bind('closed.bs.alert', function () {
    $.ajax({
        type: "POST",
        url: '/message/help/hide',
        data: { messageId : $(this).attr('x-message-id'),
                scope: $(this).attr('x-scope') }
    });

});

//Comments
$('.hiddenCommentsTooltip').tooltip();


$('.comment_add').on('click', function(e) {
    var commentId = $(this).data('commentId');
    var addArea = $('[data-comment-id=' + commentId + '].addArea');
    addArea.show();
});

$('.comment_addCancel').click(function(e){
    var commentId = $(this).data('commentId');
    var addArea = $('[data-comment-id=' + commentId + '].addArea');
    addArea.hide();
    e.stopPropagation();
    e.preventDefault();
});

$('.comment_edit').click(function(e){
    var commentId = $(this).data('commentId');
    $('button[data-comment-id= '+ commentId +']').hide();
    var contractId = $('.contractId').data('contractId');
    var textContainer = $('.commentText[data-comment-id= '+ commentId +']');
    var text = textContainer.text();
    var content = "<form action='\/comments\/contract\/" + contractId + "\/edit\/"+commentId+"' method='POST'>" +
        "<div class='form-group'><textarea name='text' class='form-control'>"+text+"<\/textarea><\/div>" +
        "<div class='form-group'><input type='submit' class='btn btn-primary' value='Сохранить'><\/div>" +
        "<\/form>"
    textContainer.replaceWith(content);
    e.stopPropagation();
    e.preventDefault();
});

$('.comment_delete').click(function(e){
    $(this).hide();
    $(this).parent().find('.comment_deleteConfirm').fadeIn();
    $(this).parent().find('.comment_deleteCancel').fadeIn();
    e.stopPropagation();
    e.preventDefault();
});

$('.comment_deleteCancel').click(function(e){
    $(this).hide();
    $(this).parent().find('.comment_deleteConfirm').hide();
    $(this).parent().find('.comment_delete').fadeIn();
    e.stopPropagation();
    e.preventDefault();
});

//Feedbacks
$('.feedback_add').on('click', function(e) {
    var feedbackId = $(this).data('feedbackId');
    var addArea = $('[data-feedback-id=' + feedbackId + '].addArea');
    addArea.show();
});

$('.feedback_addCancel').click(function(e){
    var feedbackId = $(this).data('feedbackId');
    var addArea = $('[data-feedback-id=' + feedbackId + '].addArea');
    addArea.hide();
    e.stopPropagation();
    e.preventDefault();
});

$('.feedback_edit').click(function(e){
    var feedbackId = $(this).data('feedbackId');
    $('button[data-feedback-id= '+ feedbackId +']').hide();
    var textContainer = $('.feedbackText[data-feedback-id= '+ feedbackId +']');
    var text = textContainer.text();
    var content = "<form action='\/feedbacks\/edit\/"+feedbackId+"' method='POST'>" +
        "<div class='form-group'><textarea name='text' class='form-control'>"+text+"<\/textarea><\/div>" +
        "<div class='form-group'><input type='submit' class='btn btn-primary' value='Сохранить'><\/div>" +
        "<\/form>"
    textContainer.replaceWith(content);
    e.stopPropagation();
    e.preventDefault();
});

$('.feedback_delete').click(function(e){
    $(this).hide();
    $(this).parent().find('.feedback_deleteConfirm').fadeIn();
    $(this).parent().find('.feedback_deleteCancel').fadeIn();
    e.stopPropagation();
    e.preventDefault();
});

$('.feedback_deleteConfirm').click(function(e){
    var feedbackId = $(this).data('feedbackId');
    window.location = '/feedbacks/delete/'+feedbackId;
    e.stopPropagation();
    e.preventDefault();
});

$('.feedback_deleteCancel').click(function(e){
    $(this).hide();
    $(this).parent().find('.feedback_deleteConfirm').hide();
    $(this).parent().find('.feedback_delete').fadeIn();
    e.stopPropagation();
    e.preventDefault();
});



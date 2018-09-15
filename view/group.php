<script src="/jquery-3.2.1.min.js"></script>
<div>
    <h1>群 <?=htmlentities($id)?></h1>
    <ul id="chatList">

    </ul>
    <form action="?a=send_msg" >
        <input type="hidden" name="group_id" value="<?=htmlentities($id)?>">
        <input name="name" placeholder="你的名字" value="<?=isset($_SESSION['name'])?htmlentities($_SESSION['name']):'' ?>">
        <input name="msg" placeholder="你说">
        <input type="submit" value="发送">
    </form>
</div>
<script>
$(function(){
    $('form').submit(function (e) {
        e.preventDefault();
        var data = $(this).serialize();
        $.post("?a=send_msg", data, function (ret) {
            // do nothing
        });
    });
    var last_id="";
    pull_msg();
    function pull_msg() {
        $.get("?a=pull_msg", {last_id:last_id,group_id:$("[name=group_id]").val()},function (ret) {
            setTimeout(pull_msg, 100);
            var msg_lst = ret.data;
            for (let i = 0; i < msg_lst.length; i++) {
                const msg = msg_lst[i];
                var li = $("<li></li>").append($("<span></span>").text(msg.name))
                    .append("<span>:</span>").append($("<span></span>").text(msg.msg));
                $('#chatList').append(li);
            }
            var _last_id = ret.last_id;
            if (_last_id!=="")
                last_id=_last_id;
        },"json");
    }
});
</script>
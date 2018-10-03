<style>
#backToHome{
    padding: 0.5em;
    display: block;
}
.xiaowangzi{
    border: 0 solid #a00478;
    background-color: #ffffff;
    border-radius: 3rem;
    color: rgba(0,0,0,0);
    background-image: url(/mao.jpg);
    background-size: contain;
    display: inline-block;
    width: 2em;
    height: 2em;
    cursor: pointer;
}

li {
    list-style: none;
    margin: 4px 0;
}
ul {
    padding: 0;
    margin: 0;
    margin-bottom: 3em;
}
.msg {
    display: block;
    /* border: 1px solid grey; */
}
.name, .colon, .time {
    color: grey;
    font-size: small;
    padding-right: 5px;
}
#sendFormWrap{
    height:2em;
    position: fixed;
    bottom: 0;
    background: white;
}
input[name="name"] {
    width: 4em;
}
</style>
<script src="/jquery-3.3.1.min.js"></script>

<div>
    <a id="backToHome" href="/">回首页</a>
    <h1>#<?=htmlentities($id)?> <?=htmlentities($g['name']?:'')?></h1>
    <ul id="chatList">

    </ul>
    <div id="sendFormWrap">
        <form action="?a=send_msg" >
            <input type="hidden" name="group_id" value="<?=htmlentities($id)?>">
            <?php if(isset($_SESSION['name']) && $_SESSION['name']): ?>
                <span><?=htmlspecialchars($_SESSION['name'])?></span>
                <input type="hidden" name="name" value="<?=htmlentities($_SESSION['name'])?>">
            <?php else: ?>
                <input type="text" name="name" placeholder="你的名字" value="">
            <?php endif ?>
            <input name="msg" id="msgBox" placeholder="你说">
            <input type="submit" value="发送">
        </form>
    </div>
</div>
<script>
$(function(){
    $('form').submit(function (e) {
        e.preventDefault();

        // 固定名字
        var nameBox = $('[name=name]');
        var name = nameBox.val();
        if (name=='') {
            alert("请务必输入一个名字");
            return;
        }
        if (name && nameBox.attr('type') == 'text') {
            nameBox.attr('type', 'hidden');
            nameBox.after($("<span></span>").text(name));
        }

        if ($('#msgBox').val()==='') {
            return;
        }

        var data = $(this).serialize();
        $.post("?a=send_msg", data, function (ret) {
            // do nothing
        });

        // 清空
        $('#msgBox').val('');

        // 彩蛋
        if (name=='小王子') {
            $('[type=submit]').addClass("xiaowangzi")
        }
    });
    var last_id="";
    var old_time = '';
    pull_msg();
    function pull_msg() {
        $.get("?a=pull_msg", {last_id:last_id,group_id:$("[name=group_id]").val()},function (ret) {
            setTimeout(pull_msg, 100);
            var msg_lst = ret.data;
            for (let i = 0; i < msg_lst.length; i++) {
                const msg = msg_lst[i];
                var created = msg.created.substr(11, 5);
                var li = $("<li></li>").append($("<span class='name'></span>").text(msg.name));
                if (old_time!==created)
                    li.append($("<span class='time'></span>").text(created));
                li.append($("<span class='msg'></span>").text(msg.msg));
                $('#chatList').append(li);
                old_time = created;
            }

            // 卷到底部
            if (msg_lst.length > 0)
                $("html, body").animate({ scrollTop: $(document).height() }, 1000);

            var _last_id = ret.last_id;
            if (_last_id!=="")
                last_id=_last_id;
        },"json");
    }
});
</script>
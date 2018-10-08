<div>
    <form action="?">
        <input type="hidden" name="a" value="group">
        输入群名字<br>
        <input name="name"><br>
        <input type="submit" value="带我去">
    </form>
</div>

<hr>

<div>
    <div>聊天室列表：</div>
    <ul>
        <?php foreach ($rooms as $key => $room): ?>
        <li>
            <a href="?<?php echo htmlentities(http_build_query(['a' => 'group', 'id' => $room['id']])) ?>">#<?php echo htmlspecialchars($room['id']) ?> <?php echo htmlspecialchars($room['name']) ?></a>
        </li>
        <?php endforeach ?>
    </ul>
</div>
